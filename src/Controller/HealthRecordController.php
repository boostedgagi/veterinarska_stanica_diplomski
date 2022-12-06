<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthRecordController extends AbstractController
{
    #[Route('/health_record',methods: 'POST')]
    public function insertHealthRecord(): Response
    {




        return $this->json("",Response::HTTP_NO_CONTENT);
    }
}
