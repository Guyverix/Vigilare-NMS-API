<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/trap",
 *     summary="Submit a new trap event",
 *     description="Receives a new trap via multipart form-data and processes it through validation and mapping.",
 *     tags={"Trap"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"eventName", "eventSummary"},
 *                 @OA\Property(property="endEvent", type="string", format="date-time", example="0000-00-00 00:00:00"),
 *                 @OA\Property(property="evid", type="string", example="64fa0b30b87c1"),
 *                 @OA\Property(property="eventSeverity", type="string", example="3", description="0=ok, 1=debug, ..., 5=critical"),
 *                 @OA\Property(property="eventReceiver", type="string", example="192.168.1.10"),
 *                 @OA\Property(property="eventSummary", type="string", example="Ping failed on service X"),
 *                 @OA\Property(property="eventName", type="string", example="ping_check"),
 *                 @OA\Property(property="eventType", type="string", example="3"),
 *                 @OA\Property(property="eventMonitor", type="string", example="3"),
 *                 @OA\Property(property="eventCounter", type="string", example="1"),
 *                 @OA\Property(property="eventAddress", type="string", example="10.0.0.45"),
 *                 @OA\Property(property="eventProxyIp", type="string", example="0.0.0.0"),
 *                 @OA\Property(property="device", type="string", example="host01.domain.local"),
 *                 @OA\Property(property="eventAgeOut", type="string", example="3600"),
 *                 @OA\Property(property="startEvent", type="string", format="date-time"),
 *                 @OA\Property(property="stateChange", type="string", format="date-time"),
 *                 @OA\Property(property="eventRaw", type="string", example="{eventName: ping_check }"),
 *                 @OA\Property(property="eventDetails", type="string", example="Ping Check Failure"),
 *                 @OA\Property(property="application", type="string", enum={"true", "false"}, example="false"),
 *                 @OA\Property(property="customerVisible", type="string", enum={"true", "false"}, example="false"),
 *                 @OA\Property(property="osEvent", type="string", enum={"true", "false"}, example="false")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Trap successfully created",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="string", example="abc123"),
 *             @OA\Property(property="eventName", type="string", example="ping_check"),
 *             @OA\Property(property="evid", type="string", example="64fa0b30b87c1"),
 *             @OA\Property(property="eventSeverity", type="string", example="3"),
 *             @OA\Property(property="eventSummary", type="string", example="Ping failed"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-15T10:15:00Z")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation failed"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */

Class Trap {}
