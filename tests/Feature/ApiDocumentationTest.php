<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_documentation_page_is_available_in_local_environment(): void
    {
        $this->app['env'] = 'local';

        $this->get('/docs/api')
            ->assertOk()
            ->assertSee('Habit Tracker API');
    }

    public function test_documentation_is_forbidden_outside_local_environment(): void
    {
        $this->get('/docs/api')->assertForbidden();
        $this->get('/docs/api.json')->assertForbidden();
    }

    public function test_openapi_document_describes_api_routes(): void
    {
        $this->app['env'] = 'local';

        $this->getJson('/docs/api.json')
            ->assertOk()
            ->assertJsonPath('info.title', 'Habit Tracker API')
            ->assertJsonStructure([
                'paths' => ['/login', '/refresh', '/user'],
            ]);
    }

    public function test_openapi_document_marks_only_sanctum_routes_as_protected(): void
    {
        $this->app['env'] = 'local';

        $this->getJson('/docs/api.json')
            ->assertOk()
            ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
            ->assertJsonPath('security', [['http' => []]])
            ->assertJsonPath('paths./login.post.security', [])
            ->assertJsonPath('paths./refresh.post.security', [])
            ->assertJsonMissingPath('paths./user.get.security');
    }
}
