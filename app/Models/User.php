<?php
// Kullanıcı modeli (users tablosu). Giriş ve Filament yönetim paneli için kullanılır.
// Yönetici bilgisi is_admin alanındadır; panele giriş canAccessPanel() ile kontrol edilir.
// Şifre casts ayarı sayesinde otomatik hash'lenir.

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
/**
 * Giriş yapabilen kullanıcı. Filament panelini kullanabilmesi için canAccessPanel() koşulunu sağlaması gerekir.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Filament paneline girişe izin verilip verilmeyeceğini belirler. Sadece 'admin' paneli ve is_admin=true olan kullanıcılar girebilir.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // İki koşul birlikte aranır (&&): doğru panel ve yönetici bayrağı. Biri yanlışsa giriş reddedilir.
        return $panel->getId() === 'admin'
            && (bool) $this->is_admin;
    }

}
