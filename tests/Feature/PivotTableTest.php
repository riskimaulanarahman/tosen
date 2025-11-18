<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Outlet;
use App\Models\Attendance;
use App\Services\PivotTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class PivotTableTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;
    protected $outlet;
    protected $employee;
    protected $pivotService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pivotService = new PivotTableService();
        
        // Create owner
        $this->owner = User::factory()->create([
            'role' => 'owner',
        ]);
        
        // Create outlet
        $this->outlet = Outlet::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        
        // Create employee
        $this->employee = User::factory()->create([
            'role' => 'employee',
            'outlet_id' => $this->outlet->id,
        ]);
    }

    public function test_pivot_table_page_can_be_accessed_by_owner()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('reports.pivot'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return $page->component('Reports/Pivot');
        });
    }

    public function test_pivot_table_page_cannot_be_accessed_by_employee()
    {
        $response = $this->actingAs($this->employee)
            ->get(route('reports.pivot'));

        $response->assertStatus(403);
    }

    public function test_pivot_table_service_generates_correct_structure()
    {
        // Create attendance records
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        Attendance::factory()->create([
            'user_id' => $this->employee->id,
            'check_in_date' => $startDate->format('Y-m-d'),
            'check_in_time' => $startDate->copy()->setTime(8, 0),
            'check_out_time' => $startDate->copy()->setTime(17, 0),
            'attendance_status' => 'on_time',
        ]);

        $pivotData = $this->pivotService->generatePivotTable(
            $this->owner->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $this->assertArrayHasKey('employees', $pivotData);
        $this->assertArrayHasKey('date_range', $pivotData);
        $this->assertArrayHasKey('summary', $pivotData);
        
        $this->assertCount(1, $pivotData['employees']);
        $this->assertEquals($this->employee->name, $pivotData['employees'][0]['name']);
        $this->assertArrayHasKey($startDate->format('Y-m-d'), $pivotData['employees'][0]['attendances']);
    }

    public function test_pivot_table_service_handles_weekends_correctly()
    {
        $startDate = Carbon::create(2024, 1, 1); // Monday
        $endDate = Carbon::create(2024, 1, 7);   // Sunday

        $pivotData = $this->pivotService->generatePivotTable(
            $this->owner->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $attendances = $pivotData['employees'][0]['attendances'];
        
        // Saturday and Sunday should be marked as weekend
        $this->assertEquals('weekend', $attendances['2024-01-06']['status']); // Saturday
        $this->assertEquals('weekend', $attendances['2024-01-07']['status']); // Sunday
        
        // Weekdays should be marked as absent (no attendance records)
        $this->assertEquals('absent', $attendances['2024-01-01']['status']); // Monday
        $this->assertEquals('absent', $attendances['2024-01-02']['status']); // Tuesday
    }

    public function test_pivot_table_service_calculates_summary_correctly()
    {
        $startDate = Carbon::now()->startOfMonth();
        
        // Create multiple attendance records with different statuses
        Attendance::factory()->create([
            'user_id' => $this->employee->id,
            'check_in_date' => $startDate->format('Y-m-d'),
            'attendance_status' => 'on_time',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->employee->id,
            'check_in_date' => $startDate->copy()->addDay()->format('Y-m-d'),
            'attendance_status' => 'late',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->employee->id,
            'check_in_date' => $startDate->copy()->addDays(2)->format('Y-m-d'),
            'attendance_status' => 'absent',
        ]);

        $pivotData = $this->pivotService->generatePivotTable(
            $this->owner->id,
            $startDate->format('Y-m-d'),
            $startDate->copy()->addDays(2)->format('Y-m-d')
        );

        $summary = $pivotData['summary']['attendance_summary'];
        
        $this->assertEquals(1, $summary['on_time']);
        $this->assertEquals(1, $summary['late']);
        $this->assertEquals(1, $summary['absent']);
    }

    public function test_pivot_table_export_generates_csv()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        Attendance::factory()->create([
            'user_id' => $this->employee->id,
            'check_in_date' => $startDate->format('Y-m-d'),
            'attendance_status' => 'on_time',
        ]);

        $pivotData = $this->pivotService->generatePivotTable(
            $this->owner->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $statusConfig = $this->pivotService->getStatusConfig();
        $exportData = $this->pivotService->exportToCsv($pivotData, $statusConfig);

        $this->assertArrayHasKey('filename', $exportData);
        $this->assertArrayHasKey('content', $exportData);
        $this->assertStringContainsString('Nama Karyawan', $exportData['content']);
        $this->assertStringContainsString($this->employee->name, $exportData['content']);
    }

    public function test_pivot_table_respects_outlet_filter()
    {
        // Create another outlet and employee
        $otherOutlet = Outlet::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        
        $otherEmployee = User::factory()->create([
            'role' => 'employee',
            'outlet_id' => $otherOutlet->id,
        ]);

        $startDate = Carbon::now()->startOfMonth();
        
        // Create attendance for both employees
        Attendance::factory()->create([
            'user_id' => $this->employee->id,
            'check_in_date' => $startDate->format('Y-m-d'),
            'attendance_status' => 'on_time',
        ]);

        Attendance::factory()->create([
            'user_id' => $otherEmployee->id,
            'check_in_date' => $startDate->format('Y-m-d'),
            'attendance_status' => 'on_time',
        ]);

        // Test without outlet filter (should return both employees)
        $pivotData = $this->pivotService->generatePivotTable(
            $this->owner->id,
            $startDate->format('Y-m-d'),
            $startDate->format('Y-m-d')
        );

        $this->assertCount(2, $pivotData['employees']);

        // Test with outlet filter (should return only filtered employee)
        $pivotDataFiltered = $this->pivotService->generatePivotTable(
            $this->owner->id,
            $startDate->format('Y-m-d'),
            $startDate->format('Y-m-d'),
            $this->outlet->id
        );

        $this->assertCount(1, $pivotDataFiltered['employees']);
        $this->assertEquals($this->employee->name, $pivotDataFiltered['employees'][0]['name']);
    }

    public function test_pivot_table_status_config_returns_correct_structure()
    {
        $statusConfig = $this->pivotService->getStatusConfig();

        $this->assertArrayHasKey('on_time', $statusConfig);
        $this->assertArrayHasKey('late', $statusConfig);
        $this->assertArrayHasKey('absent', $statusConfig);
        
        $this->assertArrayHasKey('text', $statusConfig['on_time']);
        $this->assertArrayHasKey('color', $statusConfig['on_time']);
        $this->assertArrayHasKey('bg_color', $statusConfig['on_time']);
        $this->assertArrayHasKey('icon', $statusConfig['on_time']);
    }

    public function test_pivot_table_export_endpoint_works()
    {
        Attendance::factory()->create([
            'user_id' => $this->employee->id,
            'check_in_date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => 'on_time',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('reports.export.pivot'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }
}
