<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/device/{action}",
 *     summary="Manage devices and device groups",
 *     description="Manages device records via multiple sub-actions: view, create, update, delete, test, find, debug, properties, performance.",
 *     tags={"Device"},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="Action to perform. Options: view, create, update, delete, test, find, debug, properties, performance",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="hostname", type="string", example="host01.example.com"),
 *                 @OA\Property(property="address", type="string", format="ipv4", example="192.168.0.10"),
 *                 @OA\Property(property="productionState", type="string", example="1"),
 *                 @OA\Property(property="deviceGroup", type="string", example="web-servers"),
 *                 @OA\Property(property="component", type="string", example="eth0"),
 *                 @OA\Property(property="id", type="integer", example=123),
 *                 @OA\Property(property="deviceInDeviceGroup", type="string", example="yes"),
 *                 @OA\Property(property="deviceGroupMonitors", type="string", example="cpu,mem")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Result of the device operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid action or missing required parameters"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */





Class Device {}
