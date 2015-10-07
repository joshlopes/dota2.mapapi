<?php

namespace Dota2MapApi;

use Dota2MapApi\Api\ApiProblem;
use Dota2MapApi\Api\ApiProblemException;
use Dota2MapApi\Map\MapService;
use Silex\Application as SilexApplication;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Yaml\Parser;

class Application extends SilexApplication
{

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->configureParameters();
        $this->configureProviders();
        $this->configureServices();
        $this->configureListeners();
    }

    /**
     * Dynamically finds all *Controller.php files in the Controller directory,
     * instantiates them, and mounts their routes.
     *
     * This is done so we can easily create new controllers without worrying
     * about some of the Silex mechanisms to hook things together.
     */
    public function mountControllers()
    {
        $controllerPath = 'src/Dota2MapApi/Controller';
        $finder = new Finder();
        $finder->in($this['root_dir'].'/'.$controllerPath)
            ->name('*Controller.php')
        ;

        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            // e.g. Api/FooController.php
            $cleanedPathName = $file->getRelativePathname();
            // e.g. Api\FooController.php
            $cleanedPathName = str_replace('/', '\\', $cleanedPathName);
            // e.g. Api\FooController
            $cleanedPathName = str_replace('.php', '', $cleanedPathName);

            $class = 'Dota2MapApi\\Controller\\'.$cleanedPathName;

            // don't instantiate the abstract base class
            $refl = new \ReflectionClass($class);
            if ($refl->isAbstract()) {
                continue;
            }

            $this->mount('/', new $class($this));
        }
    }

    private function configureProviders()
    {
        // URL generation
        $this->register(new UrlGeneratorServiceProvider());

        // Twig
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => $this['root_dir'].'/src/Dota2MapApi/Resources/views',
        ));

        // Monolog
        $this->register(new MonologServiceProvider(), array(
            'monolog.logfile' => $this['root_dir'].'/logs/development.log',
        ));
    }

    private function configureParameters()
    {
        $yaml = new Parser();

        $this['root_dir'] = __DIR__.'/../..';
        $this['config_dir'] = __DIR__.'/Resources/config';
        $this['config'] = [
            'settings' => $yaml->parse(file_get_contents($this['config_dir'] . '/settings.yml')),
            'heroes' => $yaml->parse(file_get_contents($this['config_dir'] . '/heroes.yml')),
            'items' => $yaml->parse(file_get_contents($this['config_dir'] . '/items.yml')),
            'map' => $yaml->parse(file_get_contents($this['config_dir'] . '/map.yml'))
        ];
    }

    private function configureServices()
    {
        $app = $this;

        $this['map.service'] = $this->share(function() use ($app) {
            $service = new MapService($app['config']);

            return $service;
        });
    }

    private function configureListeners()
    {
        $app = $this;

        $this->error(function(\Exception $e, $statusCode) use ($app) {
            // only act on /api URLs
            if (strpos($app['request']->getPathInfo(), '/api') !== 0) {
                return;
            }

            // allow 500 errors in debug to be thrown
            if ($app['debug'] && $statusCode == 500) {
                return;
            }

            if ($e instanceof ApiProblemException) {
                $apiProblem = $e->getApiProblem();
            } else {
                $apiProblem = new ApiProblem(
                    $statusCode
                );

                /*
                 * If it's an HttpException message (e.g. for 404, 403),
                 * we'll say as a rule that the exception message is safe
                 * for the client. Otherwise, it could be some sensitive
                 * low-level exception, which should *not* be exposed
                 */
                if ($e instanceof HttpException) {
                    $apiProblem->set('detail', $e->getMessage());
                }
            }

            $data = $apiProblem->toArray();
            // making type a URL, to a temporarily fake page
            if ($data['type'] != 'about:blank') {
                $data['type'] = 'http://localhost:8000/docs/errors#'.$data['type'];
            }
            $response = new JsonResponse(
                $data,
                $apiProblem->getStatusCode()
            );
            $response->headers->set('Content-Type', 'application/problem+json');

            return $response;
        });
    }
}
