<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Model\Workspace;
use Carbon\Carbon;
use Symfony\Component\Translation\Exception\NotFoundResourceException;


abstract class BaseRepository
{
    protected int $wsId;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function setup(string $wsId, Carbon $startDate, Carbon $endDate): self
    {
        $wsid = Workspace::where('uuid', $wsId)->first()->id;
        $wsid = 2;
        if (is_null($wsid)) {
            throw new NotFoundResourceException('Workspace not found', 404);
        }

        $this->wsId = $wsid;
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $this;
    }
}