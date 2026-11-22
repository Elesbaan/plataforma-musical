import { useEffect, useRef } from 'react';

function Checkbox({ checked, indeterminate = false, disabled, onChange, label, description, id }) {
    const ref = useRef(null);

    useEffect(() => {
        if (ref.current) ref.current.indeterminate = indeterminate;
    }, [indeterminate]);

    return (
        <label
            htmlFor={id}
            className={`flex min-h-touch items-start gap-3 rounded-xl px-3 py-2 ${disabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer hover:bg-surface-sunken'}`}
        >
            <input
                ref={ref}
                id={id}
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={(e) => onChange(e.target.checked)}
                className="mt-0.5 h-5 w-5 shrink-0 rounded border-line text-primary focus:ring-primary"
            />
            <span className="min-w-0">
                <span className="block text-sm font-medium text-ink">{label}</span>
                {description && <span className="block font-mono text-xs text-ink-muted">{description}</span>}
            </span>
        </label>
    );
}

/**
 * Matriz de permisos agrupada por módulo, con selección por módulo y global.
 *
 * @param {{ groups: Array<{module: string, label: string, permissions: Array<{key: string, label: string}>}>, value: string[], onChange: (keys: string[]) => void, disabled?: boolean }} props
 */
export default function PermissionMatrix({ groups, value, onChange, disabled = false }) {
    const selected = new Set(value);
    const allKeys = groups.flatMap((group) => group.permissions.map((p) => p.key));

    const setKeys = (keys, enabled) => {
        const next = new Set(selected);
        keys.forEach((key) => (enabled ? next.add(key) : next.delete(key)));
        onChange(allKeys.filter((key) => next.has(key)));
    };

    const allChecked = allKeys.length > 0 && allKeys.every((key) => selected.has(key));
    const someChecked = allKeys.some((key) => selected.has(key));

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-line bg-surface-sunken">
                <Checkbox
                    id="perm-all"
                    label={`Seleccionar todos los permisos (${selected.size} de ${allKeys.length})`}
                    checked={allChecked}
                    indeterminate={someChecked && !allChecked}
                    disabled={disabled}
                    onChange={(checked) => setKeys(allKeys, checked)}
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                {groups.map((group) => {
                    const keys = group.permissions.map((p) => p.key);
                    const count = keys.filter((key) => selected.has(key)).length;
                    return (
                        <fieldset key={group.module} className="rounded-xl border border-line p-2">
                            <legend className="sr-only">{group.label}</legend>
                            <div className="border-b border-line pb-1">
                                <Checkbox
                                    id={`perm-module-${group.module}`}
                                    label={`${group.label} (${count}/${keys.length})`}
                                    checked={count === keys.length}
                                    indeterminate={count > 0 && count < keys.length}
                                    disabled={disabled}
                                    onChange={(checked) => setKeys(keys, checked)}
                                />
                            </div>
                            <div className="pt-1">
                                {group.permissions.map((permission) => (
                                    <Checkbox
                                        key={permission.key}
                                        id={`perm-${permission.key}`}
                                        label={permission.label}
                                        description={permission.key}
                                        checked={selected.has(permission.key)}
                                        disabled={disabled}
                                        onChange={(checked) => setKeys([permission.key], checked)}
                                    />
                                ))}
                            </div>
                        </fieldset>
                    );
                })}
            </div>
        </div>
    );
}
