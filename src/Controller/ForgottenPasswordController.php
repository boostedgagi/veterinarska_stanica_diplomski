<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\RateLimit\RateLimited;
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
    #[RateLimited('token_check')]
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
            throw new BadRequestHttpException('Token is not valid.');
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
    #[RateLimited('password_request')]
    #[Route('/password/request_new', methods: 'POST')]
    public function requestNewPassword(Request $request, TemplatedEmailService $email, UserRepository $userRepo, TokenFactory $tokenFactory): Response
    {
        $requestNewPassword = new RequestNewPassword();
        $this->handleJSONForm($request, $requestNewPassword, RequestNewPasswordType::class);

        // same answer whether the account exists or not, so the endpoint can't be used to find accounts
        $user = $userRepo->findOneBy(['email' => $requestNewPassword->email]);
        if ($user) {
            $token = $tokenFactory->save($user);
            $email->sendPasswordRequest($token, $requestNewPassword->email);
        }

        return $this->json('If an account with this e-mail exists, a reset link has been sent to it.', Response::HTTP_CREATED);
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
