<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/rendergraph/{action}",
 *     summary="Render and manage graph data sources",
 *     description="Handles graph rendering and lookup actions. Valid actions: render, delete, link, debug, metrics, findRrd, findGraphite, findRrdTemplates.",
 *     tags={"RenderGraph"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The graph-related action to perform",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="host", type="string", example="web01"),
 *                 @OA\Property(property="metric", type="string", example="cpu.usage"),
 *                 @OA\Property(property="template", type="string", example="cpu_template"),
 *                 @OA\Property(property="graphType", type="string", example="line"),
 *                 @OA\Property(property="startTime", type="string", example="-1h", description="RRD start time offset"),
 *                 @OA\Property(property="endTime", type="string", example="now"),
 *                 @OA\Property(property="output", type="string", example="png", description="Graph output format"),
 *                 @OA\Property(property="target", type="string", example="graphite.target.string")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Graph operation completed",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object", description="Results such as rendered graph URLs or available RRDs")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid request or unsupported action"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */


Class RenderGraph {}
