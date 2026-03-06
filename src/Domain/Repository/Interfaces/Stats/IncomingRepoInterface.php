<?php
namespace Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats;

interface IncomingRepoInterface {
    
    public function setup(string $wsId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): self;

    public function incomingByCategory(?int $categoryId = null): array;

    public function incomingByLabels();
}