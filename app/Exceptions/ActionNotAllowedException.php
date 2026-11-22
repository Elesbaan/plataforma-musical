<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Acción bloqueada por una regla de negocio (ej. eliminar una categoría con
 * servicios). El mensaje se muestra tal cual al usuario como aviso flash.
 */
class ActionNotAllowedException extends RuntimeException {}
