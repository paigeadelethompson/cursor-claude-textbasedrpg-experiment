<?php

/**
 * @OA\Schema(
 *     schema="Game",
 *     required={"id", "name", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Epic Quest"),
 *     @OA\Property(property="status", type="string", enum={"active", "completed", "paused"}),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */ 