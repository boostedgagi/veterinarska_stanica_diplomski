<?php

namespace App\Controller;

use App\Entity\Token;
use App\Factory\TokenFactory;
use App\Form\RequestNewPasswordType;
use App\Model\RequestNewPassword;
use App\Model\Token as ModelToken;
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
        $data = json_decode($request->getContent(), false);

        $token = $verifyRepo->findTokenByTokenValue($data->token);
        if (!$token) {
            return $this->json('Token is not valid.', Response::HTTP_OK);
        }

        $user = $userRepo->findBy(['email'=>$data->email]);

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
        $email = new TemplatedEmailService($mailer);

        $this->em->persist($token);
        $this->em->flush();

        $email->sendPasswordRequest($token,$requestNewPassword->email);

        return $this->json([],Response::HTTP_CREATED);
    }
}
