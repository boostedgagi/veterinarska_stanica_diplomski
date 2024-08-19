<?php

namespace App\Controller;

use App\Factory\TokenFactory;
use App\Form\NewPasswordEnvelopeType;
use App\Form\RequestNewPasswordType;
use App\Model\NewPasswordEnvelope;
use App\Model\RequestNewPassword;
use App\Repository\UserRepository;
use App\Repository\TokenEntityRepository;
use App\Service\TemplatedEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgottenPasswordController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/password/make_new', methods: 'POST')]
    public function renewForgottenPassword(Request $request, TokenEntityRepository $verifyRepo, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher): Response
    {
        $newPasswordEnvelope = new NewPasswordEnvelope();
        $this->handleJSONForm($request,$newPasswordEnvelope,NewPasswordEnvelopeType::class);

        $token = $verifyRepo->findOneByValue($newPasswordEnvelope->token);
        if (!$token) {
            return $this->json('Token is not valid.', Response::HTTP_OK);
        }

        $user = $userRepo->findOneBy(['email'=>$newPasswordEnvelope->email]);
        if (!$user) {
            return $this->json('User not found.', Response::HTTP_OK);
        }

        if ($token["token"] && ($token["expires"] > strtotime(date('Y-m-d h:i:s')))) {
            $tokenObj = $verifyRepo->find($newPasswordEnvelope->token_id);

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $newPasswordEnvelope->password
            );
            $user->setPassword($hashedPassword);

            $this->em->remove($tokenObj);
            $this->em->flush();
            return $this->json([], Response::HTTP_OK);
        }
        return $this->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/password/request_new', methods: 'POST')]
    public function requestNewPassword(Request $request, MailerInterface $mailer,UserRepository $userRepo): Response
    {
        $requestNewPassword = new RequestNewPassword();
        $this->handleJSONForm($request,$requestNewPassword,RequestNewPasswordType::class);

        $user = $userRepo->findBy(['email'=>$requestNewPassword->email]);
        if (!$user) {
            return $this->json('User not found.', Response::HTTP_OK);
        }

        $tokenFactory = new TokenFactory($this->em);
        $token = $tokenFactory->save();

        $this->em->persist($token);
        $this->em->flush();

        $email = new TemplatedEmailService($mailer);
        $email->sendPasswordRequest($token,$requestNewPassword->email);

        return $this->json('Email successfully sent. Check your email inbox.',Response::HTTP_CREATED);
    }
}
