<?php
namespace App\MessageHandler;
//date_default_timezone_set('Europe/Berlin');

use App\Entity\ContactMessage;
use App\Entity\User;
use App\Enum\ContactMessageStatus;
use App\Message\Message;
use App\Message\MessageStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Repository\UserRepository;

#[AsMessageHandler]
class MessageHandler
{

    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(Message $message): void
    {
        $contactMessage = new ContactMessage(
            $this->getUser($message->getSender()),
            $this->getUser($message->getReceiver()),
            $message->getContent(),
            ContactMessageStatus::SENT->value,
            $message->getChatId()
            );

        $this->em->persist($contactMessage);
        $this->em->flush();
    }

    private function getUser(string $userId):User
    {
        return $this->userRepo->find($userId);
    }
}