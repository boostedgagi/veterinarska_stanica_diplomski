<?php

namespace App\Controller;

use App\RateLimit\RateLimited;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\Routing\Attribute\Route;
use App\Model\VerifyAccount;
use App\Repository\TokenEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAccountController extends AbstractController
{

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Security(name: null)] // public route, see security.yaml
    #[RateLimited('token_check')]
    #[Route('/verify_account', methods: 'GET')]
    public function verifyAccount(Request $request, TokenEntityRepository $verifyRepo): Response
    {
        $queryParams = new VerifyAccount($request->query->all());
        if (!$queryParams->getToken() || !$queryParams->getUserId()) {
            return $this->json("Missing token or user_id.", Response::HTTP_BAD_REQUEST);
        }

        $savedToken = $verifyRepo->findOneByValue($queryParams->getToken());
        if(!$savedToken){
            return $this->json("Account already verified",Response::HTTP_OK);
        }

        // the token only verifies the account it was issued for
        $user = $savedToken->getUser();
        if ($user && $user->getId() === $queryParams->getUserId() && !$savedToken->isExpired())
        {
            $user->setVerified(true);

            $this->em->remove($savedToken);
            $this->em->flush();

            return $this->json("Account verified.", Response::HTTP_OK);
        }

        return $this->json("Account not verified.", Response::HTTP_FORBIDDEN);
    }
}
