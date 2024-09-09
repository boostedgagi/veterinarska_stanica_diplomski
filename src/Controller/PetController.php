<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\Pet;
use App\Entity\User;
use App\Form\PetType;
use App\Form\QRCodeType;
use App\Model\QRCode;
use App\Repository\PetRepository;
use App\Service\TemplatedEmailService;
use App\Service\UploadImage;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Http\Discovery\Exception\NotFoundException;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class PetController extends AbstractController
{
    use FormTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[OA\Post(
        path:'/pet',
        requestBody: new OA\RequestBody(
            description: 'Insert new pet data here,',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: PetType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns created health record.',
                content: new Model(
                    type: Pet::class,
                    groups: [ContextGroup::CREATE_PET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error'
            )
        ]
    )]
    #[Route('/pet', methods: 'POST')]
    public function create(Request $request): Response
    {
        $pet = new Pet();

        $this->handleJSONForm($request, $pet, PetType::class);

        $this->em->persist($pet);
        $this->em->flush();

        return $this->json($pet, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_PET]);
    }

    #[OA\Post(
        path: '/pet_upload_image/{id}',
        description: 'Upload image for pet.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'image',
                            type: 'file',
                            format: 'binary'
                        )
                    ]
                )
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Pet\'s ID.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns path of the uploaded image.'
            ),
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Could return string if file has errors.',
            ),
        ]
    )]
    #[Route('/pet_upload_image/{id}', methods: 'POST')]
    public function uploadProfileImage(Request $request,?Pet $pet): Response
    {
        if(!$pet){
            return $this->json('Pet not found.');
        }
        $uploadImage = new UploadImage($request, $pet, $this->em);
        $uploadImage->upload();

        return $this->json($pet, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_PET]);
    }

    #[OA\Put(
        path: '/pet/{id}',
        description: 'Change pet data.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: PetType::class)
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns updated pet.',
                content: new Model(
                    type: Pet::class,
                    groups: [ContextGroup::CREATE_PET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Pet with specified ID not found.'
            )
        ]
    )]
    #[Route('/pet/{id}', requirements: ['id' => Requirements::NUMERIC], methods: 'PUT')]
    public function edit(?Pet $pet, Request $request): Response
    {
        if(!$pet){
            return $this->json('Pet not found.');
        }
        $this->handleJSONForm($request, $pet, PetType::class);

        $this->em->persist($pet);
        $this->em->flush();

        return $this->json($pet, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_PET]);
    }

    #[OA\Delete(
        path: '/pet/{id}',
        description: 'Delete one pet.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Returns empty response.'
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Pet with specified ID not found.'
            )
        ]
    )]
    #[Route('/pet/{id}', methods: 'DELETE')]
    public function delete(?Pet $pet): Response
    {
        if(!$pet){
            return $this->json('Pet not found.');
        }

        $this->em->remove($pet);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(
        path: '/pet/{id}',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'One pet data.',
                content: new Model(
                    type: Pet::class,
                    groups: [ContextGroup::SHOW_PET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Pet with specified ID not found.',
            )
        ]
    )]
    #[Route('/pet/{id}', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function showOne(?Pet $pet): Response
    {
        if(!$pet)
        {
            return $this->json('Pet not found.',Response::HTTP_NOT_FOUND);
        }

        return $this->json($pet, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_PET]);
    }

    #[OA\Get(
        path: '/pet',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Data for all pets.',
                content: new Model(
                    type: Pet::class,
                    groups: [ContextGroup::SHOW_PET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error occurred.',
            )
        ]
    )]
    #[Route('/pet', methods: 'GET')]
    public function showAllPets(Request $request, PetRepository $repo): Response
    {
        $pets = $repo->findAll();// Todo paginate this

        return $this->json($pets, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_PET]);
    }

    #[Route('/user/{id}/pets')]
    public function showOneUserPets(User $user):JsonResponse
    {
        $pets = $user->getPets();

        return $this->json($pets,Response::HTTP_OK,[],['groups'=>ContextGroup::SHOW_MY_PETS]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[OA\Post(
        path:'/qr-code',
        requestBody: new OA\RequestBody(
            description: 'Make qr code for pet',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: QRCodeType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns path to qr code, and in background it is being sent by mail to owner.',
                content: new Model(
                    type: QRCode::class,
                    groups: [ContextGroup::CREATE_PET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error'
            )
        ]
    )]
    #[Route('/qr-code', methods: 'POST')]
    public function generateQRAndSendByMail(Request $request, PetRepository $petRepo, MailerInterface $mailer, BuilderInterface $builder): Response
    {
        $qrCode = new QRCode($builder);
        $this->handleJSONForm($request, $qrCode, QRCodeType::class);

        $pet = $petRepo->find($qrCode->getPetId());
        $generatedQRCode = $qrCode->generate();

        $email = new TemplatedEmailService($mailer);
        $email->sendQrCodeWithMail($pet, $generatedQRCode);

        return $this->json("", Response::HTTP_OK);
    }

    #[OA\Get(
        path: '/found_pet',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'number')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'One found pet data.',
                content: new Model(
                    type: Pet::class,
                    groups: [ContextGroup::SHOW_PET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Pet is not existing in our database.',
            )
        ]
    )]
    #[Route('/found_pet', methods: 'GET')]
    public function foundPet(Request $request, PetRepository $petRepo): Response
    {
        $pet = $petRepo->find($request->query->get('id'));
        if(!$pet)
            {
//            return $this->json("Pet is not existing in our database.",Response::HTTP_NOT_FOUND);
            throw new NotFoundException('Pet is not existing in our database.');
            }

        return $this->json($pet, Response::HTTP_OK, [], ['groups' => ContextGroup::FOUND_PET]);
    }
}
