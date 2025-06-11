<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/auth/access_token",
 *     summary="Authenticate and retrieve a JWT token",
 *     description="Accepts multipart form data with username and password
- API server should be in HTTPS mode if any part can be reached from public internet in ANY way
- Always avoid straight HTTP mode.  It is supported but a horrible idea
- Never ever use HTTP mode even when behind a proxy if you can help it
- Are you sensing a pattern?",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         content={
 *             @OA\MediaType(
 *                 mediaType="multipart/form-data",
 *                 @OA\Schema(
 *                     type="object",
 *                     required={"username", "password"},
 *                     properties={
 *                         @OA\Property(property="username", type="string"),
 *                         @OA\Property(property="password", type="string", format="password")
 *                     }
 *                 )
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Authentication successful - returns JWT"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials"
 *     )
 * )
 */

class Auth {}
