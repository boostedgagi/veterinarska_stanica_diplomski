<?php

namespace App\Service;

use App\Model\PaginatedResult;
use App\Model\PaginationQueryParams;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    /**
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param ArrayCollection|Collection|array $recordCollection
     */
    public function __construct(private readonly PaginatorInterface $paginator, private readonly Request $request, private readonly ArrayCollection|Collection|array $recordCollection)
    {
    }

    private function paginate():PaginationInterface{

        $queryParams = new PaginationQueryParams($this->request);

        return $this->paginator->paginate(
            $this->recordCollection,
            $queryParams->page,
            $queryParams->limit
        );
    }

    public function getPaginatedResult():PaginatedResult
    {
        $pagination = $this->paginate();

        return new PaginatedResult(
            $pagination->getItems(),
            $pagination->getCurrentPageNumber(),
            $pagination->getTotalItemCount()
        );
    }

}