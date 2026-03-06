<?php

namespace Tests\Feature;

use Budgetcontrol\Stats\Domain\Entity\ApplePie\ApplePieChart;
use Budgetcontrol\Stats\Domain\Entity\ApplePie\ApplePieChartField;
use PHPUnit\Framework\TestCase;

class ApplePieChartTest extends TestCase
{
    public function test_empty_chart_has_no_fields(): void
    {
        $chart = new ApplePieChart();

        $this->assertEmpty($chart->getFields());
    }

    public function test_add_field_stores_field(): void
    {
        $chart = new ApplePieChart();
        $field = new ApplePieChartField(100.0, 'food', 1);
        $chart->addField($field);

        $this->assertCount(1, $chart->getFields());
        $this->assertSame($field, $chart->getFields()[0]);
    }

    public function test_multiple_fields_are_stored_in_order(): void
    {
        $chart = new ApplePieChart();
        $field1 = new ApplePieChartField(100.0, 'food', 1);
        $field2 = new ApplePieChartField(200.0, 'transport', 2);
        $chart->addField($field1);
        $chart->addField($field2);

        $fields = $chart->getFields();
        $this->assertCount(2, $fields);
        $this->assertSame($field1, $fields[0]);
        $this->assertSame($field2, $fields[1]);
    }

    public function test_to_array_returns_type_apple(): void
    {
        $chart = new ApplePieChart();
        $array = $chart->toArray();

        $this->assertArrayHasKey('type', $array);
        $this->assertEquals('Apple', $array['type']);
    }

    public function test_to_array_returns_field_key(): void
    {
        $chart = new ApplePieChart();
        $array = $chart->toArray();

        $this->assertArrayHasKey('field', $array);
        $this->assertIsArray($array['field']);
    }

    public function test_to_array_with_field_includes_value_label_color(): void
    {
        $chart = new ApplePieChart();
        $chart->addField(new ApplePieChartField(150.0, 'groceries', 3));
        $array = $chart->toArray();

        $this->assertNotEmpty($array['field']);
        $field = (array) $array['field'][0];
        $this->assertArrayHasKey('value', $field);
        $this->assertArrayHasKey('label', $field);
        $this->assertArrayHasKey('color', $field);
        $this->assertEquals(150.0, $field['value']);
        $this->assertEquals('groceries', $field['label']);
    }

    public function test_to_array_empty_chart_has_empty_fields(): void
    {
        $chart = new ApplePieChart();
        $array = $chart->toArray();

        $this->assertEmpty($array['field']);
    }

    public function test_is_equals_to_empty_charts(): void
    {
        $chart1 = new ApplePieChart();
        $chart2 = new ApplePieChart();

        $this->assertTrue($chart1->isEqualsTo($chart2));
    }

    public function test_is_not_equals_to_chart_with_different_fields(): void
    {
        $chart1 = new ApplePieChart();
        $chart1->addField(new ApplePieChartField(100.0, 'food', 1));

        $chart2 = new ApplePieChart();

        $this->assertFalse($chart1->isEqualsTo($chart2));
    }

    public function test_is_equals_to_charts_with_same_field_instance(): void
    {
        $field = new ApplePieChartField(100.0, 'food', 1);

        $chart1 = new ApplePieChart();
        $chart1->addField($field);

        $chart2 = new ApplePieChart();
        $chart2->addField($field);

        $this->assertTrue($chart1->isEqualsTo($chart2));
    }
}
