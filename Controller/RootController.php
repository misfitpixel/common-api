<?php

namespace MisfitPixel\Common\Api\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class RootController
 * @package MisfitPixel\Common\Api\Controller
 */
class RootController extends AbstractController
{
    /**
     * @return JsonResponse
     */
    public function root(): JsonResponse
    {
        $version = shell_exec('git describe --tags `git rev-list --tags --max-count=1`');
        $documentationUrl = null;
        $message = null;

        try {
            $documentationUrl = $this->getParameter('misfitpixel.common.documentation.url');

        } catch(InvalidArgumentException $e) {
            // do nothing.
        }

        try {
            $message = $this->getParameter('misfitpixel.common.documentation.message');

        } catch(InvalidArgumentException $e) {
            // do nothing.
        }

        return new JsonResponse([
            'version' => ($version != null) ? $version : 'Development',
            'documentation_url' => $documentationUrl,
            'message' => $message
        ]);
    }
}
