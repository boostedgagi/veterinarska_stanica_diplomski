<?php
namespace App\MessageHandler;

use App\Entity\User;
use App\Message\Message;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Repository\UserRepository;

#[AsMessageHandler]
class MessageHandler
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function __invoke(Message $message): void
    {
//        $sender = $this->getUser($message->getSender());

        dump($message);
    }

    private function getUser(string $userId):User
    {
        return $this->userRepo->find($userId);
    }



}