<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Leave Management System API",
    description: "API Documentation for Leave Management System",
    contact: new OA\Contact(email: "admin@example.com")
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    in: "header",
    name: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
abstract class Controller
{
    //
}
