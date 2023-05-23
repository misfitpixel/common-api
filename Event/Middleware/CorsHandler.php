<?php

namespace MisfitPixel\Common\Api\Event\Middleware;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class CorsHandler
 * @package MisfitPixel\Common\Api\Event\Middleware
 */
class CorsHandler
{
    /**
     * @param RequestEvent $event
     * @return void
     */
    public function execute(RequestEvent $event)
    {
        /**
         * return CORS catch-all response.
         */
        if($event->getRequest()->getMethod() === Request::METHOD_OPTIONS) {
            $event->setResponse(new Response(null, 204, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, GET, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Origin, x-requested-with, Authorization, Content-Type, Content-Range, Content-Disposition, Content-Description',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache'
            ]));
        }
    }

    /**
     * @param ResponseEvent $event
     * @return void
     */
    public function response(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Origin, x-requested-with, Authorization, Content-Type, Content-Range, Content-Disposition, Content-Description',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache'
        ]);

        $event->setResponse($response);
    }
}
