<?php

namespace App\Controller;

use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Form\PetType;
use App\Repository\HealthRecordRepository;
use App\Repository\PetRepository;
use App\Service\EmailRepository;
use App\Service\UploadImage;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCodeBundle\Response\QrCodeResponse;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

class PetController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/pets', methods: 'POST')]
    public function createPet(Request $request): Response
    {
        $pet = new Pet();
        $this->handleJSONForm($request, $pet, PetType::class);
        $this->em->persist($pet);
        $this->em->flush();

        return $this->json($pet, Response::HTTP_CREATED, [], ['groups' => 'pet_created']);
    }

    #[Route('/pet_upload_image/{id}', methods: 'POST')]
    public function uploadProfileImage(Request $request, PetRepository $repo, int $id): Response
    {
        $pet = $repo->find($id);
        $uploadImage = new UploadImage($request, $pet, $this->em);
        $uploadImage->upload();
        return $this->json($pet, Response::HTTP_CREATED, [], ['groups' => 'pet_created']);
    }

    #[Route('/pets/{id}', methods: 'PUT')]
    public function updatePet(Request $request, PetRepository $repo, int $id): Response
    {
        $pet = $repo->find($id);

        $this->handleJSONForm($request, $pet, PetType::class);

        $this->em->persist($pet);
        $this->em->flush();

        return $this->json($pet, Response::HTTP_CREATED, [], ['groups' => 'pet_created']);
    }

    #[Route('/pets/{id}', methods: 'DELETE')]
    public function deletePet(Request $request, PetRepository $repo, int $id): Response
    {
        $pet = $repo->find($id);

        $this->em->remove($pet);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[Route('/pets/{id}', methods: 'GET')]
    public function showOnePet(Request $request, PetRepository $repo, int $id): Response
    {
        $pet = $repo->find($id);

        return $this->json($pet, Response::HTTP_OK, [], ['groups' => 'pet_showAll']);
    }

    #[Route('/pets', methods: 'GET')]
    public function showAllPets(Request $request, PetRepository $repo): Response
    {
        $pets = $repo->findAll();

        return $this->json($pets, Response::HTTP_OK, [], ['groups' => 'pet_showAll']);
    }

    #[Route('/qr-code', methods: 'POST')]
    public function generateQRAndSendByMail(Request $request, PetRepository $repo, MailerInterface $mailer, BuilderInterface $builder): Response
    {
        $data = json_decode($request->getContent(), false);
        $pet = $repo->find($data->id);

        $possibleQRCode = $builder->data($data->url . $data->id)->build();
        $qrCodePath = 'qr-codes/' . uniqid('', true) . '.png';

        $possibleQRCode->saveToFile($qrCodePath);

        $email = new EmailRepository($mailer);
        $email->sendQrCodeWithMail($pet, $qrCodePath);

        return $this->json($qrCodePath, Response::HTTP_OK);
    }

    #[Route('/found_pet', methods: 'GET')]
    public function foundPet(Request $request, PetRepository $repo): Response
    {
        $queryParams = (object)$request->query->all();
        $pet = $repo->find($queryParams->id);

        return $this->json($pet, Response::HTTP_OK, [], ['groups' => 'pet_foundPet']);
    }


    #[Route('/notify', methods: 'GET')]
    public function notifier(MailerInterface $mailer, NotifierInterface $notifier, HealthRecordRepository $healthRecRepo): Response
    {

        $examinationsToRemind = $healthRecRepo->getExaminationsInNextSevenDays();
        if(count($examinationsToRemind)===0){
            return $this->json('There are no owners to notify!', Response::HTTP_OK);
        }
        foreach ($examinationsToRemind as $examination) {
            try {
                $email = new EmailRepository($mailer);
                //$examination is the HealthRecordType
                $email->notifyUserAboutPetHaircut($notifier, $examination);

            } catch (\Exception $exception) {
                return $this->json($exception, Response::HTTP_OK);
            }
        }

        return $this->json('Owners are notified!', Response::HTTP_OK);
    }
}
