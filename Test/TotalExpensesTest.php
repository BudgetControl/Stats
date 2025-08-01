<?php declare(strict_types=1);

namespace Budgetcontrol\Stats\Test;

use PHPUnit\Framework\TestCase;
use Budgetcontrol\Stats\Domain\ValueObjects\Stats\TotalExpenses;
use Budgetcontrol\Library\Model\EntryInterface;
use InvalidArgumentException;

/**
 * Test class for TotalExpenses value object
 */
class TotalExpensesTest extends TestCase
{
    private TotalExpenses $totalExpenses;

    protected function setUp(): void
    {
        $this->totalExpenses = new TotalExpenses();
    }

    public function testInitialValues(): void
    {
        $this->assertEquals(0.0, $this->totalExpenses->value());
        $this->assertEmpty($this->totalExpenses->entries());
    }

    public function testSumWithValidExpenseEntry(): void
    {
        $expenseEntry = $this->createMockExpenseEntry(-100.0, 'expenses');
        
        $this->totalExpenses->sum($expenseEntry);
        
        $this->assertEquals(-100.0, $this->totalExpenses->value());
        $this->assertCount(1, $this->totalExpenses->entries());
        $this->assertSame($expenseEntry, $this->totalExpenses->entries()[0]);
    }

    public function testSubstractWithValidExpenseEntry(): void
    {
        $expenseEntry = $this->createMockExpenseEntry(-50.0, 'expenses');
        
        $this->totalExpenses->substract($expenseEntry);
        
        $this->assertEquals(50.0, $this->totalExpenses->value());
        $this->assertCount(1, $this->totalExpenses->entries());
    }

    public function testSumWithInvalidEntryType(): void
    {
        $incomeEntry = $this->createMockExpenseEntry(100.0, 'incoming');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only expense entries are allowed in TotalExpenses. Entry type "incoming" is not allowed.');
        
        $this->totalExpenses->sum($incomeEntry);
    }

    public function testSubstractWithInvalidEntryType(): void
    {
        $debitEntry = $this->createMockExpenseEntry(75.0, 'debit');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only expense entries are allowed in TotalExpenses. Entry type "debit" is not allowed.');
        
        $this->totalExpenses->substract($debitEntry);
    }

    public function testAcceptsNegativeAmountWithoutType(): void
    {
        $entryWithoutType = $this->createMockExpenseEntry(-200.0, null);
        
        // Should accept negative amounts even without explicit type
        $this->totalExpenses->sum($entryWithoutType);
        
        $this->assertEquals(-200.0, $this->totalExpenses->value());
        $this->assertCount(1, $this->totalExpenses->entries());
    }

    public function testRejectsPositiveAmountWithoutType(): void
    {
        $entryWithPositiveAmount = $this->createMockExpenseEntry(150.0, null);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only expense entries are allowed in TotalExpenses. Entry type "unknown" is not allowed.');
        
        $this->totalExpenses->sum($entryWithPositiveAmount);
    }

    public function testMultipleOperations(): void
    {
        $expense1 = $this->createMockExpenseEntry(-100.0, 'expenses');
        $expense2 = $this->createMockExpenseEntry(-50.0, 'expenses');
        
        $this->totalExpenses->sum($expense1);
        $this->totalExpenses->sum($expense2);
        $this->totalExpenses->substract($expense1);
        
        $this->assertEquals(-50.0, $this->totalExpenses->value()); // -100 + (-50) - (-100) = -50
        $this->assertCount(3, $this->totalExpenses->entries());
    }

    /**
     * Creates a mock entry for testing
     */
    private function createMockExpenseEntry(float $amount, ?string $type): EntryInterface
    {
        /** @var EntryInterface&\PHPUnit\Framework\MockObject\MockObject $entry */
        $entry = $this->createMock(EntryInterface::class);
        
        // Set public properties to simulate the real entry behavior
        $entry->amount = $amount;
        if ($type !== null) {
            $entry->type = $type;
        }
        
        return $entry;
    }
}
