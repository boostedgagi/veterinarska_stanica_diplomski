<?php

namespace App\Controller;

use App\Entity\VerifyAccount;
use App\Repository\VerifyAccountRepository;
use App\Service\EmailRepository;
use App\Service\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Object_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgottenPasswordController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/password/make_new', methods: 'GET')]
    public function renewForgottenPassword(Request $request,VerifyAccountRepository $verifyRepo): Response
    {
        $queryParams = (object)$request->query->all();

        $token = $verifyRepo->find($queryParams->token_id);
        if($token){
            $this->em->remove($token);
            $this->em->flush();
            dd('token is deleted and password renewal will be finished soon');
        }

        return $this->json('');
    }

    #[Route('/password/request_new', methods: 'POST')]
    public function requestPasswordRenewal(Request $request, MailerInterface $mailer, TokenRepository $tokenRepo): Response
    {
        $data = (object)json_decode($request->getContent(), false);

        $email = new EmailRepository($mailer);

        $tokenWithData = new VerifyAccount($tokenRepo->makeNewToken());

        $this->em->persist($tokenWithData);
        $this->em->flush();

        $tokenWithData->email = $data->email;
        //here I expanded pure token object with mail where token will be sent

        $email->sendPasswordRequest($tokenWithData);

        return $this->json(['status' => 'Email with password renewal request is sent. Check your mail!'], Response::HTTP_OK);
    }
}
