<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'key';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['key', 'value'];
    protected $useTimestamps    = false;

    public function getValue(string $key, string $default = ''): string
    {
        $row = $this->find($key);
        return $row ? (string) ($row['value'] ?? $default) : $default;
    }

    public function setValue(string $key, string $value): void
    {
        if ($this->find($key)) {
            $this->where('key', $key)->set('value', $value)->update();
        } else {
            $this->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function getAll(): array
    {
        $rows   = $this->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'] ?? '';
        }
        return $result;
    }
}
