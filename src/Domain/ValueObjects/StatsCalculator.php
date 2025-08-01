<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\ValueObjects;

use Budgetcontrol\Stats\Domain\ValueObjects\Stats\StatsInterface;
use Webit\Wrapper\BcMath\BcMathNumber;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\TotalExpenses;

final class StatsCalculator {

    private array $entries;

    public function __construct(
        array $entriesCollection = [],
    ) {
        $this->entries = $entriesCollection;
    }

    public static function create(
        array $entriesCollection = [],
    ): self {
        if ($entriesCollection === null) {
            $entriesCollection = [new TotalExpenses()];
        }
        return new self($entriesCollection);
    }

    public function add(StatsInterface $stats): void
    {
        $this->entries[] = $stats;
    }

    public function get(): BcMathNumber
    {
        $total = new BcMathNumber();
        foreach ($this->entries as $entry) {
            $total = $total->add($entry->value());
        }
        return $total;
    }

}