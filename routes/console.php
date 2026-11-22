<?php

use Illuminate\Support\Facades\Schedule;

/*
| Tareas programadas. En el hosting compartido se ejecutan con un Cron cada minuto:
|     * * * * * php /ruta/al/proyecto/artisan schedule:run >> /dev/null 2>&1
*/

// Limpia fragmentos de subidas de video abandonadas.
Schedule::command('media:clean-chunks')->daily();
