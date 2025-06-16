<?php
namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/snmp/table",
 *     summary="Retrieve an SNMP table",
 *     description="Returns a full SNMP table for a given OID on a target host. Only SNMP v1 and v2c are supported.",
 *     operationId="getSnmpTable",
 *     tags={"SNMP"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"hostname", "oid"},
 *                 @OA\Property(property="hostname", type="string", example="router1.local"),
 *                 @OA\Property(property="oid", type="string", example="1.3.6.1.2.1.2.2"),
 *                 @OA\Property(property="community", type="string", example="public"),
 *                 @OA\Property(property="version", type="string", enum={"1", "2", "2c"}, example="2")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="SNMP table successfully retrieved",
 *         @OA\JsonContent(type="object")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input or internal error"
 *     ),
 *     @OA\Response(
 *         response=501,
 *         description="SNMP v3 is not supported"
 *     )
 * )
 */

Class SnmpTable {}
