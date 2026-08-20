<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every page in the app, rendered. This is the net under the UI
 * standardization work: a shared layout or component change that breaks one
 * page breaks it here first.
 *
 * Only parameterless GET routes that return a view are covered — routes with
 * bound models need fixtures and are tested by their own module's tests. The
 * list comes from the router, so a new page is covered the day it ships.
 */
class AllPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    /** Routes that are not pages, or that need a payload to reach. */
    private const SKIP = [
        'login', 'logout', 'login.preview.1a', 'login.preview.1b',
    ];

    private function pageRoutes(): array
    {
        $names = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name
                || in_array($name, self::SKIP, true)
                || ! in_array('GET', $route->methods(), true)
                || str_contains($route->uri(), '{')
                || str_starts_with($name, 'generated::')
                || str_starts_with($name, 'export.')
                || str_ends_with($name, '.export')
                || str_ends_with($name, '.download')
                || str_contains($name, '.pdf')
                || str_contains($name, 'template')
                || str_contains($name, 'search')) {
                continue;
            }

            $names[] = $name;
        }

        sort($names);

        return $names;
    }

    public function test_every_page_renders_without_error(): void
    {
        $routes = $this->pageRoutes();
        $this->assertGreaterThan(20, count($routes), 'Route discovery found suspiciously few pages.');

        $failures = [];

        foreach ($routes as $name) {
            try {
                $status = $this->get(route($name))->getStatusCode();
            } catch (\Throwable $e) {
                $failures[] = "{$name}: threw ".class_basename($e).' — '.$e->getMessage();

                continue;
            }

            if (! in_array($status, [200, 302], true)) {
                $failures[] = "{$name}: HTTP {$status}";
            }
        }

        $this->assertSame([], $failures, "Pages failed to render:\n  ".implode("\n  ", $failures));
    }

    /**
     * The layout contract every page depends on. If a page renders but loses
     * the shell, it stops looking like the rest of the app.
     */
    public function test_every_page_renders_inside_the_shell(): void
    {
        $failures = [];

        foreach ($this->pageRoutes() as $name) {
            $response = $this->get(route($name));

            if ($response->getStatusCode() !== 200) {
                continue;
            }

            $html = $response->getContent();

            foreach ([
                'body class="app-shell'   => 'shell body class',
                'class="shell-sidebar"'   => 'sidebar',
                'class="shell-topbar"'    => 'top bar',
                'class="page-content shell-content"' => 'content column',
            ] as $needle => $label) {
                if (! str_contains($html, $needle)) {
                    $failures[] = "{$name}: missing {$label}";
                }
            }
        }

        $this->assertSame([], $failures, "Shell missing on:\n  ".implode("\n  ", $failures));
    }
}
