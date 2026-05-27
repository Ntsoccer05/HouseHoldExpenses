<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SplitGroup extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'label'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(SplitGroupSetting::class);
    }

    public function categoryOverrides(): HasMany
    {
        return $this->hasMany(SplitGroupCategoryOverride::class);
    }
}
