<?php

namespace App\Controller;

use App\Entity\Pet;
use App\Form\PetType;
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
use Symfony\Component\Routing\Annotation\Route;

class PetController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/pets', methods: 'GET')]
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
    public function generateQRAndSendByMail(Request $request, PetRepository $repo, BuilderInterface $builder): Response
    {
        $data = json_decode($request->getContent(), false);

        $possibleQRCode = $builder->data($data->url)->build();
        $possibleQRCode->saveToFile(uniqid('', true) . '.png');
        dd($possibleQRCode);
        return $this->json($possibleQRCode->getDataUri(), Response::HTTP_OK);
    }

    #[Route('/found_pet', methods: 'GET')]
    public function foundPet(Request $request, PetRepository $repo): Response
    {
        //here i will have get method for scanned qr with route and its get params
        $queryParams = (object)$request->query->all();

        $pet = $repo->find($queryParams->id);
        return $this->json($pet, Response::HTTP_OK, [], ['groups' => 'pet_foundPet']);
    }
}
