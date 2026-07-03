<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class QuotationPdfController extends Controller
{
    public function __invoke(Quotation $quotation): Response
    {
        $quotation->load(['lead', 'items.product', 'author']);

        Gate::authorize('view', $quotation->lead);

        $pdf = Pdf::loadView('crm.quotations.pdf', [
            'quotation' => $quotation,
        ]);

        $filename = 'quote-'.$quotation->quote_number.'.pdf';

        return $pdf->download($filename);
    }
}
