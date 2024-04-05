<?php
namespace Budgetcontrol\Stats\Domain\Entity\ApplePie;

use Budgetcontrol\Stats\Trait\Serializer;

final class ApplePieChart
{
    use Serializer;
    
    public string $type = 'Apple';
    private array $field = [];

    public function addField(ApplePieChartField $field)
    {
        $this->field[] = $field;
    }

    public function getFields()
    {
        return $this->field;
    }

    private function hash(): string
    {       
        $hash = '';
        foreach($this->field as $field) {
            $hash .= "{".$field->getLabel().$field->getColor().$field->getValue()."}";
        }
        return md5("fieldChart:$hash");
    }

    public function isEqualsTo(ApplePieChart $chart): bool
    {
        return $this->hash() === $chart->hash();
    }

   
}