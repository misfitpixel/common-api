<?php

namespace MisfitPixel\Common\Api\Response;


use MisfitPixel\Common\Api\Controller\Abstraction\BaseController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class JsonResponse
 * @package MisfitPixel\Common\Api\Response
 */
class JsonResponse extends \Symfony\Component\HttpFoundation\JsonResponse
{
    /** @var mixed */
    private $entity;

    /**
     * @param array $data
     * @param int $status
     * @param Request $request
     */
    public function __construct(array $data, int $status, Request $request)
    {
        /**
         * add paging.
         */
        if(isset($data['items'])) {
            $data['paging'] = $this->addPaging($data, $request);
        }

        parent::__construct($data, $status, [], false);
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param $entity
     * @return JsonResponse
     */
    public function setEntity($entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @param array $data
     * @param Request $request
     * @return array
     */
    public function addPaging(array $data, Request $request): array
    {
        $query = [];

        /**
         * set query.
         */
        $page = ($request->query->has('page')) ? $request->query->get('page') : 1;
        $limit = ($request->query->has('limit')) ? $request->query->get('limit') : BaseController::DEFAULT_PAGE_SIZE;

        $request->query->remove('page');
        $request->query->remove('limit');

        foreach($request->query as $key => $value) {
            $query[$key] = $value;
        }

        /**
         * build URLs.
         */
        $endpoint = explode('?', $request->getUri())[0];

        $prev = sprintf('%s?%s', $endpoint, http_build_query(array_merge($query, [
            'page' => $page - 1,
            'limit' => $limit
        ])));

        $next = sprintf('%s?%s', $endpoint, http_build_query(array_merge($query, [
            'page' => $page + 1,
            'limit' => $limit
        ])));

        return [
            'prev' => ($page > 1) ? $prev : null,
            'next' => (!$request->query->has('limit') !== null && sizeof($data['items']) >= $limit) ? $next : null
        ];
    }
}
