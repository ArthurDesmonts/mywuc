<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\WalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
#[ApiResource]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $sold = 0.0;

    #[ORM\OneToMany(mappedBy: 'wallet', targetEntity: Transaction::class, orphanRemoval: true)]
    private Collection $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSold(): ?float
    {
        return $this->sold;
    }

    public function setSold(float $sold): static
    {
        $this->sold = $sold;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function getTransactionsById(int $idTransaction): ?Transaction
    {
        $transaction = $this->transactions->filter(function ($transaction) use ($idTransaction) {
            return $transaction->getId() == $idTransaction;
        })->first();

        return $transaction;
    }

    public function getTransactionsToArray(): array
    {
        $transactions = $this->transactions->toArray();
        usort($transactions, function ($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });

        $arrayOfTransactions = [];

        foreach ($transactions as $transaction) {
            $arrayOfTransactions[] = [
                'id' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
                'type' => $transaction->getType(),
            ];
        }

        return $arrayOfTransactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setWallet($this);
        }

        return $this;
    }

    public function removeTransaction(int $idTransaction): static
    {
        try {
            $transaction = $this->getTransactionsById($idTransaction);
            $newAmount = $this->getSold() - $transaction->getAmount();
            if (null != $transaction) {
                $this->transactions->removeElement($transaction);
                $this->setSold($newAmount);
            }
        } catch (\Exception $e) {
            throw new \Exception('Transaction not found or not associated with this wallet');
        }

        return $this;
    }
}
