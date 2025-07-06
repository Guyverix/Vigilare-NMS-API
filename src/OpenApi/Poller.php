<?php
namespace App\OpenApi;

/**
 * @OA\Get(
 *     path="/poller/{poller}/{state}",
 *     summary="Manage or query poller daemons",
 *     description="Starts, stops, queries, or checks heartbeat for poller daemons. Valid states: start, stop, status, iteration, heartbeat, list.",
 *     tags={"Poller"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="poller",
 *         in="path",
 *         required=true,
 *         description="Name of the poller to control or query (e.g., snmp, ping, smartPoller)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="state",
 *         in="path",
 *         required=true,
 *         description="Action to perform. Options: start, stop, restart, status, iteration, heartbeat, list",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Poller action completed successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid poller or state value"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */



Class Poller {}
