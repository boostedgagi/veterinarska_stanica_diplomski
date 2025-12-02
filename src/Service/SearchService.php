<?php

namespace App\Service;

use Doctrine\ORM\Mapping\Entity;

class SearchService
{

    /**
     * @param array $criteria
     * @return void
     */
    public function search(array $criteria): void
    {
    }

    public function mapExaminationQuery(array $filtersToMap): array
    {
        $filterMap = [
            'title' => 'name',
            'time' => 'duration',
            'price' => 'price',
            'orderBy' => 'orderBy'
        ];

        return $this->mapFilters($filtersToMap,$filterMap);
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

    public function mapHealthRecordQuery(array $filtersToMap): array
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

        return $this->mapFilters($filtersToMap,$filterMap);
    }
}