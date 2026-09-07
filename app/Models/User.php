<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\role;
use App\Models\vendor;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'password',
        'role_id',
        'profile_picture',
        'department_id',
        'vendor_code',
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

    public const PII_FIELDS = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'middle_name' => 'full_name',
        'email' => 'email',
        'employee_id' => 'employee_id',
        // 'phone' => 'phone',
        // 'mobile' => 'mobile',
        // 'address' => 'address',
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

    public function role()
    {
        return $this->belongsTo(role::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isAdmin(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['admin', 'administrator']);
    }

    public function getVendorAttribute()
    {
        return $this->belongsTo(vendor::class, 'vendor_code');
    }
}
