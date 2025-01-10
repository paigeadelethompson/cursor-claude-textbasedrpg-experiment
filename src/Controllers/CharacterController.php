<?php

/**
 * @OA\Get(
 *     path="/api/characters",
 *     summary="List player characters",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of characters",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Character"))
 *     )
 * )
 */
public function index() {}

/**
 * @OA\Post(
 *     path="/api/characters",
 *     summary="Create new character",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "class"},
 *             @OA\Property(property="name", type="string", example="Warrior123"),
 *             @OA\Property(property="class", type="string", enum={"warrior", "mage", "rogue"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Character created",
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     )
 * )
 */
public function create() {}

/**
 * @OA\Put(
 *     path="/api/characters/{id}/level-up",
 *     summary="Level up a character",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Character leveled up",
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     )
 * )
 */
public function levelUp($id) {} 