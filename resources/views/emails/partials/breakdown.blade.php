@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, '.', ',');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 16px 0; font-size: 14px;">
    @foreach ($quote->items as $item)
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; color: #1f2937;">
                {{ $item->service_name_snapshot }}
                @if ($item->quantity > 1)<span style="color: #6b7280;"> × {{ $item->quantity }}</span>@endif
            </td>
            <td align="right" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">{{ $money($item->line_total) }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="padding: 8px 0; color: #6b7280;">Subtotal</td>
        <td align="right" style="padding: 8px 0;">{{ $money($quote->subtotal) }}</td>
    </tr>
    @if ((float) $quote->discount_amount > 0)
        <tr>
            <td style="padding: 4px 0; color: #047857; font-weight: bold;">Ahorro por cotizar en línea</td>
            <td align="right" style="padding: 4px 0; color: #047857; font-weight: bold;">−{{ $money($quote->discount_amount) }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding: 4px 0; color: #6b7280;">Viáticos</td>
        <td align="right" style="padding: 4px 0;">{{ $travel['pending'] ? 'Por confirmar' : $money($quote->travel_fee) }}</td>
    </tr>
    <tr>
        <td style="padding: 10px 0; border-top: 2px solid #111827; font-weight: bold; font-size: 16px;">Total estimado</td>
        <td align="right" style="padding: 10px 0; border-top: 2px solid #111827; font-weight: bold; font-size: 16px; white-space: nowrap;">{{ $money($quote->total) }} MXN</td>
    </tr>
</table>
@if ($travel['pending'])
    <p style="margin: 0 0 16px; padding: 10px 12px; background: #fffbeb; border-left: 4px solid #f59e0b; font-size: 13px;">{{ $travel['label'] }}.</p>
@endif
