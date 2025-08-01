<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\ValueObjects\Stats;

use Budgetcontrol\Library\Model\EntryInterface;

final class DataValue implements StatsInterface {

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
        $this->entries[] = $value;
        $this->value += $value instanceof EntryInterface ? $value->amount : $value;
        return $this;
    }

    public function substract(EntryInterface|float $value): self {
        $this->entries[] = $value;
        $this->value -= $value instanceof EntryInterface ? $value->amount : $value;
        return $this;
    }

}