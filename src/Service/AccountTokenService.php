<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\InvalidTokenException;
use App\Factory\TokenFactory;
use App\Model\NewPasswordEnvelope;
use App\Model\VerifyAccount;
use App\Repository\TokenEntityRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Account verification and passwords. A mailed token only works for the account it was
 * issued for, once, and until it expires.
 */
final readonly class AccountTokenService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenEntityRepository $tokens,
        private UserRepository $users,
        private TokenFactory $tokenFactory,
        private TemplatedEmailService $mail,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    /**
     * @return bool false when the token is unknown (already used: the account is verified)
     * @throws InvalidTokenException
     */
    public function verifyAccount(VerifyAccount $input): bool
    {
        $token = $this->tokens->findOneByValue($input->token);
        if (!$token) {
            return false;
        }

        $user = $token->getUser();
        if (!$user || $user->getId() !== $input->userId || $token->isExpired()) {
            throw new InvalidTokenException('Invalid or expired token.');
        }

        $user->setVerified(true);
        $this->em->remove($token);
        $this->em->flush();

        return true;
    }

    /** Mails a reset link if the account exists; says nothing either way (no account lookup). */
    public function requestPasswordReset(string $email): void
    {
        $user = $this->users->findOneBy(['email' => $email]);
        if ($user) {
            $this->mail->sendPasswordRequest($this->tokenFactory->save($user), $email);
        }
    }

    /** @throws InvalidTokenException */
    public function resetPassword(NewPasswordEnvelope $input): void
    {
        $token = $this->tokens->findOneByValue($input->token);
        $user = $token?->getUser();
        if (!$token || !$user || strcasecmp((string) $user->getEmail(), $input->email) !== 0) {
            throw new InvalidTokenException('Token is not valid.');
        }
        if ($token->isExpired()) {
            throw new InvalidTokenException('Token has expired, request a new one.');
        }

        $this->setPassword($user, $input->password);
        $this->em->remove($token);
        $this->em->flush();
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $this->setPassword($user, $newPassword);
        $this->em->flush();
    }

    private function setPassword(User $user, string $plain): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $plain));
    }
}
