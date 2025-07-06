<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Vigilare NMS API",
 *     version="1.0.0",
 *     description="OpenAPI documentation for the Vigilare Network Monitoring System"
 * )
 *
 * @OA\Server(
 *     url="${API_FQDN}:${API_PORT}",
 *     description="Live server API list with https"
 * ),
 * @OA\Server(
 *     url="http://127.0.0.1:8002",
 *     description="Testing localhost server API list by IP with http.  If used port must be manually edited."
 * )
 */
class Meta {}
