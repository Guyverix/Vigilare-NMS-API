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
 *     url="https://larvel01.iwillfearnoevil.com:8002",
 *     description="Live server API list with https"
 * ),
 * @OA\Server(
 *     url="http://192.168.15.99:8002",
 *     description="Testing server API list by IP with http"
 * )
 */
class Meta {}
