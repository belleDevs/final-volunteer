<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrganizationReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_dates_use_manila_time_and_pdf_download_contains_am_pm_schedule_data(): void
    {
        $this->travelTo(Carbon::parse('2026-04-15 02:00:00', 'Asia/Manila'));

        $organization = User::factory()->create([
            'role' => 'organization',
            'status' => 'approved',
            'name' => 'Accurate Reports Org',
        ]);

        $event = Event::create([
            'organization_id' => $organization->id,
            'title' => 'Early Morning Event',
            'description' => 'Report date and time check.',
            'location' => 'Manila',
            'status' => 'published',
            'start_time' => '2026-04-15 00:30:00',
            'end_time' => '2026-04-15 01:30:00',
        ]);

        $task = Task::create([
            'event_id' => $event->id,
            'title' => 'Morning Setup',
            'status' => 'pending',
            'due_date' => '2026-04-15 03:30:00',
        ]);

        $this->assertFalse($task->isOverdue());
        $pastTask = Task::create([
            'event_id' => $event->id,
            'title' => 'Check-in',
            'status' => 'pending',
            'due_date' => '2026-04-15 01:30:00',
        ]);
        $this->assertTrue($pastTask->isOverdue());

        $query = ['range' => 'today', 'category' => 'events'];

        $this->actingAs($organization)
            ->get(route('org.reports', $query))
            ->assertOk()
            ->assertSee('Apr 15, 2026 12:30 AM')
            ->assertSee('Apr 15, 2026 1:30 AM');

        $response = $this->get(route('org.reports.export', ['format' => 'pdf']).'?'.http_build_query($query));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));

        $pdf = $response->streamedContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('Early Morning Event', $pdf);
        $this->assertStringContainsString('Apr 15, 2026 12:30 AM', $pdf);

        $excelResponse = $this->get(route('org.reports.export', ['format' => 'xls']).'?'.http_build_query($query));
        $excelResponse->assertOk();
        $excel = $excelResponse->streamedContent();
        $this->assertStringContainsString('<Workbook', $excel);
        $this->assertStringContainsString('Early Morning Event', $excel);
        $this->assertStringContainsString('Apr 15, 2026 12:30 AM', $excel);

        $taskResponse = $this->get(route('org.reports.export', ['format' => 'pdf']).'?'.http_build_query([
            'range' => 'today',
            'category' => 'tasks',
        ]));
        $taskResponse->assertOk();
        $this->assertStringContainsString('Apr 15, 2026 3:30 AM', $taskResponse->streamedContent());
    }
}
