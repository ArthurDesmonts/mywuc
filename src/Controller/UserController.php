<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wallet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    // TODO : For test phase Must return the user list
    // TODO : Remove this route
    #[Route('/api/user', name: 'app_user')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        $arrayOfUsers = [];

        foreach ($users as $user) {
            $arrayOfUsers[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'mail' => $user->getMail(),
                'phone' => $user->getPhone(),
                'wallet' => [
                    'id' => $user->getWallet()->getId(),
                    'sold' => $user->getWallet()->getSold(),
                ],
                'password' => $user->getPassword(),
            ];
        }

        return new JsonResponse($arrayOfUsers, 200);
    }

    #[Route('/api/create_user', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Data from the request
        $data = json_decode($request->getContent(), true);

        if ($response = $this->requestDataValidation($data)) {
            return $response;
        }

        // Create User
        $user = new User();
        $user->setName($data['name']);
        $user->setMail($data['mail']);
        $user->setPhone($data['phone']);

        // Hash password
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Create Wallet
        $wallet = new Wallet();
        $entityManager->persist($wallet);
        $user->setWallet($wallet);

        // Data Storage
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'status' => 'User and Wallet created successfully',
            'user_id' => $user->getId(),
            'wallet_id' => $wallet->getId(),
        ], 201);
    }

    #[Route('api/delete_user/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'user does not exist'], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User as been correctly removed from DataBase'], 201);
    }

    #[Route('/api/user/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserFromRepository(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'mail' => $user->getMail(),
            'password' => $user->getPassword(),
            'phone' => $user->getPhone(),
            'wallet' => [
                'id' => $user->getWallet()->getId(),
                'sold' => $user->getWallet()->getSold(),
            ],
            'Transactions' => $user->getWallet()->getTransactions(),
        ];

        return new JsonResponse($data, 200);
    }

    // Validate the data sent in the request
    // Check only the presence of the fields
    // TODO : CHeck data integrity
    private function requestDataValidation(array $data): ?JsonResponse
    {
        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], 400);
        }

        if (!isset($data['mail'])) {
            return new JsonResponse(['error' => 'Mail is required'], 400);
        }

        if (!isset($data['phone'])) {
            return new JsonResponse(['error' => 'Phone is required'], 400);
        }

        if (!isset($data['password'])) {
            return new JsonResponse(['error' => 'Password is required'], 400);
        }

        return null;
    }
}
