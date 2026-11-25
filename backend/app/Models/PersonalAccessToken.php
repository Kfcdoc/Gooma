<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use MongoDB\Laravel\Eloquent\DocumentModel;

/**
 * Token de acceso personal de Sanctum almacenado en MongoDB.
 *
 * Extiende el modelo de Sanctum (su clase NewAccessToken exige ese tipo)
 * y le aplica el trait DocumentModel del paquete de MongoDB para que toda
 * la persistencia y la búsqueda por token usen la conexión `mongodb`.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use DocumentModel;

    protected $connection = 'mongodb';

    protected $collection = 'personal_access_tokens';

    protected $keyType = 'string';
}
