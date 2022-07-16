<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeCustomer($query)
    {
        return $this->getRole($query);
    }

    public function scopeAdmin($query)
    {
        return $this->getRole($query, 'admin');
    }

    protected function getRole($query, $role = 'customer')
    {
        return $query->where(
            'name',
            '=',
            config('constants.db.roles.' . $role)
        );
    }
}
