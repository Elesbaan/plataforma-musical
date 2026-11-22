const TONES = {
    neutral: 'bg-surface-sunken text-ink-muted',
    success: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    warning: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    danger: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
    brand: 'bg-primary/10 text-primary',
};

export default function Badge({ tone = 'neutral', className = '', children }) {
    return (
        <span className={`inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ${TONES[tone]} ${className}`}>
            {children}
        </span>
    );
}
