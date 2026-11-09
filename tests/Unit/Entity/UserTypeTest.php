<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/** Roles are the only stored user type; typeOfUser (1/2/3) is derived for the API. */
class UserTypeTest extends TestCase
{
    public function testTypeFollowsTheRoles(): void
    {
        $user = new User();
        self::assertSame(User::TYPE_USER, $user->getTypeOfUser());
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $user->setRoles([User::ROLE_VET]);
        self::assertSame(User::TYPE_VET, $user->getTypeOfUser());
        self::assertTrue($user->isVet());
        self::assertFalse($user->isAdmin());

        $user->setRoles([User::ROLE_ADMIN, User::ROLE_VET]);
        self::assertSame(User::TYPE_ADMIN, $user->getTypeOfUser(), 'admin wins');
        self::assertTrue($user->isVet());
    }

    public function testSettingATypeSetsItsRole(): void
    {
        $user = new User()->setRoles(['ROLE_SOMETHING']);

        $user->setTypeOfUser(User::TYPE_VET);
        self::assertSame(['ROLE_SOMETHING', User::ROLE_VET], array_values(array_diff($user->getRoles(), ['ROLE_USER'])));

        $user->setTypeOfUser(User::TYPE_ADMIN);
        self::assertTrue($user->isAdmin());
        self::assertFalse($user->isVet(), 'one type at a time');

        $user->setTypeOfUser(User::TYPE_USER);
        self::assertSame(['ROLE_SOMETHING', 'ROLE_USER'], $user->getRoles());
    }

    public function testNewVetGetsTheVetRole(): void
    {
        $vet = new User()->setAdditionalVetData();

        self::assertSame(User::TYPE_VET, $vet->getTypeOfUser());
        self::assertTrue($vet->isAllowed());
        self::assertTrue($vet->isVerified());
    }
}
