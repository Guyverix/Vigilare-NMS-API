<?php
namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/mapping/{action}/{job}",
 *     summary="Run a global mapping job",
 *     description="Executes a global mapping action based on action/job type such as host, trap, poller, etc.",
 *     operationId="completeGlobalMapping",
 *     tags={"Global Mapping"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The mapping target to act on",
 *         @OA\Schema(type="string", enum={"host", "hostGroup", "hostAttribute", "trap", "poller", "template"})
 *     ),
 *     @OA\Parameter(
 *         name="job",
 *         in="path",
 *         required=true,
 *         description="The job to execute on the target",
 *         @OA\Schema(type="string", enum={"view", "create", "update", "delete", "find", "test"})
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="hostname", type="string", example="myhost.domain.com"),
 *                 @OA\Property(property="iteration", type="string", example="1"),
 *                 @OA\Property(property="oid", type="string", example="1.3.6.1.4.1.8072.3.2.10"),
 *                 @OA\Property(property="perfStorage", type="string", enum={"database", "graphite", "databaseMetric"}, example="graphite"),
 *                 @OA\Property(property="monitorName", type="string", example="check_disk"),
 *                 @OA\Property(property="monitorCommand", type="string", example="/usr/lib/nagios/plugins/check_disk -w 20% -c 10% -p /")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Job completed successfully",
 *         @OA\JsonContent(type="object")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid job or action type"
 *     )
 * )
 */

Class GlobalMapping {}
