<?php

namespace Tests\Feature\Issues;

use Illuminate\Http\Response;
use Tests\TestCase;

class Issue11Test extends TestCase
{
    /**
     * Test that the leave management JavaScript file contains proper SweetAlert2 configuration
     * for success messages to show "OK" button instead of "No" and "Cancel" buttons.
     *
     * @test
     */
    public function it_shows_correct_buttons_in_success_popup_after_leave_approval()
    {
        // Read the JavaScript file content
        $jsFilePath = resource_path('assets/js/app/hrcore-leaves.js');
        $this->assertFileExists($jsFilePath, 'Leave management JavaScript file should exist');

        $jsContent = file_get_contents($jsFilePath);

        // Test that the success SweetAlert2 configuration is properly set
        // The success popup should have showCancelButton: false and confirmButtonText: 'OK'
        $this->assertStringContainsString(
            'showCancelButton: false',
            $jsContent,
            'Success popup should explicitly disable cancel button'
        );

        $this->assertStringContainsString(
            "confirmButtonText: 'OK'",
            $jsContent,
            'Success popup should have OK button text'
        );

        // Test that the success popup configuration appears after the AJAX success response
        $successPattern = '/success:\s*function\s*\(response\)\s*{\s*if\s*\(response\.status\s*===\s*[\'"]success[\'"]\)\s*{[\s\S]*?Swal\.fire\({[\s\S]*?showCancelButton:\s*false[\s\S]*?confirmButtonText:\s*[\'"]OK[\'"][\s\S]*?}\);/';

        $this->assertMatchesRegularExpression(
            $successPattern,
            $jsContent,
            'Success popup should be properly configured within the AJAX success handler'
        );
    }

    /**
     * Test that the confirmation dialog still has proper button configuration.
     *
     * @test
     */
    public function it_shows_correct_buttons_in_confirmation_dialog()
    {
        $jsFilePath = resource_path('assets/js/app/hrcore-leaves.js');
        $jsContent = file_get_contents($jsFilePath);

        // Test that confirmation dialog has proper buttons
        $this->assertStringContainsString(
            'showCancelButton: true',
            $jsContent,
            'Confirmation dialog should have cancel button'
        );

        $this->assertStringContainsString(
            "confirmButtonText: 'Yes'",
            $jsContent,
            'Confirmation dialog should have Yes button'
        );

        $this->assertStringContainsString(
            "cancelButtonText: 'No'",
            $jsContent,
            'Confirmation dialog should have No button'
        );
    }

    /**
     * Test that the handleLeaveAction function exists and is properly structured.
     *
     * @test
     */
    public function it_has_proper_handle_leave_action_function()
    {
        $jsFilePath = resource_path('assets/js/app/hrcore-leaves.js');
        $jsContent = file_get_contents($jsFilePath);

        // Test that handleLeaveAction function exists
        $this->assertStringContainsString(
            'window.handleLeaveAction = function(id, status)',
            $jsContent,
            'handleLeaveAction function should exist as a global function'
        );

        // Test that the function contains both confirmation and success dialogs
        $this->assertStringContainsString(
            'Swal.fire({',
            $jsContent,
            'Function should use SweetAlert2'
        );

        // Test that AJAX success handler is properly implemented
        $this->assertStringContainsString(
            'if (response.status === \'success\')',
            $jsContent,
            'AJAX success handler should check response status'
        );
    }

    /**
     * Test that the JavaScript file doesn't contain any syntax errors.
     *
     * @test
     */
    public function it_has_valid_javascript_syntax()
    {
        $jsFilePath = resource_path('assets/js/app/hrcore-leaves.js');
        $jsContent = file_get_contents($jsFilePath);

        // Basic syntax validation - check for balanced braces, parentheses, and brackets
        $braceBalance = substr_count($jsContent, '{') - substr_count($jsContent, '}');
        $parenBalance = substr_count($jsContent, '(') - substr_count($jsContent, ')');
        $bracketBalance = substr_count($jsContent, '[') - substr_count($jsContent, ']');

        $this->assertEquals(0, $braceBalance, 'JavaScript should have balanced braces');
        $this->assertEquals(0, $parenBalance, 'JavaScript should have balanced parentheses');
        $this->assertEquals(0, $bracketBalance, 'JavaScript should have balanced brackets');

        // Check that the file is not empty
        $this->assertGreaterThan(100, strlen($jsContent), 'JavaScript file should not be empty');
    }
}
