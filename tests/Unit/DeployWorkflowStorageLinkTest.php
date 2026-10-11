<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Uploaded files on the 'public' disk are served from /storage/... through
 * the public/storage symlink, which isn't in git. Both deploy workflows must
 * keep their rsync --delete from wiping it and must (re)create it, or every
 * uploaded photo 404s on the servers even though it was saved.
 */
class DeployWorkflowStorageLinkTest extends TestCase
{
    public static function workflows(): array
    {
        return [
            'production' => ['.github/workflows/deploy.yml'],
            'staging'    => ['.github/workflows/deploy-staging.yml'],
        ];
    }

    private function read(string $path): string
    {
        $full = dirname(__DIR__, 2) . '/' . $path;
        $this->assertFileExists($full);

        return file_get_contents($full);
    }

    #[DataProvider('workflows')]
    public function test_rsync_does_not_delete_the_public_storage_link(string $path): void
    {
        $this->assertStringContainsString("--exclude='/public/storage'", $this->read($path));
    }

    #[DataProvider('workflows')]
    public function test_deploy_creates_the_storage_link_after_syncing(string $path): void
    {
        $yaml = $this->read($path);

        $sync = strpos($yaml, 'rsync -a --delete');
        $link = strpos($yaml, 'php artisan storage:link --force');

        $this->assertNotFalse($sync);
        $this->assertNotFalse($link, 'deploy never runs php artisan storage:link');
        $this->assertGreaterThan($sync, $link, 'storage:link must run after the rsync');
    }
}
