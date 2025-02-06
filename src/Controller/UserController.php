<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wallet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/api/user', name: 'app_user')]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/create_user', name: 'create_user')]
    public function createUser(Request $request, EntityManagerInterface $entityManager)
    {
        // Fetch the data from the request
        $username = $request->get('name');
        $email = $request->get('mail');

        // User
        $user = new User();
        $user->setName($username);
        $user->setMail($email);

        // Wallet initialisation
        $wallet = new Wallet();

        // Wallet persist
        $entityManager->persist($wallet);

        $user->setWallet($wallet);

        // User persist
        $entityManager->persist($user);

        // flushing the data
        $entityManager->flush();

        return $this->json([
            'status' => 'User and Wallet created successfully',
            'user_id' => $user->getId(),
        ]);
    }
}
