@extends('emails.layout', ['title' => "Nueva cotización {$quote->folio}"])

@section('content')
    <p style="margin: 0 0 16px; font-size: 20px; font-weight: bold;">Nueva cotización en línea: {{ $quote->folio }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; margin-bottom: 8px;">
        <tr><td style="padding: 3px 0; color: #6b7280; width: 40%;">Cliente</td><td><strong>{{ $quote->client_name }}</strong></td></tr>
        <tr><td style="padding: 3px 0; color: #6b7280;">Correo</td><td>{{ $quote->client_email }}</td></tr>
        <tr><td style="padding: 3px 0; color: #6b7280;">Teléfono</td><td>{{ $quote->client_phone }}</td></tr>
        <tr><td style="padding: 3px 0; color: #6b7280;">Evento</td><td>{{ $quote->event_type }}</td></tr>
        <tr>
            <td style="padding: 3px 0; color: #6b7280;">Fecha</td>
            <td>{{ $quote->event_date->locale('es')->translatedFormat('j \d\e F \d\e Y') }}@if ($quote->event_time), {{ substr($quote->event_time, 0, 5) }} h @endif</td>
        </tr>
        <tr><td style="padding: 3px 0; color: #6b7280;">Ciudad</td><td>{{ $travel['city'] ?? '—' }}</td></tr>
        @if ($quote->venue_name)
            <tr><td style="padding: 3px 0; color: #6b7280;">Lugar</td><td>{{ $quote->venue_name }}</td></tr>
        @endif
    </table>

    @include('emails.partials.breakdown')

    <p style="margin: 0; color: #6b7280; font-size: 13px;">El PDF va adjunto. Responde este correo para escribirle directamente al cliente.</p>
@endsection
