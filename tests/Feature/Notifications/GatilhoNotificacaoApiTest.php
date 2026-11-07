<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\VerificaCrudDaApi;
use Tests\TestCase;

class GatilhoNotificacaoApiTest extends TestCase
{
    use RefreshDatabase, VerificaCrudDaApi;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_crud_de_gatilhos(): void
    {
        $this->verificarCrud('/api/notifications/triggers', [
            'tipo' => 'LEMBRETE_DIARIO',
            'canal' => 'PUSH',
            'frequencia' => 'DIARIA',
            'texto_base' => 'Hora de cuidar dos seus hábitos!',
        ], ['canal' => 'IN_APP']);
    }

    public function test_valida_canal(): void
    {
        $this->postJson('/api/notifications/triggers', [
            'tipo' => 'LEMBRETE_DIARIO',
            'canal' => 'EMAIL',
            'frequencia' => 'DIARIA',
            'texto_base' => 'Oi',
        ])->assertUnprocessable()->assertJsonValidationErrors(['canal']);
    }
}
