<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\ValueObjects\Stats;

use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use InvalidArgumentException;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Model\EntryInterface;
use Illuminate\Database\Eloquent\Collection;

final class TotalInstallementValue implements StatsInterface
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
                $this->value += $this->installmentValue($wallet);
                $this->entries[] = $wallet;
            }
        }
    }

    public function value(): float {
        return $this->value * -1; // Assuming the value is negative for expenses
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
            if (!$item instanceof \Budgetcontrol\Library\Model\Wallet && !in_array($item->type, $validWalletTpes)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Only wallet entries are allowed in TotalWallet. Entry type "%s" is not allowed.',
                        $item instanceof Wallet ? $item->type : 'float'
                    )
                );
            }
        }
    }

    /**
     * Calculates the total installment value for the given wallet.
     *
     * @param Wallet $wallet The wallet for which to calculate the installment value.
     * @return float The calculated total installment value.
     */
    private function installmentValue(Wallet $wallet): float {

        $balance = $wallet->balance * -1; // Assuming balance is negative for expenses

        if($wallet->installement_value <= 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'Installement value must be positive. Current value: %f',
                    $wallet->installement_value
                )
            );
        }

        if($wallet->installement_value < $balance && $wallet->installement === true) {
            return $wallet->installement_value;
        }

        return $balance;
    }
}