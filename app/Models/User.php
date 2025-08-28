<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Traits\HasCommission;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin \Spatie\Permission\Traits\HasRoles
 */

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasCommission, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

    public function gestor()
    {
        return $this->hasOne(\App\Models\Gestor::class);
    }

    public function distribuidor()
    {
        return $this->hasOne(\App\Models\Distribuidor::class);
    }

    public function advogado()
    {
        return $this->hasOne(Advogado::class);
    }

    public function diretorComercial()
    {
        return $this->hasOne(DiretorComercial::class);
    }

}
