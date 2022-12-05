<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VerifyAccountController extends AbstractController
{
    #[Route('/verify/account', name: 'app_verify_account')]
    public function index(): Response
    {
        return $this->render('verify_account/index.html.twig', [
            'controller_name' => 'VerifyAccountController',
        ]);
    }
}
