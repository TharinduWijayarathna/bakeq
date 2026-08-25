<?php

namespace App\Http\Controllers\Admin;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoiceDownloadController
{
    public function __invoke(Invoice $invoice): Response
    {
        $invoice->loadMissing(['order.items', 'order.user']);

        return Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
        ])
            ->setPaper('a4', 'portrait')
            ->download($invoice->number.'.pdf');
    }
}
