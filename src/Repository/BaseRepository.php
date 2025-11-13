<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class BaseRepository extends ServiceEntityRepository
{
    /**
     * @param string $alias
     * @return QueryBuilder
     * @see This function decomposes the query builder into fundamental components,
     * which will be inherited and further refined in each entity-specific repository.
     */
    public function createQueryBuilderForSearch(string $alias): QueryBuilder
    {
        return $this->createQueryBuilder($alias);
    }

//    public function applySearchFilters(QueryBuilder $qb, string $alias, array $filters): void
//    {
//        foreach ($filters as $field => $value){
//            if($value || $value != ''){
//                if(property_exists($this->getEntityName(),$field)){
//                    $qb->andWhere("$alias.$field")
//                        ->setParameter($field,$value);
//                }
//            }
//        }
//    }

    public function applyEqualsFilter($qb,$alias, $key, $value):QueryBuilder
    {
        return $qb->andWhere("$alias.$key = :value")
            ->setParameter('value',"$value");
    }


}