<?php
namespace App\OpenApi;

/**
 * @OA\Get(
 *     path="/events/{action}",
 *     summary="View filtered event list",
 *     tags={"Events"},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="Type of event list to retrieve",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Filtered event list"
 *     )
 * )
 */
class Events {}
