<?php

return [

    /*
    | Prefijo del folio secuencial: COT-YYYY-NNNN.
    */
    'folio_prefix' => 'COT',

    /*
    | Tipos de evento que se ofrecen en el cotizador (se validan en backend).
    */
    'event_types' => [
        'Boda',
        'XV años',
        'Evento corporativo',
        'Graduación',
        'Cumpleaños',
        'Aniversario',
        'Otro',
    ],

    /*
    | Límites por partida: unidades de un servicio y horas extra.
    */
    'max_quantity' => 20,

    'max_extra_hours' => 12,

    'max_items' => 30,

    /*
    | Leyenda cuando la zona elegida es "otra ciudad" (custom_quote) y no tiene nota propia.
    */
    'travel_pending_label' => 'Viáticos por confirmar según ubicación exacta',

];
