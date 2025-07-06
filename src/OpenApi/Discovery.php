<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/discovery/{action}",
 *     summary="Perform discovery-related operations",
 *     description="Handles discovery logic such as creating devices, folders, or templates, discovering SNMP data, pinging, and searching for templates. Requires a valid 'action' path parameter and optional POST form fields depending on the action type.",
 *     tags={"Discovery"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="Discovery action to perform. Valid values: create, discover, test, debug, ping, search.",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"create"},
 *                 @OA\Property(property="create", type="string", example="Device", description="Specifies what to create: Device, DeviceFolder, or DevicePropertiesTemplate"),
 *                 @OA\Property(property="id", type="integer", example=5, description="Device ID (required for 'discover')"),
 *                 @OA\Property(property="hostname", type="string", example="core-router"),
 *                 @OA\Property(property="address", type="string", example="192.168.1.1"),
 *                 @OA\Property(property="productionState", type="integer", example=1000),
 *                 @OA\Property(property="Class", type="string", example="Device"),
 *                 @OA\Property(property="Name", type="string", example="A_Default"),
 *                 @OA\Property(property="snmpVersions", type="string", example="2c"),
 *                 @OA\Property(property="snmpCommunities", type="string", example="public")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid or missing parameters"
 *     ),
 *     @OA\Response(
 *         response=501,
 *         description="Not implemented"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */


Class Discovery {}
