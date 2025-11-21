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


    private function getEntityByName(string $entityToSearch): Entity
    {
        return new $entityToSearch;
    }

//    public function paginate():PaginationInterface{
//        return [];
//    }

}