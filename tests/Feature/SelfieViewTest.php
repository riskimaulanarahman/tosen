<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Outlet;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SelfieViewTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up storage for testing
        Storage::fake('public');
    }

    public function test_owner_can_view_attendance_report_with_selfie_data()
    {
        // Create owner
        $owner = User::factory()->create(['role' => 'owner']);
        
        // Create outlet owned by owner
        $outlet = Outlet::factory()->create(['owner_id' => $owner->id]);
        
        // Create employee
        $employee = User::factory()->create([
            'role' => 'employee',
            'outlet_id' => $outlet->id
        ]);
        
        // Create attendance with selfie paths
        $attendance = Attendance::factory()->create([
            'user_id' => $employee->id,
            'outlet_id' => $outlet->id,
            'check_in_selfie_path' => 'selfies/checkin_' . $this->faker->uuid() . '.jpg',
            'check_out_selfie_path' => 'selfies/checkout_' . $this->faker->uuid() . '.jpg',
            'check_in_thumbnail_path' => 'selfies/thumbnails/checkin_' . $this->faker->uuid() . '.jpg',
            'check_out_thumbnail_path' => 'selfies/thumbnails/checkout_' . $this->faker->uuid() . '.jpg',
        ]);
        
        // Act as owner and request reports
        $response = $this->actingAs($owner)
            ->get(route('reports.index'));
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Assert response contains selfie URL attributes
        $response->assertInertia(function ($page) use ($attendance) {
            $props = $page->toArray()['props'];
            $attendances = $props['attendances']['data'];
            
            // Find our attendance in the collection
            $foundAttendance = collect($attendances)->firstWhere('id', $attendance->id);
            
            $this->assertNotNull($foundAttendance);
            $this->assertArrayHasKey('check_in_selfie_url', $foundAttendance);
            $this->assertArrayHasKey('check_out_selfie_url', $foundAttendance);
            $this->assertArrayHasKey('check_in_thumbnail_url', $foundAttendance);
            $this->assertArrayHasKey('check_out_thumbnail_url', $foundAttendance);
            
            return true;
        });
    }

    public function test_owner_can_view_selfie_modal_data()
    {
        // Create owner
        $owner = User::factory()->create(['role' => 'owner']);
        
        // Create outlet owned by owner
        $outlet = Outlet::factory()->create(['owner_id' => $owner->id]);
        
        // Create employee
        $employee = User::factory()->create([
            'role' => 'employee',
            'outlet_id' => $outlet->id
        ]);
        
        // Create attendance with selfie data
        $attendance = Attendance::factory()->create([
            'user_id' => $employee->id,
            'outlet_id' => $outlet->id,
            'check_in_selfie_path' => 'selfies/checkin_' . $this->faker->uuid() . '.jpg',
            'check_out_selfie_path' => 'selfies/checkout_' . $this->faker->uuid() . '.jpg',
            'check_in_file_size' => 150000, // 150KB
            'check_out_file_size' => 180000, // 180KB
        ]);
        
        // Act as owner and request reports
        $response = $this->actingAs($owner)
            ->get(route('reports.index'));
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Assert response contains formatted file sizes
        $response->assertInertia(function ($page) use ($attendance) {
            $props = $page->toArray()['props'];
            $attendances = $props['attendances']['data'];
            
            // Find our attendance in the collection
            $foundAttendance = collect($attendances)->firstWhere('id', $attendance->id);
            
            $this->assertNotNull($foundAttendance);
            $this->assertArrayHasKey('check_in_file_size_formatted', $foundAttendance);
            $this->assertArrayHasKey('check_out_file_size_formatted', $foundAttendance);
            $this->assertEquals('146.48 KB', $foundAttendance['check_in_file_size_formatted']);
            $this->assertEquals('175.78 KB', $foundAttendance['check_out_file_size_formatted']);
            
            return true;
        });
    }

    public function test_owner_can_access_simple_selfie_feed()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $outlet = Outlet::factory()->create(['owner_id' => $owner->id]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'outlet_id' => $outlet->id,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $employee->id,
            'outlet_id' => $outlet->id,
            'check_in_selfie_path' => 'selfies/checkin_' . $this->faker->uuid() . '.jpg',
            'check_out_selfie_path' => 'selfies/checkout_' . $this->faker->uuid() . '.jpg',
            'check_in_file_size' => 120000,
            'check_out_file_size' => 90000,
        ]);

        $response = $this->actingAs($owner)
            ->get(route('reports.selfies'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($attendance) {
            $page->component('Reports/SelfieFeed');
            $props = $page->toArray()['props'];
            $feedAttendances = $props['attendances']['data'];
            $found = collect($feedAttendances)->firstWhere('id', $attendance->id);
            $this->assertNotNull($found);
            $this->assertEquals('117.19 KB', $found['check_in_file_size_formatted']);
            $this->assertEquals('87.89 KB', $found['check_out_file_size_formatted']);
            $this->assertNotNull($found['check_in_selfie_url']);
            $this->assertNotNull($found['check_out_selfie_url']);

            return true;
        });
    }

    public function test_unauthorized_user_cannot_view_selfie_data()
    {
        // Create different owner
        $otherOwner = User::factory()->create(['role' => 'owner']);
        
        // Create outlet owned by other owner
        $outlet = Outlet::factory()->create(['owner_id' => $otherOwner->id]);
        
        // Create employee
        $employee = User::factory()->create([
            'role' => 'employee',
            'outlet_id' => $outlet->id
        ]);
        
        // Create attendance with selfie data
        $attendance = Attendance::factory()->create([
            'user_id' => $employee->id,
            'outlet_id' => $outlet->id,
            'check_in_selfie_path' => 'selfies/checkin_' . $this->faker->uuid() . '.jpg',
        ]);
        
        // Create unauthorized owner
        $unauthorizedOwner = User::factory()->create(['role' => 'owner']);
        
        // Act as unauthorized owner and request reports
        $response = $this->actingAs($unauthorizedOwner)
            ->get(route('reports.index'));
        
        // Assert response is successful but doesn't contain other owner's data
        $response->assertStatus(200);
        
        $response->assertInertia(function ($page) use ($attendance) {
            $props = $page->toArray()['props'];
            $attendances = $props['attendances']['data'];
            
            // Should not find the other owner's attendance
            $foundAttendance = collect($attendances)->firstWhere('id', $attendance->id);
            $this->assertNull($foundAttendance);
            
            return true;
        });
    }

    public function test_employee_cannot_access_selfie_feed()
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($employee)
            ->get(route('reports.selfies'));

        $response->assertStatus(403);
    }

    public function test_employee_cannot_access_reports_page()
    {
        // Create employee
        $employee = User::factory()->create(['role' => 'employee']);
        
        // Act as employee and try to access reports
        $response = $this->actingAs($employee)
            ->get(route('reports.index'));
        
        // Assert response is forbidden
        $response->assertStatus(403);
    }
}
