<?php

namespace App\Controller;

use App\Entity\OnCall;
use App\Form\OnCallType;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OnCallController extends AbstractController
{
    use FormTrait;

    #[Route('/on_call',methods:Request::METHOD_POST)]
    public function create(Request $request): Response
    {
        $onCall = new OnCall();

        $this->handleJSONForm($request,$onCall,OnCallType::class);
        return $this->json(['a'=>'b']);
    }
}
