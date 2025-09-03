<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BrandColorService
{
    /**
     * Update SCSS files with new brand color
     */
    public function updateBrandColor(string $color): bool
    {
        try {
            // Paths to the SCSS files
            $themeFiles = [
                resource_path('assets/vendor/scss/theme-default.scss'),
                resource_path('assets/vendor/scss/theme-default-dark.scss'),
            ];

            foreach ($themeFiles as $filePath) {
                if (! File::exists($filePath)) {
                    Log::warning("SCSS file not found: {$filePath}");

                    continue;
                }

                // Read the file content
                $content = File::get($filePath);

                // Replace the primary color value
                // Match pattern: $primary-color: #anything;
                $pattern = '/\$primary-color:\s*#[a-fA-F0-9]{3,6}\s*;/';
                $replacement = '$primary-color: '.$color.';';

                $updatedContent = preg_replace($pattern, $replacement, $content);

                if ($updatedContent === null) {
                    Log::error("Failed to update SCSS file: {$filePath}");

                    return false;
                }

                // Write the updated content back to the file
                File::put($filePath, $updatedContent);

                Log::info('Updated SCSS file with new brand color', [
                    'file' => $filePath,
                    'color' => $color,
                ]);
            }

            // Rebuild CSS assets after updating SCSS
            $this->rebuildAssets();

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update brand color in SCSS files', [
                'error' => $e->getMessage(),
                'color' => $color,
            ]);

            return false;
        }
    }

    /**
     * Rebuild CSS assets using npm/vite
     */
    protected function rebuildAssets(): void
    {
        try {
            // Run npm build command to recompile SCSS
            $result = Process::timeout(120)->run('npm run build');

            if ($result->successful()) {
                Log::info('Successfully rebuilt CSS assets after brand color update');
            } else {
                Log::warning('Failed to rebuild assets automatically', [
                    'output' => $result->output(),
                    'error' => $result->errorOutput(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Could not rebuild assets automatically', [
                'error' => $e->getMessage(),
                'note' => 'Please run "npm run build" manually to apply the brand color changes',
            ]);
        }
    }

    /**
     * Get current brand color from SCSS file
     */
    public function getCurrentBrandColor(): ?string
    {
        try {
            $filePath = resource_path('assets/vendor/scss/theme-default.scss');

            if (! File::exists($filePath)) {
                return null;
            }

            $content = File::get($filePath);

            // Match pattern: $primary-color: #anything;
            if (preg_match('/\$primary-color:\s*(#[a-fA-F0-9]{3,6})\s*;/', $content, $matches)) {
                return $matches[1];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get current brand color from SCSS', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
