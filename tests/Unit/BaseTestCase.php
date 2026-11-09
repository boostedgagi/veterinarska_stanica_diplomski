<?php

namespace App\Tests\Unit;

use App\Entity\Examination;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BaseTestCase extends KernelTestCase
{
    // Schema is (re)built once per run from the mapping; DAMA rolls back every test.
    use ResetDatabase;
    use Factories;

    protected ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function makeMockUser(?string $email = null,?User $vet = null): User
    {
        $user = new User()
            ->setEmail($email ?: 'marko.patarcic27@gmail.com')
            ->setFirstName('Marko')
            ->setLastName('Patarcic')
            ->setPassword('$2y$13$VOvCoCQGCdorKogJAwAkgeidOOLXcPwETGQuc/rlLse6.Vm7fs5AK')
            ->setTypeOfUser(User::TYPE_USER)
            ->setAllowed(1)
            ->setPhone('0643387626')
            ->setVerified(true) // user.verified is NOT NULL
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
        $mockVet = new User()
            ->setFirstName('Marko')
            ->setLastName('Milosevic')
            ->setTypeOfUser(2)
            ->setEmail('vet@vetShop.com')
            ->setPassword('password')
            ->setVet(null)
            ->setAllowed(true)
            ->setPhone('0631669825')
            ->setVerified(true) // user.verified is NOT NULL
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
        $examination = new Examination()
            ->setName('mockExamination')
            ->setPrice(1000)
            ->setDuration(60);

        $this->em->persist($examination);
        $this->em->flush();

        return $examination;
    }
}