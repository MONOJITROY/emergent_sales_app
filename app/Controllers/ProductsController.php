<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Request;
use App\Models\Product;

final class ProductsController extends Controller {
    public function index(Request $r): void { Auth::user(); $this->view('products/index', ['_active'=>'products']); }

    public function list(Request $r): void {
        Auth::user();
        $q = trim((string)($_GET['q'] ?? ''));
        $rows = Product::search(['sku','name','category'], $q, 'id DESC');
        $this->json($rows);
    }
    public function create(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $d = $this->payload($r);
        if ($d['sku'] === '' || $d['name'] === '') { $this->json(['ok'=>false,'error'=>'SKU and name required'], 400); return; }
        $id = Product::insert($d);
        $this->json(Product::find($id));
    }
    public function update(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $id = (int)$r->param('id'); $d = $this->payload($r);
        Product::update($id, $d);
        $this->json(Product::find($id));
    }
    public function delete(Request $r): void {
        Auth::user(); $this->requireCsrf();
        Product::delete((int)$r->param('id')); $this->json(['ok'=>true]);
    }
    private function payload(Request $r): array {
        return [
            'sku' => trim((string)$r->input('sku','')),
            'name' => trim((string)$r->input('name','')),
            'category' => trim((string)$r->input('category','')),
            'unit' => trim((string)$r->input('unit','pcs')) ?: 'pcs',
            'cost_price' => (float)$r->input('cost_price',0),
            'sale_price' => (float)$r->input('sale_price',0),
            'stock' => (float)$r->input('stock',0),
            'reorder_level' => (float)$r->input('reorder_level',0),
            'description' => (string)$r->input('description',''),
        ];
    }
}
