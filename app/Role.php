<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = [
            'admin' => ['*'],
            'manager' => ['users', 'products', 'categories', 'orders', 'reviews', 'shipping'],
            'support' => ['orders', 'reviews'],
        ];
        $rolePerms = $permissions[$this->slug] ?? [];
        return in_array('*', $rolePerms) || in_array($permission, $rolePerms);
    }
}
