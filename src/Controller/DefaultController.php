<?php

namespace App\Controller;

use App\Message\Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/',methods:'GET')]
    public function default(MessageBusInterface $messageBus):Response
    {
        $message = new Message();
        $messageBus->dispatch($message);

        return $this->json(['']);
    }
}
