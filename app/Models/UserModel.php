<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['username', 'password_hash', 'full_name', 'role', 'is_active'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = '';

    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->where('is_active', 1)->first();
    }

    public function hasUsers(): bool
    {
        return $this->countAll() > 0;
    }
}
