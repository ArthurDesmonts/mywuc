<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;

final class WalletController extends AbstractController
{
    // Crud : CREATE Transaction
    #[Route('api/wallet/transaction/add/{id}', name: 'addTransaction', methods: 'POST')]
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
        $transaction->setDate(\DateTime::createFromFormat('d/m/Y', $data['selectedDate']));
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

    // cRud : READ Wallet informations
    #[Route('/api/wallet/{id}', name: 'app_wallet')]
    public function getWalletById(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $wallet = $entityManager->getRepository(Wallet::class)->find($id);

        if (!$wallet) {
            return new JsonResponse(['error' => 'Wallet not found'], 404);
        }

        $arrayOfTransactions = $wallet->getTransactionsToArray();

        $jsonResponse = [
            'id' => $wallet->getId(),
            'sold' => $wallet->getSold(),
            'transactions' => $arrayOfTransactions,
        ];

        return new JsonResponse($jsonResponse, 200);
    }

    // cruD : DELETE a Transaction from a wallet
    #[Route('api/wallet/transaction/remove/{idWallet}', name: 'remove_transaction', methods: 'DELETE')]
    public function removeTransactionById(int $idWallet, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $wallet = $entityManager->getRepository(Wallet::class)->find($idWallet);

        if (!$wallet) {
            return new JsonResponse(['error' => 'Wallet not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['idTransaction'])) {
            return new JsonResponse(['error' => 'No transaction ID received', 404]);
        }

        $transaction = $entityManager->getRepository(Transaction::class)->find($data['idTransaction']);

        if (!$transaction) {
            return new JsonResponse(['error' => 'Transaction not found'], 404);
        }

        $wallet->removeTransaction($data['idTransaction']);

        $entityManager->persist($wallet);
        $entityManager->flush();

        return new JsonResponse(['succes' => 'The transaction as been succesfully removed from this wallet'], 201);
    }

    #[Route('api/wallet/transaction/debit/month/{idWallet}', name: 'debitPairMonth', methods: 'GET')]
    public function getDebitPairMonth(int $idWallet, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $wallet = $entityManager->getRepository(Wallet::class)->find($idWallet);

        if (!$wallet) {
            return new JsonResponse(['error' => 'No transaction ID received', 404]);
        }

        $transactions = $wallet->getTransactionsToArray();
        
        $transactionDebit = [];

        $currentYear = (int) (new \DateTime())->format('Y');

        foreach ($transactions as $transaction) {
            $transactionYear = (int) (new \DateTime($transaction['date']))->format('Y');

            if ($transactionYear === $currentYear &&  $transaction['type'] === TransactionType::WITHDRAWAL) {
                $transactionDebit[] = $transaction;
            }
        }

        $transactionPairMonth =  array_fill(0, 12, 0);

        foreach ($transactionDebit as $transaction) {
            $monthOfTransaction = (new \DateTime($transaction['date']))->format('m');
            $transactionPairMonth[ (int) $monthOfTransaction - 1] += $transaction['amount'];
        }

        $jsonResponse = [
            'transactions' => $transactionPairMonth,
        ];
        
        return new JsonResponse($jsonResponse, 200);
    }

    #[Route('api/wallet/transaction/credit/month/{idWallet}', name: 'creditPairMonth', methods: 'GET')]
    public function getCreditPairMonth(int $idWallet, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $wallet = $entityManager->getRepository(Wallet::class)->find($idWallet);

        if (!$wallet) {
            return new JsonResponse(['error' => 'No transaction ID received', 404]);
        }

        $transactions = $wallet->getTransactionsToArray();
        
        $transactionDebit = [];

        $currentYear = (int) (new \DateTime())->format('Y');

        foreach ($transactions as $transaction) {
            $transactionYear = (int) (new \DateTime($transaction['date']))->format('Y');

            if ($transactionYear === $currentYear &&  $transaction['type'] === TransactionType::DEPOSIT) {
                $transactionDebit[] = $transaction;
            }
        }

        $transactionPairMonth =  array_fill(0, 12, 0);

        foreach ($transactionDebit as $transaction) {
            $monthOfTransaction = (new \DateTime($transaction['date']))->format('m');
            $transactionPairMonth[ (int) $monthOfTransaction - 1] += $transaction['amount'];
        }

        $jsonResponse = [
            'transactions' => $transactionPairMonth,
        ];
        
        return new JsonResponse($jsonResponse, 200);
    }

    private function requestAddTransactionValidation(array $data): ?JsonResponse
    {
        if (!isset($data['amount'])) {
            return new JsonResponse(['error' => 'Amount is required'], 400);
        }

        return null;
    }
}
