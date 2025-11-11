<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class BaseRepository extends ServiceEntityRepository
{
    public function search():array
    {
        return [];
    }
}