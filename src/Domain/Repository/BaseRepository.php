<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Repository;

use Budgetcontrol\Library\Model\Workspace;
use Budgetcontrol\Stats\Domain\Repository\Interfaces\ElasticSearchRepositoryInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;


abstract class BaseRepository
{
    protected int $wsId;
    protected \Carbon\Carbon $startDate;
    protected \Carbon\Carbon $endDate;

    protected ElasticSearchRepositoryInterface $client; //FIXME: should be injected in the constructor a interface

    public function __construct(ElasticSearchRepositoryInterface $client)
    {
        // Initialize any common properties or dependencies here if needed
        $this->client = $client;
    }

    public function setup(string $wsId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): static
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