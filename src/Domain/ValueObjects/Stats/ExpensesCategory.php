<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\ValueObjects\Stats;

use Budgetcontrol\Stats\Trait\ObjectUtils;
use Budgetcontrol\Stats\Trait\Serializer;

class ExpensesCategory {

    use Serializer, ObjectUtils;

    public readonly float $total;
    public readonly string $categorySlug;
    public readonly int $categoryId;
    public readonly string $categoryName;
    
    public function __construct(float $total, string $categorySlug, int $categoryId, string $categoryName)
    {
        $this->total = $total;
        $this->categorySlug = $categorySlug;
        $this->categoryId = $categoryId;
        $this->categoryName = $categoryName;
    }

}