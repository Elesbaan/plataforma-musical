@extends('emails.layout', ['title' => "Tu cotización {$quote->folio}"])

@section('content')
    <p style="margin: 0 0 12px; font-size: 20px; font-weight: bold;">¡Gracias, {{ \Illuminate\Support\Str::of($quote->client_name)->before(' ') }}!</p>
    <p style="margin: 0 0 12px;">
        Adjuntamos tu cotización formal <strong>{{ $quote->folio }}</strong> para tu
        <strong>{{ mb_strtolower($quote->event_type) }}</strong> del
        <strong>{{ $quote->event_date->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</strong>.
    </p>

    @include('emails.partials.breakdown')

    <p style="margin: 0 0 20px; color: #6b7280; font-size: 13px;">
        Vigente hasta el {{ $quote->valid_until->locale('es')->translatedFormat('j \d\e F \d\e Y') }}. La fecha se aparta al confirmar tu reservación.
    </p>

    <p style="margin: 0 0 24px;">
        <a href="{{ $confirmationUrl }}" style="display: inline-block; padding: 12px 22px; background: {{ $primary }}; color: #ffffff; text-decoration: none; border-radius: 999px; font-weight: bold;">
            Ver mi cotización
        </a>
    </p>

    <p style="margin: 0;">Responde este correo o escríbenos por WhatsApp con tu folio para confirmar tu fecha.</p>
@endsection
