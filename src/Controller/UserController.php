<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/users',methods: 'POST')]
    public function register(Request $request,UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $plainTextPassword = json_decode($request->getContent(),false);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plainTextPassword->password
        );
        $this->handleJSONForm($request,$user,UserType::class);

        $user->setPassword($hashedPassword);
        $user->setAllowed(false);
        $user->setTypeOfUser(3);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user,Response::HTTP_CREATED,[],['groups'=>'user_created']);
    }



}
