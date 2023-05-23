<?php

namespace MisfitPixel\Common\Api\Controller;


use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class RootController
 * @package MisfitPixel\Common\Api\Controller
 */
class RootController
{
    /**
     * @return JsonResponse
     */
    public function root(): JsonResponse
    {
        $version = shell_exec('git describe --tags `git rev-list --tags --max-count=1`');

        /**
         * TODO: move docs to config.
         */
        return new JsonResponse([
            'version' => ($version != null) ? $version : 'Development',
            'documentation_url' => '',
            'message' => ''
        ]);
    }
}
