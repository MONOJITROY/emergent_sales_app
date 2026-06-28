<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Request;
use App\Models\User;

final class UsersController extends Controller {
    public function index(Request $r): void { Auth::requireAdmin(); $this->view('users/index', ['_active'=>'users']); }
    public function apiList(Request $r): void {
        Auth::requireAdmin();
        $rows = \App\Core\Database::pdo()->query('SELECT id, email, name, role, created_at FROM users ORDER BY id DESC')->fetchAll();
        $this->json($rows);
    }
    public function apiCreate(Request $r): void {
        Auth::requireAdmin(); $this->requireCsrf();
        $email = strtolower(trim((string)$r->input('email','')));
        $password = (string)$r->input('password','');
        $name = trim((string)$r->input('name',''));
        $role = $r->input('role','staff'); $role = in_array($role,['admin','staff'],true) ? $role : 'staff';
        if ($email===''||$password===''||$name==='') { $this->json(['ok'=>false,'error'=>'Name, email, password required'],400); return; }
        if (User::byEmail($email)) { $this->json(['ok'=>false,'error'=>'Email already exists'],400); return; }
        $id = User::insert(['email'=>$email,'password_hash'=>password_hash($password,PASSWORD_BCRYPT),'name'=>$name,'role'=>$role]);
        $u = User::find($id); unset($u['password_hash']); $this->json($u);
    }
    public function apiDelete(Request $r): void {
        $me = Auth::requireAdmin(); $this->requireCsrf();
        $id = (int)$r->param('id');
        if ($id === (int)$me['id']) { $this->json(['ok'=>false,'error'=>'Cannot delete yourself'],400); return; }
        User::delete($id); $this->json(['ok'=>true]);
    }
}
