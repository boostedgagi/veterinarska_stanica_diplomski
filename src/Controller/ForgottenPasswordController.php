<?php

namespace App\Controller;

use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Factory\TokenFactory;
use App\Form\NewPasswordAuthenticatedType;
use App\Form\NewPasswordEnvelopeType;
use App\Form\RequestNewPasswordType;
use App\Model\NewPasswordAuthenticated;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ForgottenPasswordController extends AbstractController
{
    use FormTrait;

    public function __construct(private EntityManagerInterface $em)
    {
    }


    /**
     * @param Request $request
     * @param TokenEntityRepository $verifyRepo
     * @param UserPasswordHasherInterface $passwordHasher
     * @return Response
     *
     * Here i should make a logic for registered user if logged in change password
     * without token and other things
     */
    #[Security(name: null)] // public route, see security.yaml
    #[Route('/password/make_new', methods: 'POST')]
    public function renewForgottenPassword(
        Request                     $request,
        TokenEntityRepository       $verifyRepo,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $newPasswordEnvelope = new NewPasswordEnvelope();
        $this->handleJSONForm($request, $newPasswordEnvelope, NewPasswordEnvelopeType::class);

        $token = $verifyRepo->findOneByValue($newPasswordEnvelope->token);
        // the token only resets the password of the account it was issued for
        if (!$token || !$token->getUser()
            || strcasecmp((string) $token->getUser()->getEmail(), $newPasswordEnvelope->email) !== 0) {
            return $this->json('Token is not valid.', Response::HTTP_OK);
        }
        $user = $token->getUser();

        if (!$token->isExpired()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $newPasswordEnvelope->password
            );
            $user->setPassword($hashedPassword);

            $this->em->remove($token);
            $this->em->flush();
            return $this->json([], Response::HTTP_OK);
        }
        return $this->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Security(name: null)] // public route, see security.yaml
    #[Route('/password/request_new', methods: 'POST')]
    public function requestNewPassword(Request $request, TemplatedEmailService $email, UserRepository $userRepo): Response
    {
        $requestNewPassword = new RequestNewPassword();
        $this->handleJSONForm($request, $requestNewPassword, RequestNewPasswordType::class);

        $user = $userRepo->findOneBy(['email' => $requestNewPassword->email]);
        if (!$user) {
            return $this->json('User not found.', Response::HTTP_OK);
        }

        $tokenFactory = new TokenFactory($this->em);
        $token = $tokenFactory->save($user);

        $this->em->persist($token);
        $this->em->flush();

        $email->sendPasswordRequest($token, $requestNewPassword->email);

        return $this->json('Email successfully sent. Check your email inbox.', Response::HTTP_CREATED);
    }

    #[Route('/password/make_new_authenticated', methods: 'POST')]
    public function makeNewPasswordAuthenticated(
        Request                     $request,
        #[CurrentUser] User         $user,
        UserPasswordHasherInterface $passwordHasher): Response
    {
        $newPasswordAuthenticated = new NewPasswordAuthenticated();
        $this->handleJSONForm($request, $newPasswordAuthenticated, NewPasswordAuthenticatedType::class);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $newPasswordAuthenticated->getNewPassword()
        );

        $user->setPassword($hashedPassword);
        $this->em->flush();

        return $this->json('Password changed.', Response::HTTP_CREATED);
    }
}
