<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\TokenEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VerifyAccountController extends AbstractController
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/verify_account', methods: 'GET')]
    public function verifyAccount(Request $request, TokenEntityRepository $verifyRepo, UserRepository $userRepo): Response
    {
        $queryParams = (object)$request->query->all();

        $savedToken = $verifyRepo->findOneByValue($queryParams->token);
        if ($savedToken['token'] && ($savedToken['expires'] > strtotime(date('Y-m-d h:i:s'))))
        {
            $user = $userRepo->find($queryParams->user_id);
            $user->setAllowed(true);

            $token = $verifyRepo->find($queryParams->token_id);
            $this->em->remove($token);
            $this->em->flush();
        }
        return $this->json("Account verified.", Response::HTTP_OK);
    }
}
