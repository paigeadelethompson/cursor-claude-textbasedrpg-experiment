<?php

/**
 * @OA\Get(
 *     path="/api/games",
 *     summary="List all games",
 *     tags={"Games"},
 *     @OA\Response(
 *         response=200,
 *         description="List of games",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Game"))
 *     )
 * )
 */
public function index() {}

/**
 * @OA\Post(
 *     path="/api/games",
 *     summary="Create a new game",
 *     tags={"Games"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name"},
 *             @OA\Property(property="name", type="string", example="New Adventure")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Game created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Game")
 *     )
 * )
 */
public function create() {}

/**
 * @OA\Get(
 *     path="/api/games/{id}",
 *     summary="Get game details",
 *     tags={"Games"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Game details",
 *         @OA\JsonContent(ref="#/components/schemas/Game")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Game not found"
 *     )
 * )
 */
public function show($id) {} 