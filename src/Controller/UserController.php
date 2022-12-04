<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
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

    #[Route('/users/{id}',methods: 'PUT')]
    public function updateUser(Request $request,int $id,UserRepository $repo): Response
    {
        $user = $repo->find($id);

        $this->handleJSONForm($request,$user,UserType::class);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user,Response::HTTP_CREATED,[],['groups'=>'user_created']);
    }

    #[Route('/users/{id}',methods: 'DELETE')]
    public function deleteUser(Request $request,int $id,UserRepository $repo): Response
    {
        $user = $repo->find($id);

        $this->em->remove($user);
        $this->em->flush();

        return $this->json("",Response::HTTP_NO_CONTENT);
    }

    #[Route('/users',methods: 'GET')]
    public function showAllUsers(Request $request,UserRepository $repo): Response
    {
        $allUsers = $repo->findAll();

        return $this->json($allUsers,Response::HTTP_OK,[],['groups'=>'user_showAll']);
    }

    #[Route('/users/{id}',methods: 'GET')]
    public function showOneUser(Request $request,int $id,UserRepository $repo): Response
    {
        $user = $repo->find($id);

        return $this->json($user,Response::HTTP_OK,[],['groups'=>'user_showAll']);
    }



    //image upload method here

}
