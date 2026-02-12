<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Sahara Summit API",
    description: "API Documentation for Event Notification",
    contact: new OA\Contact(email: "support@saharasummit.com")
)]
#[OA\Server(url: 'http://127.0.0.1:8000', description: "Local Server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class OpenApi {}
