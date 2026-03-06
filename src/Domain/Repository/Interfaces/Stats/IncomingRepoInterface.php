<?php
namespace Budgetcontrol\Stats\Domain\Repository\Interfaces\Stats;

interface IncomingRepoInterface {
    

    public function incomingByCategory(?int $categoryId = null): array;

    public function incomingByLabels();
}