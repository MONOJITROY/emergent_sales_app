<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Models\Company;

final class CompanyController extends Controller
{
    public function index(Request $r): void
    {
        Auth::requireAdmin();
        $settings = Company::settings();
        $this->view('company/settings', ['_active' => 'company', 'settings' => $settings]);
    }

    public function get(Request $r): void
    {
        Auth::requireAdmin();
        $this->json(Company::settings() ?: []);
    }

    public function save(Request $r): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();

        $data = $this->payload($r);

        // Handle logo upload
        if (!empty($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $info = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($info, $_FILES['company_logo']['tmp_name']);
            finfo_close($info);

            if (!in_array($mime, $allowed, true)) {
                $this->json(['ok' => false, 'error' => 'Logo must be JPEG, PNG, GIF, or WebP'], 400);
                return;
            }

            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                default      => 'png',
            };
            $filename = 'company_logo_' . date('Ymd_His') . '.' . $ext;
            $dest = __DIR__ . '/../../public/assets/images/' . $filename;
            move_uploaded_file($_FILES['company_logo']['tmp_name'], $dest);

            // Remove old logo file
            $current = Company::settings();
            if (!empty($current['company_logo'])) {
                $old = __DIR__ . '/../../public/assets/images/' . $current['company_logo'];
                if (file_exists($old)) @unlink($old);
            }

            $data['company_logo'] = $filename;
        }

        Company::update(1, $data);
        $this->json(Company::settings());
    }

    private function payload(Request $r): array
    {
        return [
            'company_name'            => trim((string)$r->input('company_name', '')),
            'company_address'         => trim((string)$r->input('company_address', '')),
            'company_phone'           => trim((string)$r->input('company_phone', '')),
            'company_email'           => trim((string)$r->input('company_email', '')),
            'owner_name'              => trim((string)$r->input('owner_name', '')),
            'company_gst_no'          => trim((string)$r->input('company_gst_no', '')),
            'company_gst_validity'    => trim((string)$r->input('company_gst_validity', '')),
            'company_tradelicenseno'  => trim((string)$r->input('company_tradelicenseno', '')),
            'tradelicensevalidity'    => trim((string)$r->input('tradelicensevalidity', '')),
            'company_website'         => trim((string)$r->input('company_website', '')),
            'company_pan'             => trim((string)$r->input('company_pan', '')),
            'company_emailhost'       => trim((string)$r->input('company_emailhost', '')),
            'company_smtpauth'        => (int)(bool)$r->input('company_smtpauth', 0),
            'company_security'        => in_array($r->input('company_security', 'tls'), ['ssl', 'tls'], true) ? $r->input('company_security') : 'tls',
            'company_ssl_port'        => (int)($r->input('company_ssl_port', 587)),
            'company_emailuser'       => trim((string)$r->input('company_emailuser', '')),
            'company_emailpassword'   => trim((string)$r->input('company_emailpassword', '')),
            'company_fromemailid'     => trim((string)$r->input('company_fromemailid', '')),
            'company_fromemailname'   => trim((string)$r->input('company_fromemailname', '')),
            'company_replytoemailid'  => trim((string)$r->input('company_replytoemailid', '')),
            'company_replytoemailname'=> trim((string)$r->input('company_replytoemailname', '')),
            'company_noreplyemailid'  => trim((string)$r->input('company_noreplyemailid', '')),
            'company_noreplyemailname'=> trim((string)$r->input('company_noreplyemailname', '')),
        ];
    }
}
