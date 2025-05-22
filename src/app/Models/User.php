<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        Log::error($this->email == env('FILAMENT_ADMIN_EMAIL'));
        return str_ends_with($this->email, '@gmail.com');
    }

    public function Contents():HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function IncomeCategories():HasMany
    {
        return $this->hasMany(IncomeCategory::class);
    }

    public function ExpenseCategories():HasMany
    {
        return $this->hasMany(ExpenceCategory::class);
    }

    public function MonthlyAmounts():HasMany
    {
        return $this->hasMany(MonthlyAmount::class);
    }
}