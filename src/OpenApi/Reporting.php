<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/reporting/{action}",
 *     summary="Manage reporting jobs and templates",
 *     description="Handles dynamic reporting operations such as test, run, purge, createReport, runPending, etc.",
 *     tags={"Reporting"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The action to perform. Valid options: test, purge, run, searchComplete, viewComplete, searchTemplate, createReport, findPending, runPending",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="reportId", type="integer", example=42, description="The report ID to run/view/delete"),
 *                 @OA\Property(property="templateName", type="string", example="Weekly Uptime Report"),
 *                 @OA\Property(property="hostFilter", type="string", example="host01"),
 *                 @OA\Property(property="rangeStart", type="string", format="date-time", example="2024-06-01T00:00:00Z"),
 *                 @OA\Property(property="rangeEnd", type="string", format="date-time", example="2024-06-07T00:00:00Z"),
 *                 @OA\Property(property="format", type="string", example="pdf"),
 *                 @OA\Property(property="email", type="string", format="email", example="admin@example.com")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Report operation completed successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request, invalid action or missing data"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */

Class Reporting {}
