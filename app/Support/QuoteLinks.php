<?php

namespace App\Support;

use App\Models\Quote;
use Illuminate\Support\Facades\URL;

/**
 * URLs firmadas de una cotización. El folio es secuencial (adivinable), así que
 * la confirmación y el PDF solo se abren con una firma válida.
 */
class QuoteLinks
{
    public static function confirmation(Quote $quote): string
    {
        return URL::signedRoute('quotes.show', ['folio' => $quote->folio]);
    }

    public static function download(Quote $quote): string
    {
        return URL::signedRoute('quotes.pdf', ['folio' => $quote->folio]);
    }
}
