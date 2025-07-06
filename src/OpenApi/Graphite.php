<?php
namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/graphite/{filter}",
 *     summary="Retrieve Graphite metrics or construct render URLs",
 *     description="Handles Graphite render URL generation or metric searches depending on job type",
 *     operationId="viewGraphite",
 *     tags={"Graphite"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="filter",
 *         in="path",
 *         required=true,
 *         description="Hostname or FQDN used as the filter for metric lookups",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="prefix", type="string", example="nms"),
 *                 @OA\Property(property="from", type="string", example="-6h"),
 *                 @OA\Property(property="to", type="string", example="-1m"),
 *                 @OA\Property(property="width", type="string", example="586"),
 *                 @OA\Property(property="height", type="string", example="308"),
 *                 @OA\Property(property="return", type="string", example="json"),
 *                 @OA\Property(property="check", type="string", example="disk"),
 *                 @OA\Property(
 *                     property="job",
 *                     type="string",
 *                     description="What job should be run. Options: source, check, single, createUrl, dumbSearch",
 *                     enum={"source", "check", "single", "createUrl", "dumbSearch"},
 *                     example="check"
 *                 ),
 *                 @OA\Property(property="hostname", type="string", example="host.domain.com")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success, metric or URL list returned",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input or job type not recognized"
 *     )
 * )
 */

Class Graphite {}
