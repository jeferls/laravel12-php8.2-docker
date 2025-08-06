<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Saques-Back",
    version: "1.0.0",
    description: "Documentação OpenAPI com swagger-php"
)]
#[OA\Server(url: "/api")]
class OpenApiSpec {}
