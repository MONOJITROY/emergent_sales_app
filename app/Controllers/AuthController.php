<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Models\User; use App\Core\Request; use App\Core\Csrf;

final class AuthController extends Controller {
    public function showLogin(Request $r): void {
        if (Auth::check()) { header('Location: ' . (\App\Core\App::config('base_url') ?: '') . '/'); exit; }
        $this->view('auth/login', [], null); // standalone (no layout)
    }
    public function login(Request $r): void {
        Csrf::check();
        $email = trim((string)$r->input('email', ''));
        $password = (string)$r->input('password', '');
        if ($email === '' || $password === '') { $this->json(['ok'=>false,'error'=>'Email and password required'], 400); return; }
        $u = User::byEmail($email);
        if (!$u || !password_verify($password, $u['password_hash'])) { $this->json(['ok'=>false,'error'=>'Invalid email or password'], 401); return; }
        Auth::login($u);
        $this->json(['ok'=>true,'user'=>['id'=>(int)$u['id'],'email'=>$u['email'],'name'=>$u['name'],'role'=>$u['role']]]);
    }
    public function logout(Request $r): void {
        Csrf::check(); Auth::logout(); $this->json(['ok'=>true]);
    }
}
