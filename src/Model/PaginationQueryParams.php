<?php

namespace App\Model;

use Symfony\Component\HttpFoundation\Request;

class PaginationQueryParams
{
    public int $page;

    public int $limit;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->page = $request->query->getInt('page');
        $this->limit = $request->query->getInt('limit');
    }
}