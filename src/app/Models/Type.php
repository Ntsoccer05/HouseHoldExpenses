<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Type extends Model
{
    use HasFactory;

    public function IncomeCategories():HasMany
    {
        return $this->hasMany(IncomeCategory::class);
    }

    public function ExpenseCategories():HasMany
    {
        return $this->hasMany(ExpenceCategory::class);
    }

    public function FixedCategories():HasMany
    {
        return $this->hasMany(FixedCategory::class);
    }
}