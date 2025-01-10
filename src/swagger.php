<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="RPG Game API",
 *     version="1.0.0",
 *     description="API documentation for the RPG Game"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local development server"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */ 