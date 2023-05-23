<?php

namespace MisfitPixel\Common\Api\Controller\Abstraction;


use Doctrine\Persistence\ManagerRegistry;
use MisfitPixel\Common\Auth\Service\JwtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class BaseController
 * @package MisfitPixel\Common\Api\Controller\Abstraction
 */
abstract class BaseController extends AbstractController
{
    /** @var ManagerRegistry  */
    private ManagerRegistry $manager;

    /** @var EventDispatcherInterface  */
    protected EventDispatcherInterface $dispatcher;

    /** @var JwtService  */
    protected JwtService $jwtService;

    /** @var RequestStack  */
    protected RequestStack $requestStack;

    /**
     * @param ManagerRegistry $manager
     * @param EventDispatcherInterface $dispatcher
     * @param JwtService $jwtService
     * @param RequestStack $requestStack
     */
    public function __construct(ManagerRegistry $manager, EventDispatcherInterface $dispatcher, JwtService $jwtService, RequestStack $requestStack)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
        $this->jwtService = $jwtService;
        $this->requestStack = $requestStack;
    }

    /**
     * @return ManagerRegistry
     */
    public function getManager(): ManagerRegistry
    {
        return $this->manager;
    }

    /**
     * @param Request $request
     * @return int
     */
    public function getPage(Request $request): int
    {
        return $request->query->has('page') ? $request->query->get('page') : 1;
    }

    /**
     * @param Request $request
     * @param int $limit
     * @return int
     */
    public function getLimit(Request $request, int $limit = 50): int
    {
        return $request->query->has('limit') ? $request->query->get('limit') : $limit;
    }

    /**
     * @param Request $request
     * @param int $limit
     * @return int
     */
    public function getOffset(Request $request, int $limit = 50): int
    {
        return ($this->getPage($request) - 1) * $this->getLimit($request, $limit);
    }

    /**
     * @param Request $request
     * @param array $order
     * @return array
     */
    public function getOrder(Request $request, array $order = []): array
    {
        if ($request->query->has('order')) {
            $parts = explode(':', $request->query->get('order'));

            if (sizeof($parts) === 2) {
                $order = [$parts[0] => $parts[1]];
            }
        }

        return $order;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getContent(Request $request): array
    {
        return json_decode(($request->getContent()), true);
    }
}
