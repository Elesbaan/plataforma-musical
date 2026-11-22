@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, '.', ',');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->locale('es')->translatedFormat('j \d\e F \d\e Y') : '—';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quote->folio }}</title>
    <style>
        @page { margin: 36px 40px 56px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 10.5px; color: #1f2937; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        .muted { color: #6b7280; }
        .header { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand-name { font-size: 20px; font-weight: bold; color: {{ $secondary }}; }
        .folio-box { text-align: right; }
        .folio { display: inline-block; padding: 6px 12px; border-radius: 6px; background: {{ $primary }}; color: #ffffff; font-size: 13px; font-weight: bold; }
        .rule { height: 4px; background: {{ $primary }}; margin: 16px 0 18px; border-radius: 2px; }
        .section-title { font-size: 9px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: {{ $primary }}; margin-bottom: 6px; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 0; }
        .cards td.card { width: 48%; vertical-align: top; padding: 12px; background: #f8f7f5; border-radius: 8px; }
        .cards td.gap { width: 4%; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 2px 0; }
        .kv td.k { color: #6b7280; width: 38%; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items th { background: {{ $secondary }}; color: #ffffff; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; padding: 8px; text-align: left; }
        table.items td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; }
        table.items th.num { text-align: right; }
        table.items th.center { text-align: center; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .totals { width: 48%; margin-left: 52%; border-collapse: collapse; margin-top: 12px; }
        .totals td { padding: 5px 8px; }
        .totals .saving td { color: #047857; font-weight: bold; }
        .totals .grand td { border-top: 2px solid {{ $secondary }}; font-size: 14px; font-weight: bold; color: {{ $secondary }}; padding-top: 8px; }
        .note { margin-top: 16px; padding: 10px 12px; border-left: 4px solid {{ $accent }}; background: #fffbeb; }
        .terms { margin-top: 22px; font-size: 9px; color: #4b5563; }
        .footer { position: fixed; bottom: -36px; left: 0; right: 0; font-size: 8.5px; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $settings->brand_name }}
        @if ($settings->phone_number) · Tel. {{ $settings->phone_number }} @endif
        @if ($settings->whatsapp_number) · WhatsApp {{ $settings->whatsapp_number }} @endif
        @if ($settings->contact_email) · {{ $settings->contact_email }} @endif
    </div>

    <table class="header">
        <tr>
            <td>
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $settings->brand_name }}" style="max-height: 56px; max-width: 220px;">
                @else
                    <p class="brand-name">{{ $settings->brand_name }}</p>
                @endif
                @if ($settings->tagline)
                    <p class="muted" style="margin-top: 4px;">{{ $settings->tagline }}</p>
                @endif
                @if ($settings->address_text)
                    <p class="muted">{{ $settings->address_text }}</p>
                @endif
            </td>
            <td class="folio-box">
                <p class="muted" style="font-size: 9px; text-transform: uppercase; letter-spacing: 1px;">Cotización</p>
                <p class="folio">{{ $quote->folio }}</p>
                <p class="muted" style="margin-top: 6px;">Emitida: {{ $date($quote->created_at) }}</p>
                <p><strong>Vigente hasta: {{ $date($quote->valid_until) }}</strong></p>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="cards">
        <tr>
            <td class="card">
                <p class="section-title">Cliente</p>
                <table class="kv">
                    <tr><td class="k">Nombre</td><td><strong>{{ $quote->client_name }}</strong></td></tr>
                    <tr><td class="k">Correo</td><td>{{ $quote->client_email }}</td></tr>
                    <tr><td class="k">Teléfono</td><td>{{ $quote->client_phone }}</td></tr>
                </table>
            </td>
            <td class="gap"></td>
            <td class="card">
                <p class="section-title">Evento</p>
                <table class="kv">
                    <tr><td class="k">Tipo</td><td><strong>{{ $quote->event_type }}</strong></td></tr>
                    <tr><td class="k">Fecha</td><td>{{ $date($quote->event_date) }}@if ($quote->event_time), {{ substr($quote->event_time, 0, 5) }} h @endif</td></tr>
                    <tr><td class="k">Ciudad</td><td>{{ $travel['city'] ?? '—' }}</td></tr>
                    @if ($quote->venue_name)
                        <tr><td class="k">Lugar</td><td>{{ $quote->venue_name }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="center" style="width: 12%;">Cant.</th>
                <th class="num" style="width: 20%;">Precio unitario</th>
                <th class="num" style="width: 20%;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quote->items as $item)
                <tr>
                    <td>{{ $item->service_name_snapshot }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="num">{{ $money($item->unit_price) }}</td>
                    <td class="num">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">{{ $money($quote->subtotal) }}</td></tr>
        @if ((float) $quote->discount_amount > 0)
            <tr class="saving"><td>Ahorro por cotizar en línea</td><td class="num">−{{ $money($quote->discount_amount) }}</td></tr>
        @endif
        <tr>
            <td>Viáticos{{ $travel['city'] ? ' (' . $travel['city'] . ')' : '' }}</td>
            <td class="num">{{ $travel['pending'] ? 'Por confirmar' : $money($quote->travel_fee) }}</td>
        </tr>
        <tr class="grand"><td>Total estimado</td><td class="num">{{ $money($quote->total) }} MXN</td></tr>
    </table>

    @if ($travel['pending'])
        <div class="note"><strong>Importante:</strong> {{ $travel['label'] }}.</div>
    @endif

    <div class="terms">
        <p class="section-title">Términos y condiciones</p>
        <p>Precios en pesos mexicanos. Esta cotización es válida hasta el {{ $date($quote->valid_until) }} y no aparta la fecha: la disponibilidad se confirma al reservar.</p>
        @if ($settings->quote_terms_text)
            <p style="margin-top: 6px; white-space: pre-line;">{{ $settings->quote_terms_text }}</p>
        @endif
    </div>
</body>
</html>
