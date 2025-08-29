<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use L5Swagger\Generator;
use OpenApi\Generator as OpenApiGenerator;

class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:generate-custom';
    protected $description = 'Generate Swagger documentation with suppressed warnings';

    public function handle()
    {
        $this->info('Generating Swagger documentation...');
        
        // Create storage directory if it doesn't exist
        $docsPath = storage_path('api-docs');
        if (!is_dir($docsPath)) {
            mkdir($docsPath, 0755, true);
        }
        
        try {
            // Suppress warnings
            $originalReporting = error_reporting();
            error_reporting(E_ERROR | E_PARSE);
            
            // Get config
            $config = config('l5-swagger.documentations.default', []);
            $paths = $config['paths']['annotations'] ?? [
                base_path('app/Http/Controllers'),
                base_path('Modules/DesktopTracker/app/Http/Controllers'),
            ];
            
            // Generate OpenAPI documentation
            $openapi = OpenApiGenerator::scan($paths);
            
            // Save as JSON
            $jsonPath = $docsPath . '/api-docs.json';
            file_put_contents($jsonPath, $openapi->toJson());
            
            // Restore error reporting
            error_reporting($originalReporting);
            
            $this->info('Documentation generated successfully!');
            $this->info('Location: ' . $jsonPath);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to generate documentation: ' . $e->getMessage());
            return 1;
        }
    }
}