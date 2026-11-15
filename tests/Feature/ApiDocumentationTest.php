<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function ambientes(): array
    {
        return [
            'local' => ['local'],
            'testing' => ['testing'],
            'production' => ['production'],
        ];
    }

    #[DataProvider('ambientes')]
    public function test_documentation_page_is_public_in_every_environment(string $ambiente): void
    {
        $this->app['env'] = $ambiente;

        $this->get('/docs/api')
            ->assertOk()
            ->assertSee('Habit Tracker API');
    }

    #[DataProvider('ambientes')]
    public function test_openapi_document_is_public_in_every_environment(string $ambiente): void
    {
        $this->app['env'] = $ambiente;

        $this->getJson('/docs/api.json')
            ->assertOk()
            ->assertJsonPath('info.title', 'Habit Tracker API');
    }
}
