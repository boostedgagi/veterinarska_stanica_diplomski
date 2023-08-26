<?php
namespace App\Controller;

use App\Form\MessageType;
use App\Message\Message;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    use FormTrait;

    #[Route('/',methods:Request::METHOD_POST)]
    public function default(Request $request,MessageBusInterface $messageBus):Response
    {
        $message = new Message();
        $this->handleJSONForm($request,$message,MessageType::class);
        $messageBus->dispatch($message);

        return $this->json(['']);
    }
}
