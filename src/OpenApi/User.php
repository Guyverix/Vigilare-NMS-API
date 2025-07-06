<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/user/{job}",
 *     summary="Execute a user-related job (create, update, delete)",
 *     tags={"User"},
 *     @OA\Parameter(
 *         name="job",
 *         in="path",
 *         required=true,
 *         description="User job to perform",
 *         @OA\Schema(type="string", enum={"create", "update", "delete"})
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="username", type="string"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="roles", type="array", @OA\Items(type="string"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User job completed"
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized access"
 *     )
 * )
 */
class User {}
