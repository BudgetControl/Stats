<?php
declare(strict_types=1);

namespace Budgetcontrol\Wallet\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static add(string $leftOperand, string $rightOperand, int $scale = null): string
 * @method static sub(string $leftOperand, string $rightOperand, int $scale = null): string
 * @method static mul(string $leftOperand, string $rightOperand, int $scale = null): string
 * @method static div(string $leftOperand, string $rightOperand, int $scale = null): string
 * @method static pow(string $leftOperand, string $rightOperand, int $scale = null): string
 * @method static powmod(string $leftOperand, string $rightOperand, string $modulus, int $scale = null): string
 * @method static sqrt(string $operand, int $scale = null): string
 * @method static root(string $operand, string $nth, int $scale = null): string
 * 
 * @see \Webit\Wrapper\BcMath\BcMathNumber
 */

final class BcMath extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bc-math';
    }
}