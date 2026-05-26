<?php

namespace App\Models;

use App\Models\Content;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type_id', 'category_id', 'amount',
        'content', 'fixed_expense_day', 'is_active', 'last_replicated_at', 'deactivated_at',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'last_replicated_at'  => 'datetime',
        'deactivated_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateExecutionDate(int $year, int $month): Carbon
    {
        $lastDay = Carbon::create($year, $month)->endOfMonth()->day;
        $day = min($this->fixed_expense_day, $lastDay);
        return Carbon::create($year, $month, $day);
    }

    public function isReplicatedForMonth(int $year, int $month): bool
    {
        $yearMonth = sprintf('%04d%02d', $year, $month);
        return Content::where('fixed_expense_id', $this->id)
            ->whereRaw('DATE_FORMAT(recorded_at, "%Y%m") = ?', [$yearMonth])
            ->exists();
    }

    public function replicateForMonth(int $year, int $month): ?Content
    {
        if ($this->isReplicatedForMonth($year, $month)) {
            return null;
        }
        $executionDate = $this->calculateExecutionDate($year, $month);
        $content = Content::create([
            'user_id'          => $this->user_id,
            'type_id'          => $this->type_id,
            'category_id'      => $this->category_id,
            'amount'           => $this->amount,
            'content'          => $this->content,
            'recorded_at'      => $executionDate,
            'is_fixed_expense' => true,
            'fixed_expense_id' => $this->id,
        ]);

        $this->update(['last_replicated_at' => now()]);

        return $content;
    }
}
