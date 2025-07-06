<?php
namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/snmp/oid",
 *     summary="Retrieve a single SNMP OID value",
 *     description="Returns the value of a specific SNMP OID for a target host. Only SNMP v1 and v2c are supported.",
 *     operationId="getSnmpOid",
 *     tags={"SNMP"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"hostname", "oid"},
 *                 @OA\Property(property="hostname", type="string", example="router1.local"),
 *                 @OA\Property(property="oid", type="string", example="1.3.6.1.2.1.1.5.0"),
 *                 @OA\Property(property="community", type="string", example="public"),
 *                 @OA\Property(property="version", type="string", enum={"1", "2", "2c"}, example="2c")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="SNMP OID value successfully retrieved",
 *         @OA\JsonContent(type="object")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input or unsupported SNMP version"
 *     ),
 *     @OA\Response(
 *         response=501,
 *         description="SNMP v3 is not supported"
 *     )
 * )
 */


Class SnmpOid {}
