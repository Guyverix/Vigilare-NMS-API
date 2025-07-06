<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/monitoringpoller/{action}",
 *     summary="Perform monitoring poller actions",
 *     description="Used by pollers or backend systems to push or pull monitoring data. Valid actions include: isAlive, savePerformance, deletePerformance, heartbeat, hostname, hostgroup, walk, get, snmp, nrpe, ping, housekeeping, disable, all, alive, checkName, shell.",
 *     tags={"MonitoringPoller"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The monitoring poller action to perform",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="hostgroup", type="string", example="web-servers"),
 *                 @OA\Property(property="monitor", type="string", example="ping_check"),
 *                 @OA\Property(property="cycle", type="integer", example=5),
 *                 @OA\Property(property="hostname", type="string", example="host01.example.com"),
 *                 @OA\Property(property="idList", type="string", example="101,102,103"),
 *                 @OA\Property(property="status", type="string", example="ok"),
 *                 @OA\Property(property="performance", type="string", example="rta=0.45ms;100;200"),
 *                 @OA\Property(property="timestamp", type="string", example="2025-06-15 14:33:00"),
 *                 @OA\Property(property="heartbeat", type="string", example="alive")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Monitoring poller operation completed",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid action or malformed input"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */


Class MonitoringPoller {}
