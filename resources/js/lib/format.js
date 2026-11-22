const moneyFormatter = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

/** Formatea un importe en pesos mexicanos (ej. $28,500). */
export function formatMoney(value) {
    const number = Number(value);
    return Number.isFinite(number) ? moneyFormatter.format(number) : '';
}

export function formatPercent(value) {
    const number = Number(value);
    return Number.isFinite(number) ? `${number.toLocaleString('es-MX', { maximumFractionDigits: 2 })}%` : '';
}
