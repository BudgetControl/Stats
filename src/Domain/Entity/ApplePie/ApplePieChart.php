<?php
namespace Budgetcontrol\Stats\Domain\Entity\BarChart;

use Budgetcontrol\Workspace\Service\Traits\Serializer;

final class ApplePieChart
{
    use Serializer;
    
    public string $type = 'Apple';
    private array $bar = [];

    public function addBar(ApplePieChartField $bar)
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

    public function isEqualsTo(ApplePieChart $chart): bool
    {
        return $this->hash() === $chart->hash();
    }

   
}