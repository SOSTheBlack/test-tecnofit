<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use HyperfTest\TestCase;

class IndexControllerTest extends TestCase
{
    public function testIndexEndpoint()
    {
        $response = $this->get('/api/');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'version',
            'timestamp',
            'endpoints',
        ]);
        $response->assertJson([
            'message' => 'Tecnofit Pix API',
            'version' => '1.0.0',
        ]);
    }

    public function testHealthEndpoint()
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database' => ['status', 'message'],
                'redis' => ['status', 'message'],
            ],
        ]);
    }
}
