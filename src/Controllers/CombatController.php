<?php

/**
 * @OA\Post(
 *     path="/api/combat/start",
 *     summary="Start a combat session",
 *     tags={"Combat"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"character_id", "opponent_id"},
 *             @OA\Property(property="character_id", type="integer"),
 *             @OA\Property(property="opponent_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Combat session started",
 *         @OA\JsonContent(
 *             @OA\Property(property="combat_id", type="string"),
 *             @OA\Property(property="websocket_url", type="string")
 *         )
 *     )
 * )
 */
public function startCombat() {} 