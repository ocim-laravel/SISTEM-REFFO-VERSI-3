<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_DESAINER = 'desainer';

    public const ROLE_OPERATOR_CETAK = 'operator_cetak';

    public const ROLE_FINISHING = 'finishing';

    public const ROLE_ADVERTISING = 'advertising';

    public const ROLE_OWNER = 'owner';

    /**
     * @var array<int, string>
     */
    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_DESAINER,
        self::ROLE_OPERATOR_CETAK,
        self::ROLE_FINISHING,
        self::ROLE_ADVERTISING,
        self::ROLE_OWNER,
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

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_DESAINER => 'Desainer Grafis',
            self::ROLE_OPERATOR_CETAK => 'Operator Cetak',
            self::ROLE_FINISHING => 'Finishing',
            self::ROLE_ADVERTISING => 'Advertising',
            self::ROLE_OWNER => 'Owner',
            default => 'Role Tidak Dikenal',
        };
    }

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_DESAINER => 'desainer.dashboard',
            self::ROLE_OPERATOR_CETAK => 'operator.dashboard',
            self::ROLE_FINISHING => 'finishing.dashboard',
            self::ROLE_ADVERTISING => 'advertising.dashboard',
            self::ROLE_OWNER => 'owner.dashboard',
            default => abort(403, 'Role pengguna tidak valid.'),
        };
    }
}
