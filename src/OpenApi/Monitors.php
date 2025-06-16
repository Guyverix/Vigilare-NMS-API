<?php
namespace App\OpenApi;
/**
 * @OA\Post(
 *     path="/monitors/{action}",
 *     summary="Manage monitoring configurations and queries",
 *     description="Performs monitoring-related actions such as creating monitors, assigning hosts or host groups, and querying monitor data. Valid actions include: createMonitor, updateMonitor, deleteMonitor, monitorAddHost, monitorDeleteHost, monitorAddHostgroup, monitorDeleteHostGroup, findMonitors, findMonitorsAll, findMonitorsDisable, findMonitorsByHostId, findMonitorsByCheckName, findMonitorType, findMonitorStorage, findMonitorIteration, findDeviceId, findHostGroup.",
 *     tags={"Monitors"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The monitor action to perform",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=101, description="Monitor ID"),
 *                 @OA\Property(property="hostId", type="string", example="12,15,23", description="Comma-separated or array of host IDs"),
 *                 @OA\Property(property="hostGroup", type="string", example="database-servers", description="Comma-separated or array of host group names"),
 *                 @OA\Property(property="checkName", type="string", example="http_check"),
 *                 @OA\Property(property="monitorType", type="string", example="ping"),
 *                 @OA\Property(property="interval", type="integer", example=60, description="Polling interval in seconds"),
 *                 @OA\Property(property="storageType", type="string", example="rrd"),
 *                 @OA\Property(property="iteration", type="integer", example=5)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Monitor action completed successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request (e.g. invalid action or missing required data)"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */


Class Monitors {}
