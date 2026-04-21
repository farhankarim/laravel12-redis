<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'University Management API',
    description: 'REST API for the Laravel 12 University Management System. All protected endpoints require a Sanctum Bearer token obtained from POST /api/auth/login.',
    contact: new OA\Contact(email: 'admin@example.com')
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
    description: "Enter your Sanctum API token (without the 'Bearer ' prefix)"
)]
abstract class Controller
{
    //
}
