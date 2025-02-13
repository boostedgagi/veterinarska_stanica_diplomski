<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\ContactMessage;
use App\Entity\OnCall;
use App\Entity\Chat as MessageChat;
use App\Enum\ContactMessageStatus;
use App\Form\MessageType;
use App\Form\OnCallType;
use App\Helper;
use App\Message\Message;
use App\Repository\ContactMessageRepository;
use App\Repository\OnCallRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function createOnCallSession(Request $request): Response
    {
        $onCall = new OnCall();

        $this->handleJSONForm($request, $onCall, OnCallType::class);

        $this->em->persist($onCall);
        $this->em->flush();
        return $this->json($onCall, Response::HTTP_CREATED, [], ['groups' => ContextGroup::ON_CALL]);
    }

    /**
     * @param Request $request
     * @param OnCall $onCall
     * @return Response
     *
     * This endpoint accepts OnCall type of object with altered finish date time that will represent information
     * that vet goes off duty
     */
    #[Route('/on_call/{id}', methods: Request::METHOD_PUT)]
    public function finishOnCallSession(Request $request,OnCall $onCall): Response
    {
        $this->handleJSONForm($request, $onCall, OnCallType::class);
        $this->em->flush();

        return $this->json($onCall, Response::HTTP_CREATED, [], ['groups' => ContextGroup::ON_CALL]);
    }

    /**
     * @throws Exception
     */
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
//
//        $zmqContext = new \ZMQContext();
//        $socket = $zmqContext->getSocket(ZMQ::SOCKET_PUSH,'pusher');
//        $socket->connect("tcp://localhost:5555");

//        $socket->send(json_encode($message, JSON_THROW_ON_ERROR));

        return $this->json("message sent", Response::HTTP_OK);
    }

    #[OA\Post(
        path: '/initialize_chat_id',
        description:'This route initializing chat id for sending messages inside one chat.',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Returns chat id value.'
            )
        ]
    )]
    /**
     * @throws Exception
     */
    #[Route('/initialize_chat_id',methods: Request::METHOD_POST)]
    public function initializeChatId():Response
    {
        $chat = (new MessageChat())
            ->setChatId(
                Helper::makeHashedChatId()
            );

        return $this->json(['chatId'=>$chat->getChatId()]);
    }

    #[Route('/message/{id}/seen', methods: Request::METHOD_POST)]
    public function makeMessageSeen(ContactMessage $contactMessage): Response
    {
        $contactMessage
            ->setStatus(ContactMessageStatus::SEEN->value)
            ->setUpdatedAt();

        $this->em->flush();

        return $this->json($contactMessage, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CONTACT_MESSAGE_SENT]);
    }

    #[Route('/chat/{chatId}', methods: Request::METHOD_GET)]
    public function getAllMessagesFromOneChat(int $chatId, ContactMessageRepository $messageRepo): JsonResponse
    {
        /**
         * @var $chat Message[]
         */
        $chat = [];

        $messages = $messageRepo->findBy(['chatId' => $chatId], ['createdAt' => 'DESC']);
//
        foreach($messages as $message){
            $chat[] = $message;
        }

        return $this->json($chat, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_MESSAGE]);
    }

    #[Route('/active_vets', methods: Request::METHOD_GET)]
    public function getOnCallVet(OnCallRepository $onCallRepo): Response
    {
        $activeVets = $onCallRepo->getActiveVets();

        return $this->json($activeVets, Response::HTTP_OK, [], ['groups' => ContextGroup::ON_CALL]);
    }
}
