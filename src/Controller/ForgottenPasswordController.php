<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\InvalidTokenException;
use App\Model\NewPasswordAuthenticated;
use App\Model\NewPasswordEnvelope;
use App\Model\RequestNewPassword;
use App\RateLimit\RateLimited;
use App\Repository\UserRepository;
use App\Service\AccountTokenService;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ForgottenPasswordController extends AbstractController
{
    public function __construct(private readonly AccountTokenService $accounts)
    {
    }

    /** Sets a new password with the token from the reset mail. */
    #[Security(name: null)] // public route, see security.yaml
    #[RateLimited('token_check')]
    #[Route('/password/make_new', methods: 'POST')]
    public function renewForgottenPassword(#[MapRequestPayload] NewPasswordEnvelope $input): Response
    {
        try {
            $this->accounts->resetPassword($input);
        } catch (InvalidTokenException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $this->json('Password changed.', Response::HTTP_OK);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Security(name: null)] // public route, see security.yaml
    #[RateLimited('password_request')]
    #[Route('/password/request_new', methods: 'POST')]
    public function requestNewPassword(#[MapRequestPayload] RequestNewPassword $input): Response
    {
        // same answer whether the account exists or not, so the endpoint can't be used to find accounts
        $this->accounts->requestPasswordReset($input->email);

        return $this->json('If an account with this e-mail exists, a reset link has been sent to it.', Response::HTTP_CREATED);
    }

    #[Route('/password/make_new_authenticated', methods: 'POST')]
    public function makeNewPasswordAuthenticated(
        #[MapRequestPayload] NewPasswordAuthenticated $input,
        #[CurrentUser] User $currentUser,
        UserRepository $users,
    ): Response {
        // the managed entity, not the security token's copy (that one may be detached)
        $user = $users->find($currentUser->getId()) ?? throw $this->createNotFoundException('User not found.');
        $this->accounts->changePassword($user, $input->newPassword);

        return $this->json('Password changed.', Response::HTTP_CREATED);
    }
}
