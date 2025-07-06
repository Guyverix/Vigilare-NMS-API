<?php
//declare(strict_types=1);

namespace App\OpenApi;

/**
 * @\OpenApi\Annotations\Get(
 *     path="/events/{action}",
 *     summary="Perform an event action (GET)",
 *     description="Retrieves event data for the specified action.",
 *     tags={"Events"},
 *     @\OpenApi\Annotations\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The event action to execute.",
 *         @\OpenApi\Annotations\Schema(
 *             type="string",
 *             enum={
 *                 "view", "viewAll", "viewTable", "findId", "findAliveTime",
 *                 "findEventTime", "findHistoryTime", "countEventAllHostsSeen",
 *                 "activeEventCount", "activeEventCountList", "historyEventCount",
 *                 "countEventEventHostsSeen", "monitorList", "ageOut",
 *                 "moveToHistory", "moveFromHistory", "findActiveEventByHostname",
 *                 "findClosedEventByHostname", "findActiveEventByDeviceId",
 *                 "findHistoryEventByDeviceId"
 *             }
 *         )
 *     ),
 *     @\OpenApi\Annotations\Response(response=200, description="Successful response"),
 *     @\OpenApi\Annotations\Response(response=400, description="Invalid request")
 * )
 *
 * @\OpenApi\Annotations\Post(
 *     path="/events/{action}",
 *     summary="Perform an event action (POST)",
 *     description="Executes an event action. Supports both multipart form and JSON input.",
 *     tags={"Events"},
 *     @\OpenApi\Annotations\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The event action to execute.",
 *         @\OpenApi\Annotations\Schema(
 *             type="string",
 *             enum={
 *                 "view", "viewAll", "viewTable", "findId", "findAliveTime",
 *                 "findEventTime", "findHistoryTime", "countEventAllHostsSeen",
 *                 "activeEventCount", "activeEventCountList", "historyEventCount",
 *                 "countEventEventHostsSeen", "monitorList", "ageOut",
 *                 "moveToHistory", "moveFromHistory", "findActiveEventByHostname",
 *                 "findClosedEventByHostname", "findActiveEventByDeviceId",
 *                 "findHistoryEventByDeviceId"
 *             }
 *         )
 *     ),
 *     @\OpenApi\Annotations\RequestBody(
 *         required=false,
 *         content={
 *             @\OpenApi\Annotations\MediaType(
 *                 mediaType="multipart/form-data",
 *                 @\OpenApi\Annotations\Schema(
 *                     type="object",
 *                     properties={
 *                         @\OpenApi\Annotations\Property(property="id", type="integer"),
 *                         @\OpenApi\Annotations\Property(property="reason", type="string")
 *                     }
 *                 )
 *             ),
 *             @\OpenApi\Annotations\MediaType(
 *                 mediaType="application/json",
 *                 @\OpenApi\Annotations\Schema(
 *                     type="object",
 *                     properties={
 *                         @\OpenApi\Annotations\Property(property="id", type="integer"),
 *                         @\OpenApi\Annotations\Property(property="reason", type="string")
 *                     }
 *                 )
 *             )
 *         }
 *     ),
 *     @\OpenApi\Annotations\Response(response=200, description="Event action completed"),
 *     @\OpenApi\Annotations\Response(response=400, description="Bad request")
 * )
 */
class Events {}
