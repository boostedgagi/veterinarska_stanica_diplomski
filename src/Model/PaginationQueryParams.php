<?php

namespace App\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class PaginationQueryParams
{
    public const int DEFAULT_LIMIT = 10;
    public const int MAX_LIMIT = 100;

    #[Assert\NotBlank]
    public int $page;

    #[Assert\NotBlank]
    public int $limit;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        // defaults instead of a 500 from the paginator when ?page / ?limit are missing or invalid
        $this->page = max(1, $request->query->getInt('page', 1));
        $this->limit = min(self::MAX_LIMIT, max(1, $request->query->getInt('limit', self::DEFAULT_LIMIT)));
    }
}