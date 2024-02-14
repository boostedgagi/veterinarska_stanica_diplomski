<?php

namespace App\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class PaginationQueryParams
{
    #[Assert\NotBlank]
    public int $page;

    #[Assert\NotBlank]
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