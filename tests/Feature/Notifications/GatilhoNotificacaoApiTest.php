<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GatilhoNotificacaoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_rotas_de_gatilhos_nao_estao_disponiveis(): void
    {
        Sanctum::actingAs(User::factory()->create());

        foreach ([
            ['GET', '/api/notifications/triggers'],
            ['POST', '/api/notifications/triggers'],
            ['GET', '/api/notifications/triggers/1'],
            ['PUT', '/api/notifications/triggers/1'],
            ['PATCH', '/api/notifications/triggers/1'],
            ['DELETE', '/api/notifications/triggers/1'],
        ] as [$metodo, $uri]) {
            $this->json($metodo, $uri)->assertNotFound();
        }
    }
}
