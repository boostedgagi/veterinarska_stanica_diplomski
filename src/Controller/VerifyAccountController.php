<?php

namespace App\Controller;

use App\Exception\InvalidTokenException;
use App\Model\VerifyAccount;
use App\RateLimit\RateLimited;
use App\Service\AccountTokenService;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class VerifyAccountController extends AbstractController
{
    /** Link from the welcome mail. */
    #[Security(name: null)] // public route, see security.yaml
    #[RateLimited('token_check')]
    #[Route('/verify_account', methods: 'GET')]
    public function verifyAccount(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] VerifyAccount $query,
        AccountTokenService $accounts,
    ): Response {
        try {
            $verified = $accounts->verifyAccount($query);
        } catch (InvalidTokenException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        }

        // an unknown token was used already: the account is verified
        return $this->json($verified ? 'Account verified.' : 'Account already verified', Response::HTTP_OK);
    }
}
