<?php

namespace App\Controller;

use App\Entity\Token;
use App\Model\Token as ModelToken;
use App\Repository\UserRepository;
use App\Repository\TokenEntityRepository;
use App\Service\EmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgottenPasswordController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/password/make_new', methods: 'POST')]
    public function renewForgottenPassword(Request $request, TokenEntityRepository $verifyRepo, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher): Response
    {
        $data = json_decode($request->getContent(), false);

        $token = $verifyRepo->findTokenByTokenValue($data->token);
        if (!$token) {
            return $this->json('Token is not valid.', Response::HTTP_OK);
        }

        $userData = $userRepo->findUserIdByMail($data->email);
        $user = $userRepo->find($userData[0]['user_id']);

        if (!$user) {
            return $this->json('User not found.', Response::HTTP_OK);
        }

        if ($token[0]['token'] && ($token[0]['expires'] > strtotime(date('Y-m-d h:i:s')))) {
            $tokenObj = $verifyRepo->find($data->token_id);

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data->password
            );

            $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $this->em->flush();

            $this->em->remove($tokenObj);
            $this->em->flush();
            return $this->json('You changed your password!', Response::HTTP_OK);
        }
        return $this->json('Something wrong happened, try again later.', Response::HTTP_OK);
    }

    #[Route('/password/request_new', methods: 'POST')]
    public function requestPasswordRenewal(Request $request, MailerInterface $mailer,UserRepository $userRepo): Response
    {
        $data = (object)json_decode($request->getContent(), false);

        $email = new EmailRepository($mailer);

        $preparedToken = (new ModelToken())->make30MinToken();

        $userData = $userRepo->findUserIdByMail($data->email);
        $user = $userRepo->find($userData[0]['user_id']);
        if (!$user) {
            return $this->json('User not found.', Response::HTTP_OK);
        }

        $token = new Token($preparedToken);

        $this->em->persist($token);
        $this->em->flush();

        $preparedToken->setEmailAddress($data->email);
        $email->sendPasswordRequest($token->getId(),$preparedToken);

        return $this->json(['status' => 'Email with password renewal request is sent. Check your mail!'], Response::HTTP_OK);
    }
}
