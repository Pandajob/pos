<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /** หน้า Login */
    public function login()
    {
        // ถ้า login อยู่แล้ว → ไปหน้าหลัก
        if (session()->get('auth_user')) {
            return redirect()->to('/');
        }
        // ยังไม่มี user เลย → ไปหน้า setup
        if (! $this->userModel->hasUsers()) {
            return redirect()->to('/setup');
        }
        return view('auth/login', ['title' => 'เข้าสู่ระบบ']);
    }

    /** POST Login */
    public function doLogin()
    {
        $username = trim($this->request->getPost('username') ?? '');
        $password = $this->request->getPost('password') ?? '';

        $user = $this->userModel->findByUsername($username);

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()
                ->with('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        }

        session()->set('auth_user', [
            'id'        => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ]);

        return redirect()->to('/');
    }

    /** Logout */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    /** Setup — สร้าง Admin ครั้งแรก (เฉพาะเมื่อยังไม่มี user) */
    public function setup()
    {
        if ($this->userModel->hasUsers()) {
            return redirect()->to('/login');
        }
        return view('auth/setup', ['title' => 'ตั้งค่าเริ่มต้น']);
    }

    public function doSetup()
    {
        if ($this->userModel->hasUsers()) {
            return redirect()->to('/login');
        }

        $fullName = trim($this->request->getPost('full_name') ?? '');
        $username = trim($this->request->getPost('username') ?? '');
        $password = $this->request->getPost('password') ?? '';
        $confirm  = $this->request->getPost('confirm') ?? '';

        if (empty($fullName) || empty($username) || strlen($password) < 6) {
            return redirect()->back()
                ->with('error', 'กรุณากรอกข้อมูลให้ครบ (รหัสผ่านอย่างน้อย 6 ตัวอักษร)');
        }
        if ($password !== $confirm) {
            return redirect()->back()->with('error', 'รหัสผ่านไม่ตรงกัน');
        }

        $this->userModel->insert([
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name'     => $fullName,
            'role'          => 'admin',
            'is_active'     => 1,
        ]);

        return redirect()->to('/login')
            ->with('success', 'สร้างบัญชีผู้ดูแลระบบสำเร็จ กรุณาเข้าสู่ระบบ');
    }

    /** หน้าจัดการ Users (Admin only) */
    public function users()
    {
        return view('auth/users', [
            'title' => 'จัดการผู้ใช้งาน',
            'users' => $this->userModel->orderBy('role')->findAll(),
        ]);
    }

    public function createUser()
    {
        $fullName = trim($this->request->getPost('full_name') ?? '');
        $username = trim($this->request->getPost('username') ?? '');
        $password = $this->request->getPost('password') ?? '';
        $role     = $this->request->getPost('role') === 'admin' ? 'admin' : 'cashier';

        if (empty($fullName) || empty($username) || strlen($password) < 6) {
            return redirect()->back()->with('error', 'ข้อมูลไม่ครบ (รหัสผ่านอย่างน้อย 6 ตัวอักษร)');
        }

        if ($this->userModel->where('username', $username)->countAllResults() > 0) {
            return redirect()->back()->with('error', 'ชื่อผู้ใช้ "' . esc($username) . '" มีอยู่แล้ว กรุณาใช้ชื่ออื่น');
        }

        $this->userModel->insert([
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name'     => $fullName,
            'role'          => $role,
            'is_active'     => 1,
        ]);

        return redirect()->to('/users')->with('success', 'เพิ่มผู้ใช้งานสำเร็จ');
    }

    public function deleteUser(int $id)
    {
        $auth = session()->get('auth_user');
        if ($auth['id'] === $id) {
            return redirect()->to('/users')->with('error', 'ไม่สามารถลบบัญชีตัวเองได้');
        }
        $this->userModel->delete($id);
        return redirect()->to('/users')->with('success', 'ลบผู้ใช้งานสำเร็จ');
    }

    /** หน้าโปรไฟล์ — ใช้ได้ทุก role */
    public function profile()
    {
        $auth = session()->get('auth_user');
        return view('auth/profile', [
            'title' => 'โปรไฟล์ของฉัน',
            'user'  => $this->userModel->find($auth['id']),
        ]);
    }

    public function doProfile()
    {
        $auth    = session()->get('auth_user');
        $old     = $this->request->getPost('old_password') ?? '';
        $new     = $this->request->getPost('new_password') ?? '';
        $confirm = $this->request->getPost('confirm') ?? '';

        $user = $this->userModel->find($auth['id']);

        if (! password_verify($old, $user['password_hash'])) {
            return redirect()->back()->with('error', 'รหัสผ่านเดิมไม่ถูกต้อง');
        }
        if (strlen($new) < 6) {
            return redirect()->back()->with('error', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
        }
        if ($new !== $confirm) {
            return redirect()->back()->with('error', 'รหัสผ่านใหม่ไม่ตรงกัน');
        }

        $this->userModel->update($auth['id'], [
            'password_hash' => password_hash($new, PASSWORD_BCRYPT),
        ]);

        return redirect()->back()->with('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
    }

    public function changePassword()
    {
        return $this->doProfile();
    }
}
