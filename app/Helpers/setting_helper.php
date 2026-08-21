<?php

if (! function_exists('pendingReturnsCount')) {
    function pendingReturnsCount(): int
    {
        static $count = null;
        if ($count === null) {
            try {
                $db    = \Config\Database::connect();
                $count = (int) $db->table('returns')
                                  ->where('status', 'pending')
                                  ->countAllResults();
            } catch (\Throwable $e) {
                $count = 0;
            }
        }
        return $count;
    }
}

if (! function_exists('outstandingCreditCount')) {
    /** จำนวนบิลเงินเชื่อที่ยังค้างชำระ (badge เมนู "ค้างชำระ/เงินเชื่อ") */
    function outstandingCreditCount(): int
    {
        static $count = null;
        if ($count === null) {
            try {
                $db    = \Config\Database::connect();
                $count = (int) $db->table('sales')
                                  ->where('payment_method', 'credit')
                                  ->where('credit_settled_at IS NULL')
                                  ->where('voided_at IS NULL')
                                  ->countAllResults();
            } catch (\Throwable $e) {
                $count = 0; // ตาราง/คอลัมน์ยังไม่ถูกสร้าง (ยังไม่กดอัพเดทฐานข้อมูล)
            }
        }
        return $count;
    }
}

if (! function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        static $cache = null;

        if ($cache === null) {
            try {
                $db    = \Config\Database::connect();
                $rows  = $db->table('settings')->get()->getResultArray();
                $cache = [];
                foreach ($rows as $row) {
                    $cache[$row['key']] = $row['value'];
                }
            } catch (\Throwable $e) {
                $cache = [];
            }
        }

        return isset($cache[$key]) ? (string) $cache[$key] : $default;
    }
}
