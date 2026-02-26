<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;  // FPDI with TCPDF driver
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;

class BadgeController extends Controller
{
    /**
     * GET /api/generateBadge
     *
     * Overlays dynamic visitor info (name, company, QR) onto the
     * original BADGE-HAYGE.pdf template stored in storage/app/badge_template.pdf
     *
     * Auth: Bearer token (Sanctum / Passport)
     */
    public function generateBadge(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['company', 'roles']);

        // ── Pull data from User model ──────────────────────────────────────────
        $fullname     = strtoupper(trim($user->name . ' ' . $user->last_name));
        $company_name = $user->company ? $user->company->name : $user->company_name;
        $qr_data      = strtoupper(trim($user->name . ' ' . $user->last_name));
        $user_role    =  $user->roles->first()?->slug ?? 'visitor';

        // ── Build PDF ──────────────────────────────────────────────────────────
        $pdfContent = $this->overlayBadge($fullname, $company_name, $qr_data, $user_role);

        // ── Stream response ────────────────────────────────────────────────────
        $filename = 'badge_' . str()->slug($fullname) . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            // Change 'inline' → 'attachment' to force download
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  CORE: Import original PDF and overlay only the dynamic fields
    // ──────────────────────────────────────────────────────────────────────────

    private function overlayBadge(string $fullname, string $company_name, string $qr_data, string $user_role): string
    {
        $templatePath = storage_path('app/badge_visitor.pdf');
        if ($user_role == "exhibitor") {
            $templatePath = storage_path('app/badge_exhibitor.pdf');
        }

        // ── Init FPDI (TCPDF driver) ───────────────────────────────────────────
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // ── Import the ORIGINAL badge as a full-page background ───────────────
        $pdf->setSourceFile($templatePath);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, 210, 297); // A4 portrait: 210 × 297 mm

        // ──────────────────────────────────────────────────────────────────────
        //  Coordinates (detected via pdfplumber on the real PDF, in mm):
        //
        //   Page:     210 × 297 mm  (A4 portrait)
        //   Right panel starts at x = 105 mm  →  center = 157.5 mm
        //
        //   Original text positions:
        //     ISSAM GUIOUAZ  →  y ≈ 50.6 mm
        //     TEST           →  y ≈ 62.6 mm
        //     QR code area   →  y ≈ 70 – 110 mm
        // ──────────────────────────────────────────────────────────────────────

        $panelX = 105;   // right panel left edge
        $panelW = 105;   // right panel width (goes to 210 mm)

        // ── Step 1 — Erase originals with white fill ──────────────────────────
//        $pdf->SetFillColor(255, 255, 255);
//
//        $pdf->Rect($panelX, 44, $panelW, 14, 'F');  // covers name  (y 44–58)
//        $pdf->Rect($panelX, 58, $panelW, 11, 'F');  // covers company (y 58–69)
//        $pdf->Rect($panelX, 68, $panelW, 45, 'F');  // covers QR code (y 68–113)

        // ── Step 2 — Write dynamic FULL NAME ─────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($panelX, 46);
        $pdf->Cell($panelW, 10, $fullname, 0, 1, 'C');

        // ── Step 3 — Write dynamic COMPANY NAME ──────────────────────────────
        $pdf->SetFont('helvetica', '', 13);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($panelX, 59);
        $pdf->Cell($panelW, 8, $company_name, 0, 1, 'C');


        // ── Step 4 — Generate modern styled QR CODE ───────────────────────────────
        $options = new QROptions([
            // REMOVE OR COMMENT OUT THIS LINE:
            // 'version'             => 5,

            // OPTIONAL: You can explicitly tell it to auto-size if you prefer:
            'version'             => QRCode::VERSION_AUTO,

            'eccLevel'            => QRCode::ECC_H,
            'outputType'          => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64'         => false,

            'drawCircularModules' => true,
            'circleRadius'        => 3.45,

            'keepAsSquare' => [
                QRMatrix::M_FINDER,
                QRMatrix::M_FINDER_DOT,
                QRMatrix::M_FINDER_DARK,
            ],

            'moduleValues' => [
                QRMatrix::M_FINDER_DARK => [10, 25, 60],
                QRMatrix::M_FINDER_DOT  => [10, 25, 60],
                QRMatrix::M_DATA_DARK   => [10, 25, 60],
                QRMatrix::M_FINDER      => [255, 255, 255],
                QRMatrix::M_DATA        => [255, 255, 255],
                QRMatrix::M_LOGO        => [255, 255, 255],
            ],

            'scale'            => 10,
            'imageTransparent' => false,
            'bgColor'          => [255, 255, 255],
        ]);

        // Generate PNG to a temp file
        $qrCode   = new QRCode($options);
        $qrPng    = $qrCode->render($qr_data);           // raw PNG binary
        $tmpQr    = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($tmpQr, $qrPng);

        // Place it in the PDF — 23×23 mm, centered in right panel
        $qrSize = 23;
        $qrX    = 158 - ($qrSize / 2);   // panel center minus half width
        $pdf->Image($tmpQr, $qrX, 76, $qrSize, $qrSize, 'PNG');

        // Clean up temp file
        @unlink($tmpQr);

        // ── Return raw PDF string ─────────────────────────────────────────────
        return $pdf->Output('badge.pdf', 'S');
    }
}
