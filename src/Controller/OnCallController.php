<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\OnCall;
use App\Form\MessageType;
use App\Form\OnCallType;
use App\Message\Message;
use Exception;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class OnCallController extends AbstractController
{
    use FormTrait;
    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/on_call',methods:Request::METHOD_POST)]
    public function createOnCall(Request $request): Response
    {
        $onCall = new OnCall();

        $this->handleJSONForm($request,$onCall,OnCallType::class);
        return $this->json($onCall, Response::HTTP_CREATED, [], ['groups' => ContextGroup::ON_CALL_CREATED]);
    }

    #[Route('/message', methods:Request::METHOD_POST)]
    public function sendMessage(Request $request,MessageBusInterface $messageBus):Response
    {
        $message = new Message();

        $this->handleJSONForm($request,$message,MessageType::class);
        $messageBus->dispatch($message);

        return $this->json($message,ContextGroup::CONTACT_MESSAGE_SENT);
    }

    /**
     * @throws Exception
     */
    #[Route('/message/make_hash', methods:Request::METHOD_GET)]
    public function provideInitialHash():Response
    {
        return $this->json(hash('sha256',random_int(1,100)));
    }
}
