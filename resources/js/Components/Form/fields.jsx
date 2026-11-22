import { useId } from 'react';

export const inputClass =
    'block min-h-touch w-full rounded-xl border border-line bg-surface px-4 text-base text-ink placeholder:text-ink-muted/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60 sm:text-sm';

/**
 * Envoltura de campo: etiqueta, ayuda y error accesibles.
 * `children` recibe { id, describedBy, invalid } para conectar el control.
 */
export function Field({ label, error, hint, required = false, className = '', children }) {
    const id = useId();
    const hintId = hint ? `${id}-hint` : undefined;
    const errorId = error ? `${id}-error` : undefined;
    const describedBy = [hintId, errorId].filter(Boolean).join(' ') || undefined;

    return (
        <div className={className}>
            <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-ink">
                {label}
                {required && (
                    <span className="text-red-600 dark:text-red-400" aria-hidden="true">
                        {' '}
                        *
                    </span>
                )}
            </label>
            {children({ id, describedBy, invalid: !!error })}
            {hint && !error && (
                <p id={hintId} className="mt-1.5 text-xs text-ink-muted">
                    {hint}
                </p>
            )}
            {error && (
                <p id={errorId} className="mt-1.5 text-sm text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}

export function TextField({ label, error, hint, required, className, value, onChange, type = 'text', ...props }) {
    return (
        <Field label={label} error={error} hint={hint} required={required} className={className}>
            {({ id, describedBy, invalid }) => (
                <input
                    id={id}
                    type={type}
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                    aria-describedby={describedBy}
                    aria-invalid={invalid}
                    required={required}
                    className={inputClass}
                    {...props}
                />
            )}
        </Field>
    );
}

export function TextAreaField({ label, error, hint, required, className, value, onChange, rows = 4, ...props }) {
    return (
        <Field label={label} error={error} hint={hint} required={required} className={className}>
            {({ id, describedBy, invalid }) => (
                <textarea
                    id={id}
                    rows={rows}
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                    aria-describedby={describedBy}
                    aria-invalid={invalid}
                    required={required}
                    className={`${inputClass} py-3`}
                    {...props}
                />
            )}
        </Field>
    );
}

export function SelectField({ label, error, hint, required, className, value, onChange, options, placeholder, ...props }) {
    return (
        <Field label={label} error={error} hint={hint} required={required} className={className}>
            {({ id, describedBy, invalid }) => (
                <select
                    id={id}
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                    aria-describedby={describedBy}
                    aria-invalid={invalid}
                    required={required}
                    className={`${inputClass} pr-10`}
                    {...props}
                >
                    {placeholder !== undefined && <option value="">{placeholder}</option>}
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            )}
        </Field>
    );
}

/** Interruptor accesible (role="switch"). */
export function ToggleField({ label, description, checked, onChange, error }) {
    const id = useId();

    return (
        <div>
            <button
                type="button"
                role="switch"
                aria-checked={!!checked}
                aria-labelledby={`${id}-label`}
                aria-describedby={description ? `${id}-desc` : undefined}
                onClick={() => onChange(!checked)}
                className="flex min-h-touch w-full items-center justify-between gap-4 rounded-xl border border-line bg-surface px-4 py-2 text-left hover:bg-surface-sunken"
            >
                <span>
                    <span id={`${id}-label`} className="block text-sm font-medium text-ink">
                        {label}
                    </span>
                    {description && (
                        <span id={`${id}-desc`} className="block text-xs text-ink-muted">
                            {description}
                        </span>
                    )}
                </span>
                <span aria-hidden="true" className={`relative h-6 w-11 shrink-0 rounded-full transition ${checked ? 'bg-primary' : 'bg-line'}`}>
                    <span
                        className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all ${checked ? 'left-[1.375rem]' : 'left-0.5'}`}
                    />
                </span>
            </button>
            {error && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{error}</p>}
        </div>
    );
}
