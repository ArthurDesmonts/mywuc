<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wallet;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    // TODO : For test phase Must return the user list
    // FIXME: Remove this route
    #[Route('/api/user', name: 'app_user')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        $arrayOfUsers = [];

        foreach ($users as $user) {
            $arrayOfUsers[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'firstName' => $user->getFirstName(),
                'mail' => $user->getMail(),
                'phone' => $user->getPhone(),
                'wallet' => [
                    'id' => $user->getWallet()->getId(),
                    'sold' => $user->getWallet()->getSold(),
                ],
                'password' => $user->getPassword(),
                'Transactions' => $user->getWallet()->getTransactionsToArray(),
            ];
        }

        return new JsonResponse($arrayOfUsers, 200);
    }

    // Crud : CREATE route
    #[Route('/api/user/create', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Data from the request
        $data = json_decode($request->getContent(), true);

        if ($response = $this->requestDataValidation($data)) {
            return $response;
        }

        if (!$this->isAtomicPhoneNumber($data['phone'], $entityManager)) {
            return new JsonResponse(['error' => 'Phone already associated to an account'], 400);
        }

        if (!$this->isAtomicMailAdress($data['mail'], $entityManager)) {
            return new JsonResponse(['error' => 'Mail already associated to an account'], 400);
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

    // cRud : Read route
    #[Route('/api/user/get/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserFromRepository(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'firstName' => $user->getFirstName(),
            'mail' => $user->getMail(),
            'password' => $user->getPassword(),
            'phone' => $user->getPhone(),
            'wallet' => [
                'id' => $user->getWallet()->getId(),
                'sold' => $user->getWallet()->getSold(),
            ],
            'Transactions' => $user->getWallet()->getTransactionsToArray(),
        ];

        return new JsonResponse($data, 200);
    }

    // crUd : UPDATE route
    #[Route('api/user/update/{id}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $dataRequest = json_decode($request->getContent(), true);

        if (empty($dataRequest)) {
            return new JsonResponse(['error' => 'No field specified for update.'], 400);
        }

        if (isset($dataRequest['name'])) {
            $user->setName($dataRequest['name']);
        }

        if (isset($dataRequest['firstName'])) {
            $user->setFirstName($dataRequest['firstName']);
        }

        if (isset($dataRequest['firstname'])) {
            $user->setName($dataRequest['name']);
        }

        if (isset($dataRequest['mail'])) {
            if ($this->isAtomicMailAdress($dataRequest['mail'], $entityManager)) {
                $user->setMail($dataRequest['mail']);
            } else {
                return new JsonResponse(['error' => 'Mail already associated to an account'], 400);
            }
        }

        if (isset($dataRequest['phone'])) {
            if ($this->isAtomicPhoneNumber($dataRequest['phone'], $entityManager)) {
                $user->setPhone($dataRequest['phone']);
            } else {
                return new JsonResponse(['error' => 'Phone already associated to an account'], 400);
            }
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'firstName' => $user->getFirstName(),
            'mail' => $user->getMail(),
            'password' => $user->getPassword(),
            'phone' => $user->getPhone(),
        ];

        return new JsonResponse($data, 200);
    }

    // cruD : DELETE route
    #[Route('api/user/delete/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'user does not exist'], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User as been correctly removed from DataBase'], 200);
    }

    #[Route('api/user/login', name: 'login_user', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['mail']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Mail and password are required'], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['mail' => $data['mail']]);

        if (!$user) {
            return new JsonResponse(['error' => 'Mail not found'], 404);
        }

        if (!$passwordEncoder->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid password'], 400);
        }

        // Générer le token JWT
        $token = $JWTManager->create($user);

        $responseData = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'firstName' => $user->getFirstName(),
            'mail' => $user->getMail(),
            'phone' => $user->getPhone(),
            'wallet' => [
                'id' => $user->getWallet()->getId(),
                'sold' => $user->getWallet()->getSold(),
            ],
            'Transactions' => $user->getWallet()->getTransactionsToArray(),
            'token' => $token, // Ajouter le token à la réponse
        ];

        return new JsonResponse($responseData);
    }
    

    // Validate the data sent in the request
    // Check only the presence of the fields
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

    private function isAtomicPhoneNumber(string $phone, EntityManagerInterface $entityManager): bool
    {
        $similarPhoneInDB = $entityManager->getRepository(User::class)->findOneBy(['phone' => $phone]);
        if ($similarPhoneInDB) {
            return false;
        }

        return true;
    }

    private function isAtomicMailAdress(string $mail, EntityManagerInterface $entityManager): bool
    {
        $similarMailInDb = $entityManager->getRepository(User::class)->findOneBy(['mail' => $mail]);
        if ($similarMailInDb) {
            return false;
        }

        return true;
    }
}
