<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Quote;
use App\Services\QuoteConversionService;
use Illuminate\Http\RedirectResponse;

/**
 * "Confirmar y agendar evento" (requiere quotes.manage y events.manage).
 */
class QuoteConversionController extends Controller
{
    public function __invoke(EventRequest $request, Quote $quote, QuoteConversionService $conversion): RedirectResponse
    {
        $conversion->convert($quote, $request->validated(), $request->boolean('force_overbooking'));

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('success', "La cotización {$quote->folio} quedó confirmada y el evento se agendó.");
    }
}
