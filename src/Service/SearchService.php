<?php

namespace App\Service;

use App\Model\PaginatedResult;
use App\Repository\ExaminationRepository;
use App\Repository\HealthRecordRepository;
use Doctrine\ORM\Mapping\Entity;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchService
{

    public function __construct(public readonly PaginatorInterface $paginator){

    }

    /**
     * @param array $criteria
     * @return void
     */
    public function search(array $criteria): void
    {
    }

    public function mapExaminationQueryAndSearch(array $filtersToMap,Request $request,ExaminationRepository $examinationRepo): PaginatedResult
    {
        $filterMap = [
            'title' => 'name',
            'time' => 'duration',
            'price' => 'price',
            'orderBy' => 'orderBy'
        ];

        $mappedFilters = $this->mapFilters($filtersToMap,$filterMap);
        $searchedData = $examinationRepo->search($mappedFilters);

        $paginationService = new PaginationService($this->paginator, $request, $searchedData);
        return $paginationService->getPaginatedResult();
    }

    private function mapFilters(array $filtersToMap,$filterMap): array
    {
        $mappedFilters = [];

        foreach ($filtersToMap as $key => $value) {
            if (isset($filterMap[$key])) {
                $mappedFilters[$filterMap[$key]] = $value;
            }
        }
        return $mappedFilters;
    }

    public function mapHealthRecordQueryAndSearch(array $filtersToMap,Request $request, HealthRecordRepository $healthRecordRepo): PaginatedResult
    {
        $filterMap = [
            'vet' => 'vetId',
            'pet' => 'petId',
            'examination' => 'examinationId',
            'start'=>'startedAt',
            'finish'=>'finishedAt',
            'comment'=>'comment',
            'status'=>'status',
            'madeByVet'=>'madeByVet'
        ];

        $mappedFilters = $this->mapFilters($filtersToMap,$filterMap);
        $searchedData = $healthRecordRepo->search($mappedFilters);

        $paginationService = new PaginationService($this->paginator, $request, $searchedData);
        return $paginationService->getPaginatedResult();
    }
}