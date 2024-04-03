<?php

namespace App\Tests\Unit;

use App\Entity\Examination;
use App\Entity\HealthRecord;
use App\Entity\User;
use App\Tests\DatabasePrimer;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Config\ZenstruckFoundry\DatabaseResetterConfig;

class BaseTestCase extends KernelTestCase
{
    protected ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);

        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function makeMockUser(string $email = null,User $vet = null): User
    {
        $user = (new User())
            ->setEmail($email ?: 'marko.patarcic27@gmail.com')
            ->setFirstName('Marko')
            ->setLastName('Patarcic')
            ->setPassword('$2y$13$VOvCoCQGCdorKogJAwAkgeidOOLXcPwETGQuc/rlLse6.Vm7fs5AK')
            ->setTypeOfUser(User::TYPE_USER)
            ->setAllowed(1)
            ->setPhone('0643387626')
            ->setVet($vet);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @return User
     */
    protected function makeMockVet(): User
    {
        $mockVet = (new User())
            ->setFirstName('Marko')
            ->setLastName('Milosevic')
            ->setTypeOfUser(2)
            ->setEmail('vet@vetShop.com')
            ->setPassword('password')
            ->setPlainPassword('password')
            ->setVet(null)
            ->setAllowed(true)
            ->setPhone('0631669825')
            ->setCreatedAt(new DateTimeImmutable());

        $this->em->persist($mockVet);
        $this->em->flush();

        return $mockVet;
    }

    /**
     * @return Examination
     */
    protected function makeMockExamination(): Examination
    {
        $examination = (new Examination())
            ->setName('mockExamination')
            ->setPrice(1000)
            ->setDuration(60);

        $this->em->persist($examination);
        $this->em->flush();

        return $examination;
    }
}