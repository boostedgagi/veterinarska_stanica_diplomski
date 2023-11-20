<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCreateUser():void
    {
        $user = (new User())
            ->setEmail('user.korisnikovic.' . '@4zida.rs')
            ->setFirstName('User')
            ->setLastName('Korisnikovic')
            ->setTypeOfUser(User::TYPE_USER)
            ->setAllowed(1)
            ->setPhone('0643387626')
            ->setVet(
                (new User())
                    ->setFirstName('Zeljko')
                    ->setLastName('Mamula')
                    ->setTypeOfUser(USER::TYPE_VET)
            );

        self::assertEquals('User',$user->getFirstName());
        self::assertEquals('Korisnikovic',$user->getLastName());
        self::assertSame(3,$user->getTypeOfUser());
        self::assertEquals(true,$user->isAllowed());
//        dump($user->getVet());
    }

    public function testCreateVet():void
    {
        $user = (new User())
            ->setEmail('user.korisnikovic.' . '@4zida.rs')
            ->setFirstName('User')
            ->setLastName('Korisnikovic')
            ->setTypeOfUser(User::TYPE_USER)
            ->setAllowed(1)
            ->setPhone('0643387626');

        self::assertEquals('User',$user->getFirstName());
        self::assertEquals('Korisnikovic',$user->getLastName());
        self::assertSame(3,$user->getTypeOfUser());
        self::assertEquals(true,$user->isAllowed());
    }
}