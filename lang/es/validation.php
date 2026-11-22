<?php

/*
| Mensajes de validación en español (México), con trato de "tú".
| Solo se incluyen las reglas que usa la aplicación; agrega más según se necesiten.
*/

return [
    'accepted' => 'Debes aceptar :attribute.',
    'after_or_equal' => ':Attribute debe ser una fecha igual o posterior a :date.',
    'array' => ':Attribute no tiene un formato válido.',
    'before_or_equal' => ':Attribute debe ser una fecha igual o anterior a :date.',
    'between' => [
        'numeric' => ':Attribute debe estar entre :min y :max.',
        'string' => ':Attribute debe tener entre :min y :max caracteres.',
        'array' => ':Attribute debe tener entre :min y :max elementos.',
        'file' => ':Attribute debe pesar entre :min y :max kilobytes.',
    ],
    'boolean' => ':Attribute debe ser sí o no.',
    'date' => ':Attribute no es una fecha válida.',
    'date_format' => ':Attribute no tiene el formato :format.',
    'decimal' => ':Attribute debe tener :decimal decimales.',
    'distinct' => ':Attribute tiene un valor repetido.',
    'email' => 'Escribe un correo electrónico válido.',
    'exists' => 'El valor elegido en :attribute no es válido.',
    'extensions' => ':Attribute debe tener una de estas extensiones: :values.',
    'file' => ':Attribute debe ser un archivo.',
    'gt' => [
        'numeric' => ':Attribute debe ser mayor que :value.',
    ],
    'gte' => [
        'numeric' => ':Attribute debe ser mayor o igual que :value.',
    ],
    'hex_color' => ':Attribute debe ser un color hexadecimal válido (ej. #6D28D9).',
    'image' => ':Attribute debe ser una imagen.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'lt' => [
        'numeric' => ':Attribute debe ser menor que :value.',
    ],
    'lte' => [
        'numeric' => ':Attribute debe ser menor o igual que :value.',
    ],
    'max' => [
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
        'array' => ':Attribute no puede tener más de :max elementos.',
        'file' => ':Attribute no puede pesar más de :max kilobytes.',
    ],
    'mimes' => ':Attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => ':Attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
        'array' => ':Attribute debe tener al menos :min elementos.',
        'file' => ':Attribute debe pesar al menos :min kilobytes.',
    ],
    'numeric' => ':Attribute debe ser un número.',
    'present' => ':Attribute es obligatorio.',
    'prohibited' => ':Attribute no está permitido.',
    'regex' => ':Attribute no tiene un formato válido.',
    'required' => ':Attribute es obligatorio.',
    'required_if' => ':Attribute es obligatorio cuando :other es :value.',
    'required_with' => ':Attribute es obligatorio cuando se indica :values.',
    'string' => ':Attribute debe ser texto.',
    'unique' => 'Ya existe un registro con ese valor en :attribute.',
    'uploaded' => 'No se pudo subir :attribute. Intenta de nuevo.',
    'url' => ':Attribute debe ser una dirección web válida (https://...).',
    'uuid' => ':Attribute no es válido.',

    'custom' => [],

    'attributes' => [
        // Generales
        'name' => 'el nombre',
        'email' => 'el correo electrónico',
        'password' => 'la contraseña',
        'description' => 'la descripción',
        'sort_order' => 'el orden',
        'is_active' => 'el estado',

        // Identidad y marca
        'brand_name' => 'el nombre comercial',
        'tagline' => 'el eslogan',
        'seo_description' => 'la descripción para buscadores',
        'logo_light' => 'el logo para tema claro',
        'logo_dark' => 'el logo para tema oscuro',
        'favicon' => 'el ícono del sitio',
        'color_primary' => 'el color primario',
        'color_secondary' => 'el color secundario',
        'color_accent' => 'el color de acento',
        'whatsapp_number' => 'el número de WhatsApp',
        'phone_number' => 'el teléfono',
        'contact_email' => 'el correo de contacto',
        'address_text' => 'la dirección',
        'facebook_url' => 'el enlace de Facebook',
        'instagram_url' => 'el enlace de Instagram',
        'tiktok_url' => 'el enlace de TikTok',
        'youtube_url' => 'el enlace de YouTube',
        'spotify_url' => 'el enlace de Spotify',
        'online_discount_type' => 'el tipo de descuento',
        'online_discount_value' => 'el valor del descuento',
        'online_discount_min_amount' => 'el monto mínimo',
        'quote_validity_days' => 'la vigencia de la cotización',
        'quote_terms_text' => 'los términos y condiciones',
        'hero_title' => 'el título principal',
        'hero_subtitle' => 'el subtítulo principal',
        'hero_video_url' => 'el video de portada',

        // Zonas
        'city_name' => 'la ciudad',
        'state_name' => 'el estado',
        'zone_type' => 'el tipo de zona',
        'travel_fee_type' => 'el tipo de viáticos',
        'travel_fee_value' => 'el monto de viáticos',
        'notes' => 'las notas',

        // Catálogo
        'icon' => 'el ícono',
        'category_id' => 'la categoría',
        'short_description' => 'la descripción corta',
        'full_description' => 'la descripción completa',
        'service_type' => 'el tipo de servicio',
        'base_price' => 'el precio base',
        'price_unit' => 'la unidad de precio',
        'daily_capacity' => 'la capacidad diaria',
        'features' => 'las características',
        'features.*' => 'la característica',

        // Multimedia
        'images' => 'las fotos',
        'images.*' => 'la foto',
        'embed_url' => 'el enlace del video',
        'title' => 'el título',
        'chunk' => 'el fragmento',
        'file_name' => 'el nombre del archivo',
        'total_size' => 'el tamaño del archivo',
    ],
];
