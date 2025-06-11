<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/auth/access_token",
 *     summary="Authenticate and retrieve a JWT token",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"username", "password"},
 *             @OA\Property(property="username", type="string"),
 *             @OA\Property(property="password", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Authentication successful - returns JWT"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials"
 *     )
 * )
 */
class Auth {}
