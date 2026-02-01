<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateGoogleCredentialsCommand extends Command
{
  protected $signature = 'google:migrate-credentials';

  protected $description = 'Migrate Google credentials and tokens from etc/ to storage/app/google/';

  public function handle(): int
  {
    $this->info('Migrating Google credentials and tokens to Laravel storage...');
    $this->newLine();

    $disk = Storage::disk('local');
    $migrated = 0;
    $skipped = 0;

    // Migrate credentials
    $etcPath = base_path('etc');
    if (is_dir($etcPath)) {
      $files = glob($etcPath . '/credentials*.json');

      foreach ($files as $file) {
        $filename = basename($file);
        $newPath = "google/{$filename}";

        if ($disk->exists($newPath)) {
          $this->warn("⚠ Skipped {$filename} (already exists in storage)");
          $skipped++;
          continue;
        }

        $content = file_get_contents($file);
        $disk->put($newPath, $content);
        $this->info("✓ Migrated {$filename}");
        $migrated++;
      }
    }

    // Migrate tokens (from storage/app/tokens/ to storage/app/google/tokens/)
    $oldTokenPath = storage_path('app/tokens');
    if (is_dir($oldTokenPath)) {
      $files = glob($oldTokenPath . '/token_*.json');

      foreach ($files as $file) {
        $filename = basename($file);
        $newPath = "google/tokens/{$filename}";

        if ($disk->exists($newPath)) {
          $this->warn("⚠ Skipped {$filename} (already exists)");
          $skipped++;
          continue;
        }

        $content = file_get_contents($file);
        $disk->put($newPath, $content);
        $this->info("✓ Migrated {$filename}");
        $migrated++;
      }
    }

    $this->newLine();
    $this->info("Migration complete!");
    $this->line("  Migrated: {$migrated} file(s)");
    $this->line("  Skipped: {$skipped} file(s)");

    if ($migrated > 0) {
      $this->newLine();
      $this->comment('Note: Original files in etc/ were not deleted.');
      $this->comment('You can safely delete them after verifying the migration.');
    }

    return Command::SUCCESS;
  }
}
