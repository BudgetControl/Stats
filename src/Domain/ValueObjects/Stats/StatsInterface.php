<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\ValueObjects\Stats;

use Budgetcontrol\Library\Model\EntryInterface;

interface StatsInterface {

    public function value(): float;

    public function entries(): array;

    public function sum(EntryInterface|float $value): self;

    public function substract(EntryInterface|float $value): self;

}