<?php

namespace Tests\Feature\Issues;

use Tests\TestCase;

/**
 * Test case for GitHub Issue #32: Searching a Unit Triggers DataTable Warning
 *
 * This test validates that the DataTable search functionality works without
 * triggering SQL errors when searching on computed columns like products_count.
 */
class Issue32Test extends TestCase
{
    /**
     * Test that DataTable JavaScript configuration prevents products_count from being searchable.
     *
     * This is the core fix for issue #32: The products_count column must be marked as
     * non-searchable to prevent DataTables from trying to search on it, which would
     * cause an SQL error since products_count is a computed field from withCount('products').
     */
    public function test_javascript_configuration_marks_products_count_as_non_searchable()
    {
        // Read the JavaScript file content to verify the configuration
        $jsFilePath = base_path('Modules/WMSInventoryCore/resources/assets/js/wms-inventory-units.js');
        $this->assertFileExists($jsFilePath);

        $jsContent = file_get_contents($jsFilePath);

        // Verify that products_count column is marked as non-searchable
        $this->assertStringContainsString("{ data: 'products_count', searchable: false }", $jsContent);

        // Verify that actions column is also non-searchable (existing behavior)
        $this->assertStringContainsString("{ data: 'actions', orderable: false, searchable: false }", $jsContent);
    }
}
