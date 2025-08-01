<?php

namespace Budgetcontrol\Stats\Domain\ValueObjects\Stats;

use InvalidArgumentException;
use Budgetcontrol\Library\Model\EntryInterface;

final class TotalPlannedEntries implements StatsInterface
{
    private float $value;
    private array $entries;

    public function __construct() {
        $this->value = 0;
        $this->entries = [];
    }

    public function value(): float {
        return $this->value;
    }

    public function entries(): array {
        return $this->entries;
    }
    
    public function sum(EntryInterface|float $value): self {
        $this->validateExpenseEntry($value);
        $this->entries[] = $value;
        $this->value += $value instanceof EntryInterface ? $value->amount : $value;
        return $this;
    }

    public function substract(EntryInterface|float $value): self {
        $this->validateExpenseEntry($value);
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
    private function validateExpenseEntry(EntryInterface|float $entry): void {
        if (is_float($entry) || !$entry instanceof \Budgetcontrol\Library\Model\Expense) {
            throw new InvalidArgumentException(
                sprintf(
                    'Only expense entries are allowed in TotalExpenses. Entry type "%s" is not allowed.',
                    $entry instanceof EntryInterface ? $entry->type : 'float'
                )
            );
        }
    }
}