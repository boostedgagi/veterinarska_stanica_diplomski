<?php
namespace App\MessageHandler;
//date_default_timezone_set('Europe/Berlin');

use App\Entity\ContactMessage;
use App\Entity\User;
use App\Message\Message;
use App\Message\MessageStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Repository\UserRepository;

#[AsMessageHandler]
class MessageHandler
{
    private UserRepository $userRepo;
    private EntityManagerInterface $em;

    public const STATUS_SENT = 'sent';
    // ToDo refactor to enum type

    public function __construct(UserRepository $userRepo,EntityManagerInterface $em)
    {
        $this->userRepo = $userRepo;
        $this->em = $em;
    }

    public function __invoke(Message $message): void
    {
        $contactMessage = (new ContactMessage())
            ->setSender($this->getUser($message->getSender()))
            ->setReceiver($this->getUser($message->getReceiver()))
            ->setContent($message->getContent())
            ->setStatus(self::STATUS_SENT);

        $this->em->persist($contactMessage);
        $this->em->flush();
    }

    private function getUser(string $userId):User
    {
        return $this->userRepo->find($userId);
    }
}