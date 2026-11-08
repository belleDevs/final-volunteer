<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\OrganizationApplication;
use App\Models\Task;
use App\Models\User;
use App\Support\ScheduleClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardsAndExportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_organization_links_only_include_approved_memberships(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $approvedOrganization = User::factory()->create([
            'role' => 'organization',
            'status' => 'approved',
            'name' => 'Accepted Community Group',
        ]);
        $pendingOrganization = User::factory()->create([
            'role' => 'organization',
            'status' => 'approved',
            'name' => 'Pending Community Group',
        ]);
        $volunteer = User::factory()->create([
            'role' => 'volunteer',
            'status' => 'approved',
            'name' => 'Volunteer With Memberships',
            'organization_id' => $approvedOrganization->id,
        ]);
        $unapprovedVolunteer = User::factory()->create([
            'role' => 'volunteer',
            'status' => 'approved',
            'name' => 'Volunteer Without Approved Membership',
            'organization_id' => $approvedOrganization->id,
        ]);

        OrganizationApplication::create([
            'user_id' => $volunteer->id,
            'organization_id' => $approvedOrganization->id,
            'status' => 'approved',
        ]);
        OrganizationApplication::create([
            'user_id' => $volunteer->id,
            'organization_id' => $pendingOrganization->id,
            'status' => 'pending',
        ]);
        OrganizationApplication::create([
            'user_id' => $unapprovedVolunteer->id,
            'organization_id' => $approvedOrganization->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'users']));
        $response->assertOk();

        $this->assertMatchesRegularExpression(
            '/<tr[^>]*data-role="volunteer"[^>]*data-search="[^"]*'.preg_quote(strtolower($volunteer->email), '/').'[^"]*"[^>]*data-org-links="Accepted Community Group"/',
            $response->getContent()
        );
        $response->assertSee('Approved member')
            ->assertSee('Pending organization review');
        $this->assertMatchesRegularExpression(
            '/<tr[^>]*data-role="volunteer"[^>]*data-search="[^"]*'.preg_quote(strtolower($unapprovedVolunteer->email), '/').'[^"]*"[^>]*data-org-links=""/',
            $response->getContent()
        );
    }

    public function test_admin_roster_only_counts_organization_approved_members_and_handles_missing_photos(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $organization = User::factory()->create([
            'role' => 'organization',
            'status' => 'approved',
            'name' => 'Approved Membership Organization',
        ]);
        $approvedMember = User::factory()->create([
            'role' => 'volunteer',
            'status' => 'approved',
            'name' => 'Approved Member',
            'profile_photo_path' => 'profile-photos/missing/photo.jpg',
        ]);
        $unapprovedMember = User::factory()->create([
            'role' => 'volunteer',
            'status' => 'approved',
            'name' => 'Not Yet Approved Member',
            'organization_id' => $organization->id,
        ]);

        OrganizationApplication::create([
            'user_id' => $approvedMember->id,
            'organization_id' => $organization->id,
            'status' => 'approved',
        ]);
        OrganizationApplication::create([
            'user_id' => $unapprovedMember->id,
            'organization_id' => $organization->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'volunteers']));
        $response->assertOk()
            ->assertSee('Approved member')
            ->assertSee('nextElementSibling.hidden = false', false);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'data-member="active"'));
        preg_match_all('/<article data-volunteer-card.*?<\/article>/s', $html, $rosterCards);
        $this->assertCount(1, $rosterCards[0]);
        $this->assertStringContainsString('Approved Member', $rosterCards[0][0]);
        $this->assertStringNotContainsString('Not Yet Approved Member', $rosterCards[0][0]);

        $organizationRoster = $this->get(route('admin.dashboard', [
            'tab' => 'volunteers',
            'volunteer_org' => $organization->id,
        ]));
        $organizationRoster->assertOk()->assertSee('Approved Member');
        $this->assertSame(1, substr_count($organizationRoster->getContent(), '<article data-volunteer-card'));

        $dashboard = $this->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $this->assertMatchesRegularExpression(
            '/<p class="mt-5 text-3xl font-black text-slate-900 tracking-tight">1<\/p>\s*<p class="mt-1 text-sm text-slate-400 font-medium">Organization-Approved Volunteers<\/p>/',
            $dashboard->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<a href="[^"]*tab=volunteers"[^>]*>.*?<span[^>]*>1<\/span>/s',
            $dashboard->getContent()
        );
    }

    public function test_all_roles_can_download_pdf_and_excel_reports_and_scoped_audit_exports(): void
    {
        $accounts = [
            'admin' => User::factory()->create(['role' => 'admin', 'status' => 'approved']),
            'organization' => User::factory()->create(['role' => 'organization', 'status' => 'approved']),
            'volunteer' => User::factory()->create(['role' => 'volunteer', 'status' => 'approved']),
        ];

        foreach ($accounts as $role => $account) {
            $reportRoute = ['admin' => 'admin.reports.export', 'organization' => 'org.reports.export', 'volunteer' => 'volunteer.reports.export'][$role];
            foreach (['pdf', 'xls'] as $format) {
                $response = $this->actingAs($account)->get(route($reportRoute, ['format' => $format]));
                $response->assertOk();
                $response->assertHeader('content-type', $format === 'pdf'
                    ? 'application/pdf'
                    : 'application/vnd.ms-excel; charset=UTF-8');

                $contents = $response->streamedContent();
                $this->assertStringContainsString($format === 'pdf' ? '%PDF-1.4' : '<Workbook', $contents);
            }
        }

        $organization = $accounts['organization'];
        $volunteer = $accounts['volunteer'];
        $this->recordAudit($accounts['admin'], 'admin-system-entry');
        $this->recordAudit($organization, 'organization-only-entry');
        $this->recordAudit($volunteer, 'volunteer-only-entry');

        foreach (['pdf', 'xls', 'csv'] as $format) {
            $adminExport = $this->actingAs($accounts['admin'])
                ->get(route('admin.audit.export', ['format' => $format]));
            $adminExport->assertOk();
            $this->assertStringContainsString('admin-system-entry', $adminExport->streamedContent());
        }

        foreach ([
            'organization' => [$organization, 'org.audit', 'org.audit.export', 'organization-only-entry', 'volunteer-only-entry'],
            'volunteer' => [$volunteer, 'volunteer.audit', 'volunteer.audit.export', 'volunteer-only-entry', 'organization-only-entry'],
        ] as [$account, $pageRoute, $exportRoute, $ownedText, $otherText]) {
            $this->actingAs($account)
                ->get(route($pageRoute))
                ->assertOk()
                ->assertSee($ownedText)
                ->assertDontSee($otherText);

            foreach (['pdf', 'xls', 'csv'] as $format) {
                $response = $this->get(route($exportRoute, ['format' => $format]));
                $response->assertOk();
                $contents = $response->streamedContent();
                $this->assertStringContainsString($ownedText, $contents);
                $this->assertStringNotContainsString($otherText, $contents);
                $this->assertStringContainsString('.'.$format, $response->headers->get('content-disposition'));
            }
        }
    }

    public function test_each_role_dashboard_renders_with_schedule_data_and_report_audit_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $organization = User::factory()->create(['role' => 'organization', 'status' => 'approved']);
        $volunteer = User::factory()->create(['role' => 'volunteer', 'status' => 'approved']);
        $start = ScheduleClock::now()->addHour();
        $event = Event::create([
            'organization_id' => $organization->id,
            'title' => 'Dashboard Schedule Check',
            'description' => 'A visible current dashboard event.',
            'location' => 'Manila',
            'status' => 'published',
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
        ]);
        Task::create([
            'event_id' => $event->id,
            'title' => 'Dashboard Task Check',
            'status' => 'pending',
            'due_date' => $start->copy()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
        $this->actingAs($organization)
            ->get(route('org.dashboard'))
            ->assertOk()
            ->assertSee(route('org.reports'), false)
            ->assertSee(route('org.audit'), false);
        $this->actingAs($volunteer)
            ->get(route('volunteer.dashboard'))
            ->assertOk()
            ->assertSee(route('volunteer.reports'), false)
            ->assertSee(route('volunteer.audit'), false);
    }

    private function recordAudit(User $user, string $description): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'actor_role' => $user->role,
            'action' => $description,
            'action_type' => 'other',
            'module' => 'System',
            'description' => $description,
            'status' => 'success',
        ]);
    }
}
