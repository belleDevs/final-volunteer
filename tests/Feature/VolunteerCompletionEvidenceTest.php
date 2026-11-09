<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VolunteerCompletionEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_volunteer_can_submit_task_evidence_for_review(): void
    {
        Storage::fake('public');

        $volunteer = User::factory()->create([
            'role' => 'volunteer',
            'status' => 'approved',
        ]);
        $organization = User::factory()->create([
            'role' => 'organization',
            'status' => 'approved',
        ]);
        $event = Event::create([
            'organization_id' => $organization->id,
            'title' => 'Community Cleanup',
            'description' => 'Neighborhood cleanup',
            'location' => 'Community Park',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
        ]);
        $task = Task::create([
            'event_id' => $event->id,
            'title' => 'Clean the park',
            'priority' => 'medium',
            'status' => 'pending',
        ]);
        $assignment = Assignment::create([
            'user_id' => $volunteer->id,
            'event_id' => $event->id,
            'task_id' => $task->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($volunteer)->post(
            route('volunteer.tasks.complete', $assignment->id),
            [
                'completion_note' => 'Collected litter around the park.',
                'completion_proof' => UploadedFile::fake()->image('cleanup.jpg'),
            ]
        );

        $response->assertRedirect(route('volunteer.dashboard', ['section' => 'tasks']));
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertSame('submitted', $assignment->status);
        $this->assertSame('Collected litter around the park.', $assignment->completion_note);
        $this->assertNotNull($assignment->completion_proof_path);
        Storage::disk('public')->assertExists($assignment->completion_proof_path);

        $evidenceResponse = $this->actingAs($organization)
            ->get(route('org.assignments.proof', $assignment));

        $evidenceResponse->assertOk();
        $this->assertStringStartsWith('inline;', $evidenceResponse->headers->get('Content-Disposition'));
    }
}
