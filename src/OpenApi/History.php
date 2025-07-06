<?php
namespace App\OpenApi;

/**
 * @OA\Get(
 *     path="/history/{action}",
 *     summary="Perform various history queries",
 *     description="This endpoint handles multiple history actions like `view`, `viewAll`, `viewLimit`, `viewTable`, `findId`, `countHistoryAllHostsSeen`, and `historyEventCount`.",
 *     tags={"History"},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The history action to perform. Options: view, viewAll, viewLimit, viewTable, findId, countHistoryAllHostsSeen, historyEventCount",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="column",
 *         in="path",
 *         required=false,
 *         description="Column value or ID, depending on action (used with view, findId, viewTable, viewLimit)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="direction",
 *         in="path",
 *         required=false,
 *         description="Sort direction for column (used with 'view' action)",
 *         @OA\Schema(type="string", enum={"asc", "desc"})
 *     ),
 *     @OA\Parameter(
 *         name="filter",
 *         in="path",
 *         required=false,
 *         description="Optional filter for the view query (used with 'view' action)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful result for the requested history operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request (invalid action or argument)"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */



Class History {}
