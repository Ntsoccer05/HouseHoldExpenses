<?php

namespace App\Models;

use App\Notifications\UserCustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
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
    public function sendEmailVerificationNotification()
    {
        $this->notify(new UserCustomVerifyEmail);
    }

    // public function hasVerifiedEmail()
    // {
    //     // $thisには使用先で$user->hasVerifiedEmail()とした場合の$userが入る
    //     return ! is_null($this->email_verified_at);
    // }

    // public function markEmailAsVerified()
    // {
    //     // $thisには使用先で$user->hasVerifiedEmail()とした場合の$userが入る
    //     return $this->forceFill([
    //         'email_verified_at' => $this->freshTimestamp(),
    //     ])->save();
    // }

}