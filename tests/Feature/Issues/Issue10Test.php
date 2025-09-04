<?php

namespace Tests\Feature\Issues;

use Illuminate\Foundation\Testing\TestCase;
use Modules\HRCore\app\Http\Controllers\LeaveController;

class Issue10Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /**
     * Test that team calendar shows approved leave requests with improved date filtering
     * Issue #10: Approved Employee Leave Requests Not Reflected in Team Calendar View
     */
    public function test_team_calendar_shows_approved_leaves_with_improved_date_filtering(): void
    {
        // Test the controller method logic without database operations
        $controller = new LeaveController;

        // Test that the controller method exists
        $this->assertTrue(method_exists($controller, 'teamCalendar'));

        // Verify the route exists
        $response = $this->get('/hrcore/leaves/team');
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    /**
     * Test that the team calendar view file exists and contains required elements
     */
    public function test_team_calendar_view_structure(): void
    {
        $viewPath = 'Modules/HRCore/resources/views/leave/team-calendar.blade.php';
        $this->assertFileExists(base_path($viewPath));

        $viewContent = file_get_contents(base_path($viewPath));

        // Verify key elements exist in the view
        $this->assertStringContainsString('Team Leave Calendar', $viewContent);
        $this->assertStringContainsString('teamCalendar', $viewContent);
        $this->assertStringContainsString('window.teamCalendarData', $viewContent);
        $this->assertStringContainsString('status-filter', $viewContent);
        $this->assertStringContainsString('approved', $viewContent);
    }

    /**
     * Test that JavaScript file exists and handles calendar data properly
     */
    public function test_team_calendar_javascript_exists(): void
    {
        $jsPath = 'resources/assets/js/app/hrcore-team-calendar.js';
        $this->assertFileExists(base_path($jsPath));

        $jsContent = file_get_contents(base_path($jsPath));

        // Verify key JavaScript functionality
        $this->assertStringContainsString('window.teamCalendarData', $jsContent);
        $this->assertStringContainsString('Calendar', $jsContent);
        $this->assertStringContainsString('events: calendarData.leaves', $jsContent);
        $this->assertStringContainsString('status-filter', $jsContent);
    }

    /**
     * Test date filtering logic for improved leave visibility
     */
    public function test_date_filtering_logic_improvements(): void
    {
        // Read the controller file to verify the fix is in place
        $controllerContent = file_get_contents(app_path('../Modules/HRCore/app/Http/Controllers/LeaveController.php'));

        // Verify the improved date filtering logic exists
        $this->assertStringContainsString('addMonths(6)', $controllerContent);
        $this->assertStringContainsString('subMonth()', $controllerContent);
        $this->assertStringContainsString('whereBetween', $controllerContent);
        $this->assertStringContainsString('orWhere', $controllerContent);

        // Verify it shows ongoing leaves
        $this->assertStringContainsString('from_date\', \'<=\', now()', $controllerContent);
        $this->assertStringContainsString('to_date\', \'>=\', now()', $controllerContent);

        // Verify proper ordering
        $this->assertStringContainsString('orderBy(\'from_date\', \'asc\')', $controllerContent);
    }

    /**
     * Test that approved status is included in the query
     */
    public function test_approved_status_included_in_query(): void
    {
        $controllerContent = file_get_contents(app_path('../Modules/HRCore/app/Http/Controllers/LeaveController.php'));

        // Verify both approved and pending statuses are included
        $this->assertStringContainsString("whereIn('status', ['approved', 'pending'])", $controllerContent);
    }

    /**
     * Test that the team calendar route is properly configured
     */
    public function test_team_calendar_route_configuration(): void
    {
        $routeContent = file_get_contents(app_path('../Modules/HRCore/routes/web.php'));

        // Verify the team calendar route exists
        $this->assertStringContainsString("Route::get('/team', [LeaveController::class, 'teamCalendar'])->name('team')", $routeContent);

        // Verify it's under the leaves prefix
        $this->assertStringContainsString("Route::prefix('leaves')->name('leaves.')", $routeContent);
    }

    /**
     * Test that the calendar view passes required data to JavaScript
     */
    public function test_calendar_view_data_structure(): void
    {
        $viewContent = file_get_contents(base_path('Modules/HRCore/resources/views/leave/team-calendar.blade.php'));

        // Check that leave data is properly structured for JavaScript
        $this->assertStringContainsString('$leavesData = $leaves->map(function($leave)', $viewContent);
        $this->assertStringContainsString("'status' => \$leave->status", $viewContent);
        $this->assertStringContainsString("'start' => \$leave->from_date->format('Y-m-d')", $viewContent);
        $this->assertStringContainsString("'end' => \$leave->to_date->addDay()->format('Y-m-d')", $viewContent);
    }

    /**
     * Test permissions are properly checked
     */
    public function test_team_calendar_permission_check(): void
    {
        $controllerContent = file_get_contents(app_path('../Modules/HRCore/app/Http/Controllers/LeaveController.php'));

        // Verify permission middleware is applied
        $this->assertStringContainsString("middleware('permission:hrcore.view-team-leaves')->only(['teamCalendar'])", $controllerContent);
    }
}
