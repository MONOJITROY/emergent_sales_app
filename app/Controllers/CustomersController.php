<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Request;
use App\Models\Customer;

final class CustomersController extends Controller {
    public function index(Request $r): void { Auth::user(); $this->view('customers/index', ['_active'=>'customers']); }
    public function list(Request $r): void { Auth::user(); $q = trim((string)($_GET['q'] ?? ''));
        $this->json(Customer::search(['name','email','phone'], $q)); }
    public function create(Request $r): void { Auth::user(); $this->requireCsrf();
        $d = $this->payload($r); if ($d['name']==='') { $this->json(['ok'=>false,'error'=>'Name required'],400); return; }
        $id = Customer::insert($d); $this->json(Customer::find($id)); }
    public function update(Request $r): void { Auth::user(); $this->requireCsrf();
        $id=(int)$r->param('id'); Customer::update($id, $this->payload($r)); $this->json(Customer::find($id)); }
    public function delete(Request $r): void { Auth::user(); $this->requireCsrf();
        Customer::delete((int)$r->param('id')); $this->json(['ok'=>true]); }
    private function payload(Request $r): array {
        return [
            'name' => trim((string)$r->input('name','')),
            'email' => trim((string)$r->input('email','')),
            'phone' => trim((string)$r->input('phone','')),
            'address' => trim((string)$r->input('address','')),
        ];
    }
}
