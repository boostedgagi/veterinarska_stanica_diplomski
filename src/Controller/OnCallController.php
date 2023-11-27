<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\ContactMessage;
use App\Entity\OnCall;
use App\Enum\ContactMessageStatus;
use App\Form\MessageType;
use App\Form\OnCallType;
use App\Message\Message;
use App\Repository\OnCallRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class OnCallController extends AbstractController
{
    use FormTrait;

    public function __construct(
        private readonly EntityManagerInterface $em
    )
    {
    }


    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/on_call', methods: Request::METHOD_POST)]
    public function createOnCall(Request $request): Response
    {
        $onCall = new OnCall();

        $this->handleJSONForm($request, $onCall, OnCallType::class);
        $this->em->persist($onCall);
        $this->em->flush();
        return $this->json($onCall, Response::HTTP_CREATED, [], ['groups' => ContextGroup::ON_CALL]);
    }

    #[OA\Post(
        path: '/message',
        requestBody: new OA\RequestBody(
            description: 'Send message',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: MessageType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Returns empty string, on FE page will automatically reload.'
            )
        ]
    )]
    #[Route('/message', methods: Request::METHOD_POST)]
    public function sendMessage(Request $request, MessageBusInterface $messageBus): Response
    {
        $message = new Message();

        $this->handleJSONForm($request, $message, MessageType::class);
        $messageBus->dispatch($message);

        return $this->json("", Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/message/make_hash',
        description: 'This endpoint makes hash for chatId',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'One health record data.',
                content: new Model(
                    type: 'string'
                )
            )
        ]
    )]
    #[Route('/message/make_hash', methods: Request::METHOD_GET)]
    public function provideInitialHash(): Response
    {
        return $this->json(hash('sha256', random_int(1, 100)));
    }

    #[Route('/message/{id}/seen', methods: Request::METHOD_POST)]
    public function makeMessageSeen(Request $request, ContactMessage $contactMessage): Response
    {
        $contactMessage
            ->setStatus(ContactMessageStatus::SEEN->value)
            ->setUpdatedAt();

        $this->em->flush();

        return $this->json($contactMessage, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CONTACT_MESSAGE_SENT]);
    }

    #[Route('/active_vets',methods: Request::METHOD_GET)]
    public function getOnCallVet(OnCallRepository $onCallRepo): Response
    {
        $activeVets = $onCallRepo->getActiveVets();

        return $this->json($activeVets, Response::HTTP_OK, [], ['groups' => ContextGroup::ON_CALL]);
    }
}
