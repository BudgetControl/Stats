<?php
namespace Budgetcontrol\Stats\Domain\Entity\TableChart;

use Budgetcontrol\Stats\Domain\Entity\TableChart\TableRowChart;
use Budgetcontrol\Stats\Trait\Serializer;

final class TableChart
{
    use Serializer;
    
    public string $type = 'Table';
    private array $rows = [];

    public function addRows(TableRowChart $row)
    {
        $this->rows[] = $row;
    }

    public function getRows()
    {
        return $this->rows;
    }

    private function hash(): string
    {       
        $hash = '';
        foreach($this->rows as $row) {
            $hash .= "{".$row->getAmount().$row->getLabel().$row->getPrevAmount().$row->getBounceRate()."}";
        }
        return md5("BarChart:$hash");
    }

    public function isEqualsTo(TableChart $chart): bool
    {
        return $this->hash() === $chart->hash();
    }

   
}