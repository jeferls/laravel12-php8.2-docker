<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExchangeRatesTest extends TestCase
{
    public function test_returns_exchange_rates()
    {
        $response = $this->get('/api/exchange-rates');

        $response->assertOk();
    }
}
