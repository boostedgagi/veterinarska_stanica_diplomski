<?php

namespace App\Controller;

use App\Entity\Examination;
use App\Form\ExaminationType;
use App\Repository\ExaminationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExaminationController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/examinations', methods: 'GET')]
    public function showAllExaminations(ExaminationRepository $repo): Response
    {
        $allExaminations = $repo->findAll();

        return $this->json($allExaminations, Response::HTTP_OK, [], ['groups' => 'examination_showAll']);
    }

    #[Route('/examinations/{id}', methods: 'GET')]
    public function showOneExamination(Examination $examination): Response
    {
        return $this->json($examination, Response::HTTP_OK, [], ['groups' => 'examination_showAll']);
    }

    #[Route('/examinations', methods: 'POST')]
    public function createExamination(Request $request): Response
    {
        $examination = new Examination();
        $this->handleJSONForm($request, $examination, ExaminationType::class);

        $this->em->persist($examination);
        $this->em->flush();

        return $this->json($examination, Response::HTTP_CREATED, [], ['groups' => 'examination_created']);
    }

    #[Route('/examinations/{id}', methods: 'PUT')]
    public function updateExamination(Request $request, int $id, ExaminationRepository $repo): Response
    {
        $examination = $repo->find($id);
        $this->handleJSONForm($request, $examination, ExaminationType::class);

        $this->em->persist($examination);
        $this->em->flush();

        return $this->json($examination, Response::HTTP_CREATED, [], ['groups' => 'examination_created']);
    }

    #[Route('/examinations/{id}', methods: 'DELETE')]
    public function deleteExamination(int $id, ExaminationRepository $repo): Response
    {
        $examination = $repo->find($id);

        $this->em->remove($examination);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }


}
