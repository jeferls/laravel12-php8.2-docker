<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ExchangeRate",
    type: "object",
    title: "Exchange Rate Resource",
    description: "Representação de uma taxa de câmbio"
)]
class ExchangeRateSchema
{
    #[OA\Property(type: "integer", example: 67)]
    public int $id;

    #[OA\Property(type: "string", example: "BRL")]
    public string $transaction_currency;

    #[OA\Property(type: "string", example: "Brazilian Real")]
    public string $currency_description;

    #[OA\Property(type: "string", example: "USD")]
    public string $merchant_funding_currency;

    #[OA\Property(type: "string", example: "US Dollar")]
    public string $merchant_funding_currency_description;

    #[OA\Property(type: "number", format: "float", example: 5.8266606)]
    public float $purchase_rate;

    #[OA\Property(type: "number", format: "float", example: 5.2979117)]
    public float $refund_rate;

    #[OA\Property(type: "string", format: "date", example: "2025-07-15")]
    public string $created_at;
}
