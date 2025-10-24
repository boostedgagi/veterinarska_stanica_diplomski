<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\Examination;
use App\Entity\User;
use App\Form\ExaminationType;
use App\Repository\ExaminationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ExaminationController extends AbstractController
{
    use FormTrait;

    public function __construct(
        private readonly EntityManagerInterface $em
    )
    {
    }

    #[OA\Get(
        path: '/examination',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Get all examinations.',
                content: new Model(
                    type: Examination::class,
                    groups: [ContextGroup::SHOW_EXAMINATION]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error occurred.',
            )
        ]
    )]
    #[Route('/examination', methods: 'GET')]
    public function showAllExaminations(ExaminationRepository $examinationRepo, Request $request): Response
    {
        $allExaminations = $examinationRepo->findAll();

        return $this->json($allExaminations, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_EXAMINATION]);
    }

    #[OA\Get(
        path: '/examination/{id}',
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
                description: 'One examination data.',
                content: new Model(
                    type: Examination::class,
                    groups: [ContextGroup::SHOW_EXAMINATION]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Examination with specified ID not found.',
            )
        ]
    )]
    #[Route('/examination/{id}', methods: 'GET')]
    public function showOne(Examination $examination): Response
    {
        return $this->json($examination, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_EXAMINATION]);
    }

    #[OA\Post(
        path: '/examination',
        description: 'Make new examination.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: ExaminationType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns created category.',
                content: new Model(
                    type: Examination::class,
                    groups: [ContextGroup::CREATE_EXAMINATION])
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Error.'
            )
        ]
    )]
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/examination', methods: 'POST')]
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        $examination = new Examination();
        $this->handleJSONForm($request, $examination, ExaminationType::class);

        $this->em->persist($examination);
        $this->em->flush();

        return $this->json($examination, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_EXAMINATION]);
    }

    #[OA\Put(
        path: '/examination/{id}',
        description: 'Change examination data.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: ExaminationType::class)
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
                description: 'Returns updated examination.',
                content: new Model(
                    type: Examination::class,
                    groups: [ContextGroup::CREATE_EXAMINATION]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Examination with specified ID not found.'
            )
        ]
    )]
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/examination/{id}', methods: 'PUT')]
    public function update(Request $request, Examination $examination): Response
    {
        $this->handleJSONForm($request, $examination, ExaminationType::class);

        $this->em->persist($examination);
        $this->em->flush();

        return $this->json($examination, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_EXAMINATION]);
    }

    #[OA\Delete(
        path: '/examination/{id}',
        description: 'Delete one examination.',
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
                description: 'Examination with specified ID not found.'
            )
        ]
    )]
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/examination/{id}', methods: 'DELETE')]
    public function deleteExamination(Examination $examination): Response
    {
        $this->em->remove($examination);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }
}
