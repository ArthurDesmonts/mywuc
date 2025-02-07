<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Wallet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Enum\TransactionType;

final class WalletController extends AbstractController
{
    #[Route('/api/wallet/{id}', name: 'app_wallet')]
    public function getWalletById(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $wallet = $entityManager->getRepository(Wallet::class)->find($id);

        if (!$wallet) {
            return new JsonResponse(['error' => 'Wallet not found'], 404);
        }

        $transactions = $wallet->getTransactions();

        $arrayOfTransactions = [];

        foreach ($transactions as $transaction) {
            $arrayOfTransactions[] = [
                'id' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
                'type' => $transaction->getType(),
            ];
        }

        $jsonResponse = [
            'id' => $wallet->getId(),
            'sold' => $wallet->getSold(),
            'transactions' => $arrayOfTransactions,
        ];

        return new JsonResponse($jsonResponse, 200);
    }

    #[Route('api/wallet/{id}/addTransaction', name: 'addTransaction', methods: 'POST')]
    public function addTransaction(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($response = $this->requestAddTransactionValidation($data)) {
            return $response;
        }

        $wallet = $entityManager->getRepository(Wallet::class)->find($id);

        if (!$wallet) {
            return new JsonResponse(['error' => 'Wallet not found'], 404);
        }

        // Transaction creation
        $transaction = new Transaction();
        $transaction->setAmount($data['amount']);
        $transaction->setWallet($wallet);

        // Set Wallet amount
        $wallet->setSold($wallet->getSold() + $data['amount']);

        if ($data['amount'] >= 0) {
            $transaction->setType(TransactionType::DEPOSIT);
        } else {
            $transaction->setType(TransactionType::WITHDRAWAL);
        }

        // Data Saving
        $entityManager->persist($transaction);
        $entityManager->persist($wallet);
        $entityManager->flush();

        return new JsonResponse([
            'status' => 'Transaction added successfully',
            'transaction_id' => $transaction->getId(),
            'wallet_sold' => $wallet->getSold(),
        ], 201);
    }

    private function requestAddTransactionValidation(array $data): ?JsonResponse
    {
        if (!isset($data['amount'])) {
            return new JsonResponse(['error' => 'Name is required'], 400);
        }

        return null;
    }
}