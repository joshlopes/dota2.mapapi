<?php
namespace Dota2MapApi\Controller;

use Dota2MapApi\Api\ApiProblem;
use Dota2MapApi\Api\ApiProblemException;
use Dota2MapApi\Map\Item\HeroItem;
use Dota2MapApi\Map\MapService;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;

class MapController extends BaseController
{

    protected function addRoutes(ControllerCollection $controllers)
    {
        $controllers->get('/api/map/{items}', array($this, 'showAction'));
    }

    public function showAction($items)
    {
        /** @var MapService $mapService */
        $mapService = $this->get('map.service');
        $this->parseParameter($items, $mapService);

        $map = $mapService->buildMap();
        return new Response($map, 200, ['Content-Type' => 'image/png']);
    }

    /**
     * Parameter have the follow structure: sven;xx:yy,qop;xx:yy
     * ITEM;CORDX:CORDY split by comma
     * @param $parameter
     * @param MapService $mapService
     * @return array
     */
    private function parseParameter($parameter, MapService $mapService)
    {
        $parameters = explode(",", $parameter);
        foreach ($parameters as $parameter) {
            $parameter = explode(";", $parameter);
            if (count($parameter) !== 2) {
                $problem = new ApiProblem(
                    400,
                    ApiProblem::TYPE_INVALID_REQUEST_PARAMETER
                );
                throw new ApiProblemException($problem);
            }
            $cords = explode(":", $parameter[1]);
            if (count($parameter) !== 2) {
                $problem = new ApiProblem(
                    400,
                    ApiProblem::TYPE_INVALID_REQUEST_CORD
                );
                throw new ApiProblemException($problem);
            }
            $mapService->addItem(new HeroItem($parameter[0], ['x' => $cords[0], 'y' => $cords[1]]));
        }
    }
}

