<?php

namespace Tests\Concerns;

/**
 * Percorre o ciclo CRUD completo de um recurso da API:
 * cria, lista, consulta, edita parcialmente, exclui e confirma o 404.
 */
trait VerificaCrudDaApi
{
    /**
     * @param  array<string, mixed>  $criacao  corpo do POST
     * @param  array<string, mixed>  $edicao  corpo do PATCH (atualização parcial)
     */
    protected function verificarCrud(string $uri, array $criacao, array $edicao): void
    {
        $id = $this->postJson($uri, $criacao)
            ->assertCreated()
            ->assertJson(['data' => $criacao])
            ->json('data.id');

        $this->getJson($uri)->assertOk()->assertJsonFragment(['id' => $id]);
        $this->getJson("{$uri}/{$id}")->assertOk()->assertJsonPath('data.id', $id);

        $this->patchJson("{$uri}/{$id}", $edicao)
            ->assertOk()
            ->assertJson(['data' => [...$criacao, ...$edicao]]);

        $this->deleteJson("{$uri}/{$id}")->assertNoContent();
        $this->getJson("{$uri}/{$id}")->assertNotFound();
    }
}
