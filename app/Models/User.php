<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use CrudTrait;
    use HasRoles;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'no_order',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function getRoles()
    {
        $this->loadMissing('roles');

        return $this->roles;
    }

    public function canAccessAllCompanies(): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        try {
            return $this->hasPermissionTo('BUKA SEMUA PILIHAN PERUSAHAAN');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Relasi Many-to-Many ke Company
     */
    public function companies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user', 'user_id', 'company_id')
                    ->withTimestamps();
    }

    /**
     * Mengambil daftar ID company yang dapat diakses oleh user ini
     */
    public function getAccessibleCompanyIds(): array
    {
        if ($this->canAccessAllCompanies()) {
            return Company::pluck('id')->toArray();
        }

        return $this->companies()->pluck('companies.id')->toArray();
    }
}
