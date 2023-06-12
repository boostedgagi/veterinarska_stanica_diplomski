<?php

namespace App\Controller;

use App\Entity\Pet;
use App\Form\PetType;
use App\Form\QRCodeType;
use App\Model\QRCode;
use App\Repository\PetRepository;
use App\Service\EmailRepository;
use App\Service\UploadImage;
use Container4vugFVx\getPetRepositoryService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
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
    public function create(Request $request): Response
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
    public function edit(Request $request, PetRepository $repo, int $id): Response
    {
        $pet = $repo->find($id);

        $this->handleJSONForm($request, $pet, PetType::class);

        $this->em->persist($pet);
        $this->em->flush();

        return $this->json($pet, Response::HTTP_CREATED, [], ['groups' => 'pet_created']);
    }

    #[Route('/pets/{id}', methods: 'DELETE')]
    public function delete(Request $request, PetRepository $repo, int $id): Response
    {
        $pet = $repo->find($id);

        $this->em->remove($pet);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[Route('/pets/{id}', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function show(Pet $pet): Response
    {
        return $this->json($pet, Response::HTTP_OK, [], ['groups' => 'pet_showAll']);
    }

    #[Route('/pets', methods: 'GET')]
    public function showAllPets(Request $request, PetRepository $repo): Response
    {
        $pets = $repo->findAll();

        return $this->json($pets, Response::HTTP_OK, [], ['groups' => 'pet_showAll']);
    }

    #[Route('/qr-code', methods: 'POST')]
    public function generateQRAndSendByMail(Request $request, PetRepository $petRepo, MailerInterface $mailer,BuilderInterface $builder): Response
    {
        $qrCode = new QRCode($builder);

        $this->handleJSONForm($request,$qrCode,QRCodeType::class);

        $pet = $petRepo->find($qrCode->getPetId());
        $email = new EmailRepository($mailer);
        $email->sendQrCodeWithMail($pet, $qrCode->generateQRCode());

        return $this->json("QR code is generated and sent to your mail. :)", Response::HTTP_OK);
    }

    #[Route('/found_pet', methods: 'GET')]
    public function foundPet(Request $request, PetRepository $repo): Response
    {
        $queryData = (object)$request->query->all();

        $pet = $repo->find($queryData->id);

        return $this->json($pet, Response::HTTP_OK, [], ['groups' => 'pet_showAll']);
    }
}
