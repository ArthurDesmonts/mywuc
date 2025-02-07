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

    public function getTransactionsToArray(): array
    {
        $transactions = $this->transactions;
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
}
