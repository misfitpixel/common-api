<?php

namespace MisfitPixel\Common\Api\Event\Middleware;


use MisfitPixel\Common\Api\Service\ValidatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SchemaValidator
 * @package MisfitPixel\Common\Api\Event\Middleware
 */
class SchemaValidator
{
    /** @var ContainerInterface  */
    private ContainerInterface $container;

    /** @var ValidatorService  */
    private ValidatorService $validator;

    /**
     * SchemaValidator constructor.
     * @param ContainerInterface $container
     * @param ValidatorService $validator
     */
    public function __construct(ContainerInterface $container, ValidatorService $validator)
    {
        $this->container = $container;
        $this->validator = $validator;
    }

    /**
     * @param RequestEvent $event
     * @return void
     * @throws \Exception
     */
    public function execute(RequestEvent $event)
    {
        $this->validator->validate(
            json_decode($event->getRequest()->getContent(), true),
            sprintf('%s/config/schema_validator/%s.yml',
                $this->container->get('kernel')->getProjectDir(),
                $event->getRequest()->get('_route')
            )
        );
    }
}
