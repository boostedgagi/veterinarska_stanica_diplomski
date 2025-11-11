<?php

namespace App\Service;

use ContainerBU1KZdA\get_ServiceLocator_FUl3IfhService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Knp\Component\Pager\Pagination\PaginationInterface;

class SearchService
{

    public function __construct(
        private readonly EntityManagerInterface $em
    ){}

    public function search(array $criteria, string $entityName): array
    {
        $result = [];
        $entity = $this->getEntityByName($entityName);

        $result = $this->em->getRepository($entity)->search();

        return $result;
    }


    private function getEntityByName(string $entityToSearch): Entity
    {
        return new $entityToSearch;
    }

//    public function paginate():PaginationInterface{
//        return [];
//    }

}