<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Models\Taxtype;

final class TaxtypeController extends Controller
{
    public function index(Request $r): void
    {
        Auth::user();
        $this->view('taxtypes/index', ['_active' => 'taxtypes']);
    }

    public function list(Request $r): void
    {
        Auth::user();
        $this->json(Taxtype::all());
    }

    public function create(Request $r): void
    {
        Auth::user();
        $this->requireCsrf();
        $d = $this->payload($r);
        if ($d['taxname'] === '') {
            $this->json(['ok' => false, 'error' => 'Tax name required'], 400);
            return;
        }
        $id = Taxtype::insert($d);
        $this->json(Taxtype::find($id));
    }

    public function update(Request $r): void
    {
        Auth::user();
        $this->requireCsrf();
        $id = (int)$r->param('id');
        Taxtype::update($id, $this->payload($r));
        $this->json(Taxtype::find($id));
    }

    public function delete(Request $r): void
    {
        Auth::user();
        $this->requireCsrf();
        Taxtype::delete((int)$r->param('id'));
        $this->json(['ok' => true]);
    }

    private function payload(Request $r): array
    {
        $undergroup = $r->input('undergroup', 'Duties & Taxes');
        $typeofduty = $r->input('typeofduty', 'GST');
        return [
            'undergroup' => in_array($undergroup, ['Duties & Taxes', 'Others'], true) ? $undergroup : 'Duties & Taxes',
            'typeofduty' => in_array($typeofduty, ['GST', 'Others'], true) ? $typeofduty : 'GST',
            'taxname'    => trim((string)$r->input('taxname', '')),
            'percentage' => (float)$r->input('percentage', 0),
        ];
    }
}
