import {
    Camera,
    Crown,
    Drum,
    Gem,
    Guitar,
    Heart,
    Lightbulb,
    Mic,
    MicVocal,
    Music,
    PartyPopper,
    Piano,
    Sparkles,
    Speaker,
    Star,
    Users,
    Wine,
} from 'lucide-react';

/** Etiquetas de interfaz para los tipos de servicio (valores en Service::TYPES). */
export const SERVICE_TYPES = [
    { value: 'core_band', label: 'Alineación principal' },
    { value: 'ceremony', label: 'Ceremonia' },
    { value: 'soloist', label: 'Solista o dueto' },
    { value: 'partner_act', label: 'Aliado (mariachi, banda, DJ…)' },
    { value: 'addon_extra', label: 'Amenidad o extra' },
];

export function serviceTypeLabel(value) {
    return SERVICE_TYPES.find((type) => type.value === value)?.label ?? value;
}

/** Iconos disponibles para categorías (se guarda el nombre). */
export const CATEGORY_ICONS = {
    Music,
    MicVocal,
    Mic,
    Guitar,
    Piano,
    Drum,
    Heart,
    Sparkles,
    Speaker,
    Lightbulb,
    PartyPopper,
    Star,
    Crown,
    Gem,
    Wine,
    Camera,
    Users,
};

/** Nombres antiguos de Lucide que pueden venir de datos existentes. */
const ICON_ALIASES = { Mic2: 'MicVocal' };

export function categoryIcon(name) {
    return CATEGORY_ICONS[ICON_ALIASES[name] ?? name] ?? Music;
}
