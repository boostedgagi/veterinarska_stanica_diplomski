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
        $mappedQueryFilters = $this->searchService->mapExaminationQuery($request->query->all());

        $searchedData = $examinationRepo->search($mappedQueryFilters);

        return $this->json($searchedData, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_EXAMINATION]);
    }

    #[Route('/search/health_record', methods: Request::METHOD_GET)]
    public function searchHealthRecord(Request $request, HealthRecordRepository $healthRecordRepository,PaginatorInterface $paginator): Response
    {
        //mapping query params to ORM namings
        $mappedQueryFilters = $this->searchService->mapHealthRecordQuery($request->query->all());
        //searching database after mapping query params
        $searchedData = $healthRecordRepository->search($mappedQueryFilters);

        return $this->json($searchedData, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }


}
