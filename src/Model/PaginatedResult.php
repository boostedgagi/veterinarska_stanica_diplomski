<?php

namespace App\Model;

use App\ContextGroup;
use Symfony\Component\Serializer\Annotation\Groups;

class PaginatedResult
{
    public array $items;

    public int $itemsCount;

    public int $currentPageNumber;

    public int $totalItemsCount;

    /**
     * @param array $items
     * @param int $currentPageNumber
     * @param int $totalItemsCount
     */
    public function __construct(array $items, int $currentPageNumber, int $totalItemsCount)
    {
        $this->items = $items;
        $this->currentPageNumber = $currentPageNumber;
        $this->totalItemsCount = $totalItemsCount;
    }

    /**
     * @return array
     */
    #[Groups(
        [
            ContextGroup::SHOW_VET,
            ContextGroup::SHOW_HEALTH_RECORD,

        ]
    )]
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     * @return PaginatedResult
     */
    public function setItems(array $items): PaginatedResult
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return int
     */
    #[Groups(
        [
//            ContextGroup::SHOW_VET,
//            ContextGroup::SHOW_HEALTH_RECORD,
        ]
    )]
    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    /**
     * @param int $itemsCount
     * @return PaginatedResult
     */
    public function setItemsCount(int $itemsCount): PaginatedResult
    {
        $this->itemsCount = $itemsCount;
        return $this;
    }

    /**
     * @return int
     */
    #[Groups(
        [
            ContextGroup::SHOW_VET,
            ContextGroup::SHOW_HEALTH_RECORD,
        ]
    )]
    public function getCurrentPageNumber(): int
    {
        return $this->currentPageNumber;
    }

    /**
     * @param int $currentPageNumber
     * @return PaginatedResult
     */
    public function setCurrentPageNumber(int $currentPageNumber): PaginatedResult
    {
        $this->currentPageNumber = $currentPageNumber;
        return $this;
    }

    /**
     * @return int
     */
    #[Groups(
        [
            ContextGroup::SHOW_VET,
            ContextGroup::SHOW_HEALTH_RECORD,
        ]
    )]
    public function getTotalItemsCount(): int
    {
        return $this->totalItemsCount;
    }

    /**
     * @param int $totalItemsCount
     * @return PaginatedResult
     */
    public function setTotalItemsCount(int $totalItemsCount): PaginatedResult
    {
        $this->totalItemsCount = $totalItemsCount;
        return $this;
    }


}