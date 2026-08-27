<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportType;
use App\Support\BakeryReports;
use App\Support\Brand;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportDownloadController
{
    public function __invoke(Request $request, string $report): Response
    {
        $type = ReportType::tryFrom($report);
        abort_unless($type !== null, 404);

        $monthInput = (string) $request->query('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
            $monthInput = now()->format('Y-m');
        }

        $month = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        $payload = BakeryReports::build($type, $month);
        $filename = 'report-'.$type->value.'-'.$month->format('Y-m').'.pdf';

        return Pdf::loadView('admin.reports.pdf', [
            'payload' => $payload,
            'brandName' => Brand::name(),
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
