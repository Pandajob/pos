<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerTierModel extends Model
{
    protected $table            = 'customer_tiers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['name', 'discount_pct', 'is_wholesale', 'badge_color', 'description'];
    protected $useTimestamps    = false;

    public function getAll(): array
    {
        return $this->orderBy('id')->findAll();
    }
}
