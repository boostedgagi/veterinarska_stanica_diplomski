<?php

namespace App\Controller;

use App\Model\VerifyAccount;
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
        $queryParams = new VerifyAccount($request->query->all());

        $savedToken = $verifyRepo->findOneByValue($queryParams->getToken());
        if(!$savedToken){
            return $this->json("Account already verified",Response::HTTP_OK);
        }

        if ($savedToken['token'] && ($savedToken['expires'] > strtotime(date('Y-m-d h:i:s'))))
        {
            $user = $userRepo->find($queryParams->getUserId());
//            dd($queryParams,$savedToken);
            $user->setVerified(true);

            $token = $verifyRepo->find($queryParams->getTokenId());
            $this->em->remove($token);
            $this->em->flush();

            return $this->json("Account verified.", Response::HTTP_OK);
        }

        return $this->json("Account not verified.", Response::HTTP_FORBIDDEN);
    }
}
