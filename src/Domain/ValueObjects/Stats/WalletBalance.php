<?php

namespace Budgetcontrol\Stats\Domain\ValueObjects\Stats;

use InvalidArgumentException;
use Budgetcontrol\Library\Model\EntryInterface;

final class WalletBalance implements StatsInterface
{
    private float $value;
    private array $entries;

    public function __construct(array|Collection $wallets = []) {
        $this->validateEntry($wallets);
        if (empty($wallets)) {
            $this->value = 0;
            $this->entries = [];
        } else {
            $this->value = 0;
            foreach ($wallets as $wallet) {
                $this->value += $wallet->balance;
                $this->entries[] = $wallet;
            }
        }
    }

    public function value(): float {
        return $this->value;
    }

    public function entries(): array {
        return $this->entries;
    }

    public function sum(EntryInterface|float $value): self {
        $this->entries[] = $value;
        $this->value += $value instanceof EntryInterface ? $value->amount : $value;
        return $this;
    }

    public function substract(EntryInterface|float $value): self {
        $this->entries[] = $value;
        $this->value -= $value instanceof EntryInterface ? $value->amount : $value;
        return $this;
    }

    /**
     * Validates that the entry is of type 'expenses'
     * 
     * @param EntryInterface $entry The entry to validate
     * @throws InvalidArgumentException If the entry is not of type 'expenses'
     */
    private function validateEntry(array|Collection $wallets): void {
        $validWalletTpes = [
            EntityWallet::creditCard->value,
            EntityWallet::creditCardRevolving->value,
        ];

        foreach ($wallets as $item) {
            if (!$item instanceof \Budgetcontrol\Library\Model\Wallet || in_array($item->type, $validWalletTpes)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Only wallet entries are allowed in TotalWallet. Entry type "%s" is not allowed.',
                        $item instanceof Wallet ? $item->type : 'float'
                    )
                );
            }
        }
    }

}