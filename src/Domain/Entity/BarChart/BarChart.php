<?php
namespace Budgetcontrol\Stats\Domain\Entity\BarChart;

use Budgetcontrol\Stats\Domain\Entity\BarChart\BarChartBar;
use Budgetcontrol\Workspace\Service\Traits\Serializer;

final class BarChart
{
    use Serializer;
    
    public string $type = 'Bar';
    private array $bar = [];

    public function addBar(BarChartBar $bar)
    {
        $this->bar[] = $bar;
    }

    public function getBars()
    {
        return $this->bar;
    }

    private function hash(): string
    {       
        $hash = '';
        foreach($this->bar as $bar) {
            $hash .= "{".$bar->getLabel().$bar->getColor().$bar->getValue()."}";
        }
        return md5("BarChart:$hash");
    }

    public function isEqualsTo(BarChart $chart): bool
    {
        return $this->hash() === $chart->hash();
    }

   
}