<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitGroupCategoryOverride extends Model
{
    use HasFactory;

    protected $fillable = ['split_group_id', 'category_id', 'type_id', 'other_ratio'];

    public function splitGroup(): BelongsTo
    {
        return $this->belongsTo(SplitGroup::class);
    }
}
