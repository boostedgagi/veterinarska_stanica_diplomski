<?php
namespace App\MessageHandler;
//date_default_timezone_set('Europe/Berlin');

use App\Entity\ContactMessage;
use App\Entity\User;
use App\Enum\ContactMessageStatus;
use App\Message\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
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
        $sender = $this->getUser($message->getSender());
        $receiver = $this->getUser($message->getReceiver());

        $contactMessage = new ContactMessage(
            $sender,
            $receiver,
            $message->getContent(),
            ContactMessageStatus::SENT->value,
            $message->getChatId()
            );

        $this->em->persist($contactMessage);
        $this->em->flush();

//        $this->connection->send($contactMessage->getContent());
    }

    private function getUser(string $userId):User
    {
        // an unknown user will never appear: don't retry the message
        return $this->userRepo->find($userId)
            ?? throw new UnrecoverableMessageHandlingException(sprintf('User %s does not exist.', $userId));
    }
}