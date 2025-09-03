<?php

namespace Tests\Feature\Issues;

use Tests\TestCase;

class Issue27Test extends TestCase
{
    /**
     * Test that the JavaScript file contains the proper response handling
     * for issue #27: No Success Message After Product Deletion
     */
    public function test_javascript_contains_proper_success_message_handling()
    {
        // Read the JavaScript file
        $jsPath = resource_path('assets/js/app/wms-inventory-products.js');
        $this->assertFileExists($jsPath, 'JavaScript file should exist');

        $jsContent = file_get_contents($jsPath);

        // Assert that the success handler uses response.data.message
        $this->assertStringContainsString(
            'response.data.message',
            $jsContent,
            'JavaScript should use response.data.message for success message'
        );

        // Assert that there's proper fallback message
        $this->assertStringContainsString(
            'Product has been deleted successfully',
            $jsContent,
            'JavaScript should have fallback success message'
        );

        // Assert that error handling also uses proper response format
        $this->assertStringContainsString(
            'error.responseJSON.data?.message',
            $jsContent,
            'JavaScript should use proper error message handling'
        );
    }

    /**
     * Test that the backend controller returns proper success response structure
     */
    public function test_backend_returns_proper_response_structure()
    {
        // Check that the ProductController destroy method uses Success::response
        $controllerPath = app_path('../Modules/WMSInventoryCore/app/Http/Controllers/ProductController.php');
        $this->assertFileExists($controllerPath, 'ProductController should exist');

        $controllerContent = file_get_contents($controllerPath);

        // Assert that the destroy method returns Success::response with proper message
        $this->assertStringContainsString(
            'Success::response(__(',
            $controllerContent,
            'Controller should use Success::response for success messages'
        );

        $this->assertStringContainsString(
            'Product has been deleted successfully',
            $controllerContent,
            'Controller should return proper success message'
        );
    }

    /**
     * Test that Success and Error response classes exist and work properly
     */
    public function test_response_classes_exist()
    {
        // Test that Success::response works
        $successResponse = \App\ApiClasses\Success::response('Test message');
        $responseData = $successResponse->getData(true); // Get as array
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Test message', $responseData['data']);

        // Test that Error::response works
        $errorResponse = \App\ApiClasses\Error::response('Test error');
        $errorData = $errorResponse->getData(true); // Get as array
        $this->assertEquals('failed', $errorData['status']);

        // Test with array data structure
        $successWithArray = \App\ApiClasses\Success::response(['message' => 'Test message']);
        $arrayData = $successWithArray->getData(true);
        $this->assertEquals('success', $arrayData['status']);
        $this->assertEquals('Test message', $arrayData['data']['message']);
    }
}
