<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search/examination',methods: Request::METHOD_GET)]
    /**
     * @see I decided to work with separate route/endpoint for every single entity that I want to search through.
     */
    public function searchExaminations(Request $request): Response
    {
        $examinationName = $request->get('name',null);
        $examinationDuration = $request->get('duration',null);
        $examinationPrice = $request->get('price',null);



        return $this->json();
    }
}
