<?php
namespace App\OpenApi;

/**
 * @OA\Post(
 *     path="/infrastructure/{action}",
 *     summary="Perform infrastructure-related operations",
 *     description="Handles a variety of infrastructure management tasks based on the 'action' parameter, such as creating, updating, deleting, and listing hosts and categories.",
 *     tags={"Infrastructure"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="action",
 *         in="path",
 *         required=true,
 *         description="The action to perform. Options: findChildren, findChildrenOfParent, findOrphans, newHost, updateHost, deleteHost, findCategory, newCategory, updateCategory, validateCategoryBeforeDelete, validateHostBeforeDelete, deleteCategory.",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="hostname", type="string", example="server01"),
 *                 @OA\Property(property="category_id", type="integer", example=3),
 *                 @OA\Property(property="parent_id", type="integer", example=1),
 *                 @OA\Property(property="product_id", type="integer", example=5),
 *                 @OA\Property(property="category_name", type="string", example="Datacenter 1")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful infrastructure action",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input or missing fields"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */


Class Infrastructure {}
