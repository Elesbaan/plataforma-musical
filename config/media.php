<?php

return [

    /*
    | Tamaño máximo de un video subido por bloques (en MB).
    */
    'max_video_mb' => (int) env('MEDIA_MAX_VIDEO_MB', 500),

    /*
    | Tamaño objetivo de cada fragmento (en KB). Se reduce automáticamente si
    | upload_max_filesize o post_max_size del servidor son menores.
    */
    'chunk_kb' => (int) env('MEDIA_CHUNK_KB', 2048),

    /*
    | Extensiones de video permitidas y horas tras las cuales se eliminan
    | fragmentos de subidas abandonadas (comando media:clean-chunks).
    */
    'video_extensions' => ['mp4', 'webm', 'mov'],

    'stale_chunk_hours' => 24,

    /*
    | Fotos: peso máximo por archivo (en KB) y cantidad por envío.
    */
    'max_image_kb' => 10240,

    'max_images_per_upload' => 10,

];
