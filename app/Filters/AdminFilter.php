<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->to(site_url('/sales'))
                ->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
