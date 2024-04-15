<?php
namespace Budgetcontrol\Stats\Domain\Entity\ApplePie;


final class ApplePieChartField
{
    private float $value;
    private string $label;
    private string $color;
    private float $labelId;

    public function __construct(float $value, string $label, float $labelId)
    {
        $this->value = $value;
        $this->label = $label;
        $this->labelId = $labelId;
        $this->color = $this->color();
    }

    private function color()
    {
        $hex = '';
        for ($i = 0; $i < 6; $i++) {
            $hex .= dechex(mt_rand(0, 15));
        }
        return '#'.$hex;
    }

    /**
     * Get the value of value
     */ 
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of label
     */ 
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get the value of color
     */ 
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Get the value of labelId
     *
     * @return int
     */
    public function getLabelId(): int
    {
        return $this->labelId;
    }
}