<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitGroupSetting extends Model
{
    use HasFactory;

    protected $fillable = ['split_group_id', 'income_other_ratio', 'income_other_offset', 'expense_other_ratio', 'expense_other_offset'];

    public function splitGroup(): BelongsTo
    {
        return $this->belongsTo(SplitGroup::class);
    }
}
