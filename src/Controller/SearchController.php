<?php

namespace App\Controller;

use App\ContextGroup;
use App\Repository\ExaminationRepository;
use App\Repository\HealthRecordRepository;
use App\Service\PaginationService;
use App\Service\SearchService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    public function __construct(
        public readonly SearchService $searchService
    )
    {
    }

    #[Route('/search/examination', methods: Request::METHOD_GET)]
    public function searchExamination(Request $request, ExaminationRepository $examinationRepo): Response
    {
        $searchedData = $this->searchService->mapExaminationQueryAndSearch(
            $request->query->all(),
            $request,
            $examinationRepo);

        return $this->json($searchedData, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_EXAMINATION]);
    }

    #[Route('/search/health_record', methods: Request::METHOD_GET)]
    public function searchHealthRecord(Request $request, HealthRecordRepository $healthRecordRepository): Response
    {
        $searchedData = $this->searchService->mapHealthRecordQueryAndSearch(
            $request->query->all(),
            $request,
            $healthRecordRepository
        );

        return $this->json($searchedData, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }


}
