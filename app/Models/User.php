<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole('admin');
        }

        if ($panel->getId() === 'gerente') {
            return $this->hasRole('club_manager') && ! is_null($this->club_id);
        }

        if ($panel->getId() === 'painel') {
            return $this->hasRole('player');
        }

        return false;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'fcm_token',
        'club_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'password_reset_code',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'email_verified_at'             => 'datetime',
        'email_verification_expires_at' => 'datetime',
        'password_reset_expires_at'     => 'datetime',
        'password'                      => 'hashed',
    ];

    public function sendEmailVerificationNotification(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_verification_code'       => hash('sha256', $code),
            'email_verification_expires_at' => now()->addMinutes(30),
        ])->save();

        $this->notify(new VerifyEmailNotification($code));
    }

    public function hasValidVerificationCode(string $code): bool
    {
        if (is_null($this->email_verification_code)) {
            return false;
        }

        if ($this->email_verification_expires_at?->isPast()) {
            return false;
        }

        return hash_equals($this->email_verification_code, hash('sha256', $code));
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at'             => $this->freshTimestamp(),
            'email_verification_code'       => null,
            'email_verification_expires_at' => null,
        ])->save();
    }

    public function sendPasswordResetCode(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'password_reset_code'       => hash('sha256', $code),
            'password_reset_expires_at' => now()->addMinutes(30),
        ])->save();

        $this->notify(new ResetPasswordNotification($code));
    }

    public function hasValidPasswordResetCode(string $code): bool
    {
        if (is_null($this->password_reset_code)) {
            return false;
        }

        if ($this->password_reset_expires_at?->isPast()) {
            return false;
        }

        return hash_equals($this->password_reset_code, hash('sha256', $code));
    }

    public function player()
    {
        return $this->hasOne(Player::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
