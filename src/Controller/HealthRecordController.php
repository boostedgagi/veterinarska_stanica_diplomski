<?php

namespace App\Controller;

use App\Entity\HealthRecord;
use App\Form\HealthRecordType;
use App\Repository\HealthRecordRepository;
use App\Repository\PetRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Date;

class HealthRecordController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }


    #[Route('/health_record', methods: 'POST')]
    public function insertHealthRecord(Request $request): Response
    {
        $healthRecord = new HealthRecord();

        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => 'healthRecord_created']);
    }

    #[Route('/health_record/{id}', methods: 'PUT')]
    public function editHealthRecord(Request $request,int $id,HealthRecordRepository $repo): Response
    {
//        $form = new HealthRecordType();
//        $form->buildForm();

        $healthRecord = $repo->find($id);

        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => 'healthRecord_created']);
    }

    #[Route('/health_record/{id}', methods: 'DELETE')]
    public function delete(Request $request,int $id,HealthRecordRepository $repo): Response
    {
        $healthRecord = $repo->find($id);

        $this->em->remove($healthRecord);
        $this->em->flush();

        return $this->json("",Response::HTTP_NO_CONTENT);
    }

    #[Route('/pets/{id}/health_record', methods: 'GET')]
    public function getHealthRecord(Request $request, int $id, PetRepository $petRepo): Response
    {
        $pet = $petRepo->find($id);

        $petHealthRecords = $pet->getHealthRecords();

        return $this->json($petHealthRecords, Response::HTTP_OK, [], ['groups' => 'healthRecord_showAll']);
    }

}
