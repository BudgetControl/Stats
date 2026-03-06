<?php

namespace Budgetcontrol\Stats\Controller;

use Budgetcontrol\Stats\Domain\Repository\Interfaces\StatsRepositoryInterface;

class ChartController
{
    protected StatsRepositoryInterface $repository;

    public function __construct(StatsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

}
