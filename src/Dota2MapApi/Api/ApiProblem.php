<?php

namespace Dota2MapApi\Api;

use Symfony\Component\HttpFoundation\Response;

/**
 * A wrapper for holding data to be used for a application/problem+json response
 */
class ApiProblem
{
    const TYPE_VALIDATION_ERROR = 'validation_error';
    const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';
    const TYPE_INVALID_REQUEST_PARAMETER = 'invalid_request';
    const TYPE_INVALID_REQUEST_CORD = 'invalid_cord';

    private static $titles = array(
        self::TYPE_VALIDATION_ERROR => 'There was a validation error',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON format sent',
        self::TYPE_INVALID_REQUEST_PARAMETER => 'Invalid Request parameter. Make sure you are sending the follow structure: ITEM;CORDX:CORDY',
        self::TYPE_INVALID_REQUEST_CORD => 'Invalid Cord parameter. Make sure you are sending the follow structure: ITEM;CORDX:CORDY where CORD are split by :'
    );

    private $statusCode;

    private $type;

    private $title;

    private $extraData = array();

    public function __construct($statusCode, $type = null)
    {
        $this->statusCode = $statusCode;
        $this->type = $type;

        if ($type === null) {
            // no type? The default is about:blank and the title should
            // be the standard status code message
            $this->type = 'about:blank';
            $this->title = isset(Response::$statusTexts[$statusCode])
                ? Response::$statusTexts[$statusCode]
                : 'Unknown status code :(';
        } else {
            if (!isset(self::$titles[$type])) {
                throw new \Exception(sprintf(
                    'No title for type "%s". Did you make it up?',
                    $type
                ));
            }

            $this->title = self::$titles[$type];
        }
    }

    public function toArray()
    {
        return array_merge(
            $this->extraData,
            array(
                'status' => $this->statusCode,
                'type' => $this->type,
                'title' => $this->title,
            )
        );
    }

    public function set($name, $value)
    {
        $this->extraData[$name] = $value;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
