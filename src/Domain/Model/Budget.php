<?php
namespace Budgetcontrol\Stats\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;
    
    protected $table = 'budgets';

    protected $hidden = ['id'];
}