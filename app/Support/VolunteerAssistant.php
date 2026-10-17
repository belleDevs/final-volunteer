<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\ChatbotRule;
use App\Models\Event;
use App\Models\Officer;
use App\Models\OfficerAlbum;
use App\Models\TaskApplication;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VolunteerAssistant
{
    public const HANDOFF = 'I don\'t have a confident answer for that from your records. I\'ve notified your organization — they can reply in this same Messages chat.';

    public const CONFIDENTIAL = 'I can\'t share that — it\'s confidential. I only talk about your own account and public organization details saved in VolunteerHub.';

    /**
     * Answer from this volunteer's records, public org data, and system help.
     * A null answer means the organization has to take over.
     *
     * @return array{response: string, intent: string, confidence: float}|null
     */
    public function answer(User $volunteer, string $message, ?User $organization): ?array
    {
        $text = mb_strtolower(trim(preg_replace('/\s+/', ' ', $message) ?? ''));
        if ($text === '') {
            return null;
        }

        if ($refusal = $this->confidentialReply($text)) {
            return $refusal;
        }

        if ($this->wantsHumanAgent($text)) {
            return $this->answerRequestAgent($organization);
        }

        if ($this->wantsAssistantBack($text)) {
            return $this->answerRequestAssistant($organization);
        }

        if ($this->isGreeting($text)) {
            $orgName = $organization->name ?? 'your organization';

            return $this->ok(
                'Hi '.$volunteer->name.'! I can help with your VolunteerHub account, '.$orgName.', and how this portal works.',
                "I understand the 5Ws and How — for example:\n• Who is the president?\n• What are my open tasks?\n• When / where is my next event?\n• Why do I submit evidence?\n• How do I accept a task?",
                'If I don\'t know, or you say you want to talk to a person, '.$orgName.' can reply in this same Messages chat.',
                'greeting',
                0.7
            );
        }

        $personal = $this->fromRecords($volunteer, $text, $organization);
        if ($personal !== null) {
            return $personal;
        }

        foreach (ChatbotRule::query()->orderByDesc('keyword')->get() as $rule) {
            $keyword = mb_strtolower(trim($rule->keyword));
            if ($keyword === '' || mb_strlen($keyword) < 3) {
                continue;
            }
            if (str_contains($text, $keyword)) {
                return [
                    'response' => trim($rule->response),
                    'intent' => $keyword,
                    'confidence' => 0.75,
                ];
            }
        }

        return null;
    }

    public static function handoffMessage(?User $organization): string
    {
        $org = $organization->name ?? 'your organization';

        return 'I don\'t have a confident answer for that from your records. I\'ve notified '.$org
            .' — they can reply in this same Messages chat. Keep typing here, or open Messages from the sidebar.';
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerRequestAgent(?User $organization): array
    {
        $org = $organization->name ?? 'your organization';

        return $this->ok(
            'Got it — I\'ve asked '.$org.' to talk with you directly.',
            'They\'ll reply in this same Messages conversation. You can keep sending notes here while you wait.',
            'When you want me back, say “talk to the assistant” or “back to AI”.',
            'request_agent',
            1.0
        );
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    public function answerRequestAssistant(?User $organization): array
    {
        $org = $organization->name ?? 'your organization';

        return $this->ok(
            'You\'re back with the VolunteerHub assistant.',
            'I can help again with your account, '.$org.', and how this portal works.',
            'Say “talk to my organization” anytime if you need a person again.',
            'request_assistant',
            1.0
        );
    }

    public function messageRequestsAssistant(string $message): bool
    {
        $text = mb_strtolower(trim(preg_replace('/\s+/', ' ', $message) ?? ''));

        return $text !== '' && $this->wantsAssistantBack($text);
    }

    public function messageRequestsAgent(string $message): bool
    {
        $text = mb_strtolower(trim(preg_replace('/\s+/', ' ', $message) ?? ''));

        return $text !== '' && $this->wantsHumanAgent($text);
    }

    /**
     * @return array{response: string, intent: string, confidence: float}|null
     */
    private function fromRecords(User $volunteer, string $text, ?User $organization): ?array
    {
        $eventIds = $organization
            ? Event::where('organization_id', $organization->id)->pluck('id')
            : null;

        $lens = $this->questionLens($text);

        if ($this->asksAboutOfficers($text)) {
            return $this->answerOfficers($organization, $text, $lens);
        }

        if ($this->asksAboutRegister($text)) {
            return $this->answerRegister($volunteer, $text);
        }

        if ($this->mentions($text, ['evidence', 'proof', 'submit work', 'submit evidence'])
            || ($lens === 'why' && $this->mentions($text, ['submit', 'hours', 'certificate']))) {
            $orgLabel = $organization->name ?? 'your organization';

            if ($lens === 'how') {
                return $this->ok(
                    'Here\'s how to submit evidence:',
                    "1. Open Assigned Tasks\n2. Pick an accepted role\n3. Tap Submit evidence and attach a photo or file\n4. Wait for {$orgLabel} to review it",
                    'They use that proof to approve your hours and certificates.',
                    'how_evidence'
                );
            }

            return $this->ok(
                $orgLabel.' asks for evidence so they can confirm you finished the role.',
                "Once they approve it, your hours can count and certificates can be issued.",
                'Open Assigned Tasks → Submit evidence → attach a file.',
                'why_evidence'
            );
        }

        if ($this->mentions($text, ['accept', 'decline', 'respond to']) || ($lens === 'how' && $this->asksAboutTasks($text))) {
            return $this->ok(
                'Here\'s how to handle a task:',
                "1. Open Assigned Tasks\n2. Accept or Decline while the event is still open\n3. After you accept, do the work and submit evidence\n4. Your organization confirms completion and hours",
                null,
                'how_tasks'
            );
        }

        if ($this->asksAboutAvailability($text) || ($lens === 'how' && str_contains($text, 'availab'))) {
            return $this->answerAvailability($volunteer, $organization);
        }

        if ($this->asksAboutAccount($text)) {
            return $this->answerAccount($volunteer, $organization, $text, $lens);
        }

        if ($this->asksAboutNotifications($text)) {
            return $this->answerNotifications($volunteer, $lens);
        }

        if ($this->asksAboutMessages($text)) {
            return $this->answerMessages($organization, $lens);
        }

        if ($this->asksAboutApplications($text)) {
            return $this->answerApplications($volunteer, $eventIds, $lens);
        }

        if ($this->asksAboutSkills($text)) {
            return $this->answerSkills($volunteer, $lens);
        }

        if ($this->asksAboutCertificates($text)) {
            return $this->answerCertificates($volunteer, $organization, $eventIds, $lens);
        }

        if ($this->asksAboutHours($text)) {
            $hours = number_format((float) $this->assignments($volunteer, $eventIds)
                ->where('status', 'completed')
                ->sum('hours_logged'), 1);
            $with = $organization ? ' with '.$organization->name : '';

            return $this->ok(
                "You have {$hours} approved volunteer hours{$with}.",
                'Hours only count after the organization approves your submitted evidence.',
                null,
                'hours'
            );
        }

        if ($this->asksAboutMembership($text)) {
            return $this->answerMembership($volunteer, $organization, $lens);
        }

        if ($this->asksAboutTasks($text)) {
            return $this->answerTasks($volunteer, $text, $organization, $eventIds, $lens);
        }

        if ($this->asksAboutEvents($text)) {
            return $this->answerEvents($volunteer, $text, $organization, $eventIds, $lens);
        }

        if ($this->asksAboutWho($text, $organization)) {
            return $this->answerWho($volunteer, $organization, $text, $eventIds);
        }

        $orgName = mb_strtolower($organization->name ?? '');
        if ($organization && (
            $this->mentions($text, ['organization', 'about jci', 'jci', 'my org', 'the org', 'chapter'])
            || ($orgName !== '' && str_contains($text, $orgName))
        )) {
            return $this->answerOrganization($organization, $lens);
        }

        if ($this->asksAboutSystem($text)) {
            return $this->answerSystem($organization);
        }

        if (in_array($lens, ['who', 'what', 'when', 'where', 'why', 'how'], true)) {
            return $this->ok(
                'I can help with your account and this portal. A few ideas:',
                "• Who is the president / who are the officers?\n• What are my tasks or hours?\n• When / where is my event?\n• How do I accept a task or message the organization?",
                'I won\'t share confidential info like passwords or other volunteers\' private details.',
                'guide_'.$lens,
                0.65
            );
        }

        return null;
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function ok(string $answer, ?string $detail = null, ?string $tip = null, string $intent = 'general', float $confidence = 0.9): array
    {
        $parts = [rtrim($answer)];
        if (filled($detail)) {
            $parts[] = rtrim($detail);
        }
        if (filled($tip)) {
            $parts[] = rtrim($tip);
        }

        return [
            'response' => implode("\n\n", $parts),
            'intent' => $intent,
            'confidence' => $confidence,
        ];
    }

    /**
     * @return array{response: string, intent: string, confidence: float}|null
     */
    private function confidentialReply(string $text): ?array
    {
        if ($this->mentions($text, [
            'password hash', 'hashed password', 'reset token', 'remember token',
            'api key', 'secret key', 'private key', '.env', 'database password',
            'admin password', 'org password', 'someone else\'s password', 'other password',
        ]) || (
            ! $this->mentions($text, ['change password', 'reset password', 'update password', 'forgot password'])
            && (
                preg_match('/\b(what|tell|show|give|reveal|share)\b.{0,40}\b(my|the|our)?\s*password\b/u', $text)
                || preg_match('/\bpassword\b.{0,20}\b(is|of mine|for me)\b/u', $text)
            )
        )) {
            return $this->ok(
                'I can\'t show passwords — that\'s confidential and VolunteerHub never displays them.',
                'To change yours, open Settings and enter a new password there.',
                null,
                'confidential_password',
                1.0
            );
        }

        $otherPeople = $this->mentions($text, [
            'other volunteer', 'another volunteer', 'other member', 'another member',
            'all volunteers', 'every volunteer', 'list of volunteers', 'volunteer list',
            'everyone\'s email', 'everyone\'s phone', 'their password', 'his password', 'her password',
            'someone\'s email', 'someone\'s phone', 'other people\'s', 'other person\'s',
            'phone directory', 'member directory', 'contact list of',
        ]) || (bool) preg_match('/\b(email|phone|address|birthday|password|hours|certificate)\b.{0,40}\b(of|for)\b.{0,40}\b(volunteer|member|user|person|someone|everybody|everyone)\b/u', $text);

        $adminInternal = $this->mentions($text, [
            'admin panel', 'admin dashboard', 'admin account', 'superuser',
            'verification document', 'uploaded document', 'id card', 'passport number',
            'bank account', 'credit card', 'debit card', 'gcash pin', 'otp code',
            'sql', 'database dump', 'server ip', 'ssh', 'root access',
            'other applicant', 'all applicants', 'applicant list',
        ]);

        if ($otherPeople || $adminInternal) {
            return $this->ok(
                self::CONFIDENTIAL,
                'Ask about your own tasks, events, hours, officers, or how to use the portal — or send a message to your organization.',
                null,
                'confidential',
                1.0
            );
        }

        return null;
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerOfficers(?User $organization, string $text, string $lens): array
    {
        if (! $organization) {
            return $this->ok(
                'I can show officers once you\'re linked to an organization.',
                null,
                null,
                'officers',
                0.8
            );
        }

        $album = OfficerAlbum::where('organization_id', $organization->id)
            ->with('officers')
            ->orderByDesc('year')
            ->first();

        if (! $album || $album->officers->isEmpty()) {
            return $this->ok(
                $organization->name.' hasn\'t published officers in VolunteerHub yet.',
                'You can ask them in Messages, or check again later.',
                null,
                'officers',
                0.88
            );
        }

        $officers = $album->officers
            ->sortBy(fn (Officer $officer) => Officer::rank($officer->position))
            ->values();

        $wantsPresident = (bool) preg_match('/\bpresident\b/u', $text)
            && ! preg_match('/\b(vice|past)\s+president\b/u', $text);
        $wantsVice = (bool) preg_match('/\bvice\s*president\b/u', $text);
        $wantsSecretary = (bool) preg_match('/\bsecretary\b/u', $text);
        $wantsTreasurer = (bool) preg_match('/\btreasurer\b/u', $text);
        $wantsAuditor = (bool) preg_match('/\bauditor\b/u', $text);

        $matchPosition = function (string $needle) use ($officers): ?Officer {
            return $officers->first(function (Officer $officer) use ($needle) {
                $position = mb_strtolower($officer->position);

                if ($needle === 'president') {
                    return str_contains($position, 'president')
                        && ! str_contains($position, 'vice')
                        && ! str_contains($position, 'past');
                }

                return str_contains($position, $needle);
            });
        };

        if ($wantsPresident) {
            $president = $matchPosition('president');
            if ($president) {
                return $this->ok(
                    $president->name.' is the President of '.$organization->name.' for '.$album->year.'.',
                    null,
                    null,
                    'officers_president',
                    0.96
                );
            }

            return $this->ok(
                'There\'s no President listed for '.$album->year.' yet. Here\'s who is on record:',
                $this->officerLines($officers),
                null,
                'officers_president_missing'
            );
        }

        foreach ([
            ['vice president', $wantsVice],
            ['secretary', $wantsSecretary],
            ['treasurer', $wantsTreasurer],
            ['auditor', $wantsAuditor],
        ] as [$title, $wants]) {
            if (! $wants) {
                continue;
            }
            $found = $matchPosition($title);
            if ($found) {
                return $this->ok(
                    $found->name.' is the '.Str::title($title).' of '.$organization->name.' for '.$album->year.'.',
                    null,
                    null,
                    'officers_role',
                    0.95
                );
            }
        }

        $intro = $lens === 'when'
            ? 'These are the '.$album->year.' officers for '.$organization->name.':'
            : 'Here are the '.$album->year.' officers for '.$organization->name.':';

        return $this->ok($intro, $this->officerLines($officers), null, 'officers', 0.94);
    }

    private function officerLines(Collection $officers): string
    {
        return $officers->map(fn (Officer $officer) => '• '.$officer->position.' — '.$officer->name)->join("\n");
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerAccount(User $volunteer, ?User $organization, string $text, string $lens): array
    {
        if ($this->mentions($text, ['password', 'change password', 'reset password', 'update password'])) {
            return $this->ok(
                'To change your password, open Settings and enter your current password plus a new one.',
                'I can\'t show or recover your password — that stays private.',
                null,
                'account_password',
                0.95
            );
        }

        if ($this->mentions($text, ['email', 'e-mail'])) {
            return $this->ok(
                'Your account email is '.$volunteer->email.'.',
                'You can update it in Settings if needed.',
                null,
                'account_email',
                0.94
            );
        }

        if ($this->mentions($text, ['phone', 'contact number', 'mobile'])) {
            $phone = filled($volunteer->phone) ? $volunteer->phone : 'not saved yet';

            return $this->ok(
                'Your contact number is '.$phone.'.',
                'Update it anytime on Profile.',
                null,
                'account_phone',
                0.94
            );
        }

        if ($this->mentions($text, ['photo', 'avatar', 'picture', 'profile picture'])) {
            return $this->ok(
                'Open Profile, choose a new photo, then save.',
                null,
                null,
                'account_photo'
            );
        }

        if ($lens === 'how' || $this->mentions($text, ['setting', 'update profile', 'edit profile', 'change profile'])) {
            return $this->ok(
                'Here\'s where to update your account:',
                "• Profile — name, photo, contact number, bio, interests, experience\n• Settings — password, email, notification choices",
                null,
                'account'
            );
        }

        $bits = collect([
            '• Name — '.$volunteer->name,
            '• Email — '.$volunteer->email,
            filled($volunteer->phone) ? '• Phone — '.$volunteer->phone : null,
            '• Availability — '.$this->availabilityLabel($volunteer),
            $organization ? '• Organization — '.$organization->name : '• Organization — not linked yet',
            $volunteer->isMember() ? '• Membership — approved' : '• Membership — not approved yet',
        ])->filter()->join("\n");

        return $this->ok(
            'Here\'s what\'s on your account:',
            $bits,
            'Edit Profile or Settings to make changes.',
            'account',
            0.92
        );
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerAvailability(User $volunteer, ?User $organization): array
    {
        $orgLabel = $organization->name ?? 'your organization';

        return $this->ok(
            'Your availability is set to '.$this->availabilityLabel($volunteer).'.',
            'Update it on the Availability page so '.$orgLabel.' knows when you can take roles. Changing it won\'t cancel tasks you already accepted.',
            null,
            'availability'
        );
    }

    private function availabilityLabel(User $volunteer): string
    {
        return match ($volunteer->availability) {
            'inactive' => 'Unavailable',
            'limited' => 'Busy / Limited',
            default => 'Available',
        };
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerNotifications(User $volunteer, string $lens): array
    {
        $unread = $volunteer->unreadNotifications()->count();
        $countLine = $unread === 0
            ? 'You\'re all caught up — no unread notifications.'
            : 'You have '.$unread.' unread notification'.($unread === 1 ? '' : 's').'.';

        if ($lens === 'how') {
            return $this->ok(
                $countLine,
                "Open Notifications (or the bell), tap a notice to read it, or use Mark all as read.",
                null,
                'notifications_how',
                0.92
            );
        }

        return $this->ok($countLine, 'Open Notifications to review them.', null, 'notifications', 0.92);
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerMessages(?User $organization, string $lens): array
    {
        $org = $organization->name ?? 'your organization';

        if ($lens === 'how') {
            return $this->ok(
                'Open Messages (or the floating chat), type your note, and send it.',
                'I answer first from your records. If I can\'t, '.$org.' is notified and can reply in the same thread.',
                null,
                'messages'
            );
        }

        return $this->ok(
            'Messages is one conversation: me (the assistant) and '.$org.'.',
            'Ask anything here — I reply when I can, and they can reply in the same place.',
            null,
            'messages'
        );
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerSystem(?User $organization): array
    {
        $org = $organization->name ?? 'your organization';

        return $this->ok(
            'VolunteerHub is the volunteering portal used by '.$org.' and its volunteers.',
            "Common things people ask:\n"
                ."• What is this? — Manage events, tasks, hours, and certificates in one place\n"
                ."• Who am I talking to? — Me (assistant) first; ".$org." can take over in the same Messages chat\n"
                ."• What can I do? — Events, Assigned Tasks, Skills, Availability, Certificates, Notifications, Messages, Profile, Settings\n"
                ."• How does work get verified? — Accept a task → do the work → Submit evidence → ".$org." approves hours\n"
                ."• How do certificates work? — After your event tasks are approved, ".$org." can issue a certificate\n"
                ."• Roles vs skills — Organizations create roles (tasks); you save matching skills so they can assign you",
            'Ask a Who / What / When / Where / Why / How question about your account or '.$org.', or say “I want to talk to my organization.”',
            'system',
            0.93
        );
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerTasks(User $volunteer, string $text, ?User $organization, $eventIds, string $lens): array
    {
        $assignments = $this->assignments($volunteer, $eventIds);
        $open = $assignments->filter(fn (Assignment $a) => in_array($a->status, ['pending', 'approved', 'submitted', 'rejected'], true))->values();
        $completed = $assignments->where('status', 'completed')->values();
        $preferOpen = in_array($lens, ['what', 'when', 'where', 'who', 'how'], true)
            || $this->asksCurrent($text)
            || $this->asksNext($text)
            || $this->asksUpcoming($text);

        if ($preferOpen) {
            if ($open->isEmpty()) {
                $with = $organization ? ' from '.$organization->name : '';

                return $this->ok(
                    'You don\'t have any open tasks'.$with.' right now.',
                    $completed->isNotEmpty()
                        ? 'You have '.$completed->count().' completed task'.($completed->count() === 1 ? '' : 's').' on record — ask “what are my completed tasks?” if you want those.'
                        : 'Check Assigned Tasks again after your organization gives you a role.',
                    null,
                    'tasks'
                );
            }
            $focus = $open;
        } else {
            $focus = $assignments;
        }

        if ($focus->isEmpty()) {
            $with = $organization ? ' from '.$organization->name : '';

            return $this->ok(
                'You don\'t have any assigned tasks'.$with.' right now.',
                'Check Assigned Tasks again after your organization gives you a role.',
                null,
                'tasks'
            );
        }

        $focus = $this->sortAssignments($focus)->take(8);
        $org = $organization->name ?? 'Your organization';

        if ($lens === 'who') {
            return $this->ok(
                $org.' assigned these roles to you:',
                $focus->map(fn (Assignment $a) => '• '.$this->taskTitle($a).' — '.($a->event->title ?? 'Event'))->join("\n"),
                null,
                'tasks_who',
                0.92
            );
        }

        if ($lens === 'when') {
            return $this->ok(
                'Here\'s when your tasks are scheduled:',
                $focus->map(fn (Assignment $a) => '• '.$this->taskTitle($a)."\n  ".$this->assignmentWhen($a).' · '.$this->taskStatus($a))->join("\n"),
                'Manage them on Assigned Tasks.',
                'tasks_when',
                0.94
            );
        }

        if ($lens === 'where') {
            return $this->ok(
                'Here\'s where your tasks are held:',
                $focus->map(function (Assignment $assignment) {
                    $location = $assignment->event?->location ?: 'Location not saved';

                    return '• '.$this->taskTitle($assignment).' — '.$location.' ('.($assignment->event->title ?? 'Event').')';
                })->join("\n"),
                null,
                'tasks_where',
                0.94
            );
        }

        if ($lens === 'why') {
            return $this->ok(
                'These are roles '.$org.' assigned for specific events.',
                "Finishing them and submitting evidence lets them approve your hours — and that can lead to certificates.\n\n"
                    .$focus->map(fn (Assignment $a) => '• '.$this->taskTitle($a).' — '.$this->taskStatus($a))->join("\n"),
                null,
                'tasks_why'
            );
        }

        if ($lens === 'how') {
            return $this->ok(
                'To finish a task:',
                "1. Open Assigned Tasks\n2. Accept if it\'s still waiting for you\n3. Do the work, then submit evidence\n4. Wait for organization approval\n\n"
                    .'Open for you now:'."\n"
                    .$focus->map(fn (Assignment $a) => '• '.$this->taskTitle($a).' — '.$this->taskStatus($a))->join("\n"),
                null,
                'tasks_how',
                0.93
            );
        }

        $lines = $focus->map(function (Assignment $assignment) {
            $line = '• '.$this->taskTitle($assignment)
                .' — '.$this->taskStatus($assignment)."\n  "
                .($assignment->event->title ?? 'Event').' · '.$this->assignmentWhen($assignment);

            if (filled($assignment->task?->description)) {
                $line .= "\n  ".Str::limit(trim($assignment->task->description), 110);
            }

            return $line;
        })->join("\n");

        $extra = ($preferOpen && $completed->isNotEmpty() && $open->isNotEmpty())
            ? "\n\nYou also have ".$completed->count().' completed task'.($completed->count() === 1 ? '' : 's').' on record.'
            : '';

        return $this->ok(
            'Here are your tasks:',
            $lines.$extra,
            'Open Assigned Tasks to accept, decline, or submit evidence.',
            'tasks_what',
            0.94
        );
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerEvents(User $volunteer, string $text, ?User $organization, $eventIds, string $lens): array
    {
        $eventIdList = $this->assignments($volunteer, $eventIds)->pluck('event_id')->unique()->filter();
        $events = Event::whereIn('id', $eventIdList->isEmpty() ? [-1] : $eventIdList)
            ->orderBy('start_time')
            ->get();

        $upcoming = $events->filter(fn (Event $e) => $this->isEventLiveOrUpcoming($e))
            ->sortBy(fn (Event $e) => $e->start_time?->timestamp ?? PHP_INT_MAX)
            ->values();
        $past = $events->filter(fn (Event $e) => ! $this->isEventLiveOrUpcoming($e))
            ->sortByDesc(fn (Event $e) => $e->start_time?->timestamp ?? 0)
            ->values();

        $wantsNext = $this->asksNext($text);
        $wantsUpcomingOnly = $wantsNext || $this->asksUpcoming($text) || in_array($lens, ['when', 'how'], true);
        $wantsPast = $this->asksPast($text);

        if ($wantsPast) {
            $focus = $past->take(8);
            if ($focus->isEmpty()) {
                return $this->ok('You don\'t have any past events on record yet.', null, null, 'events_past');
            }
        } elseif ($wantsNext) {
            $focus = $upcoming->take(1);
            if ($focus->isEmpty()) {
                return $this->ok(
                    'You don\'t have an upcoming event right now.',
                    $past->isNotEmpty()
                        ? 'Your latest finished event was '.$past->first()->title.' ('.$past->first()->oversightLabel().').'
                        : null,
                    'Check My Events again after your organization assigns you to a new one.',
                    'events_next'
                );
            }
        } elseif ($wantsUpcomingOnly) {
            $focus = $upcoming->take(8);
            if ($focus->isEmpty()) {
                return $this->ok(
                    'You don\'t have any upcoming events.',
                    $past->isNotEmpty()
                        ? 'You have '.$past->count().' past event'.($past->count() === 1 ? '' : 's').' on record — ask “what were my past events?” if you want those.'
                        : null,
                    null,
                    'events_upcoming'
                );
            }
        } else {
            // General “my events” — show upcoming first; only list past if nothing live.
            $focus = $upcoming->isNotEmpty() ? $upcoming->take(8) : $past->take(8);
            if ($focus->isEmpty()) {
                $with = $organization ? ' with '.$organization->name : '';

                return $this->ok('You\'re not assigned to an event'.$with.' yet.', null, null, 'events');
            }
        }

        if ($wantsNext) {
            $event = $focus->first();

            return $this->ok(
                'Your next event is '.$event->title.'.',
                ScheduleClock::range($event->start_time, $event->end_time)
                    .(filled($event->location) ? ' · '.$event->location : '')
                    .' · '.$event->oversightLabel(),
                'Open My Events for full details.',
                'events_next',
                0.97
            );
        }

        if ($lens === 'when') {
            return $this->ok(
                $wantsPast ? 'Here\'s when your past events happened:' : 'Here\'s when your upcoming events are:',
                $focus->map(fn (Event $e) => '• '.$e->title."\n  ".ScheduleClock::range($e->start_time, $e->end_time).' · '.$e->oversightLabel())->join("\n"),
                'See more on My Events.',
                'events_when',
                0.94
            );
        }

        if ($lens === 'where') {
            return $this->ok(
                $wantsPast ? 'Here\'s where your past events were held:' : 'Here\'s where your upcoming events are held:',
                $focus->map(fn (Event $e) => '• '.$e->title.' — '.(filled($e->location) ? $e->location : 'Location not saved'))->join("\n"),
                null,
                'events_where',
                0.94
            );
        }

        if ($lens === 'who') {
            return $this->ok(
                ($organization->name ?? 'Your organization').' hosts these events you\'re part of:',
                $focus->map(fn (Event $e) => '• '.$e->title)->join("\n"),
                null,
                'events_who'
            );
        }

        if ($lens === 'why') {
            $orgLabel = $organization->name ?? 'Your organization';

            return $this->ok(
                'You see these events because '.$orgLabel.' assigned you a role on them.',
                $focus->map(fn (Event $e) => '• '.$e->title.' — '.$e->oversightLabel())->join("\n"),
                null,
                'events_why'
            );
        }

        if ($lens === 'how') {
            return $this->ok(
                'To prepare for an upcoming event:',
                "1. Check My Events for the schedule and place\n2. Open Assigned Tasks for your role\n3. Accept, attend, then submit evidence\n\n"
                    .$focus->map(fn (Event $e) => '• '.$e->title.' — '.ScheduleClock::range($e->start_time, $e->end_time).(filled($e->location) ? ' · '.$e->location : ''))->join("\n"),
                null,
                'events_how',
                0.92
            );
        }

        $lines = $focus->map(function (Event $event) {
            $line = '• '.$event->title."\n  ".ScheduleClock::range($event->start_time, $event->end_time)
                .(filled($event->location) ? ' · '.$event->location : '')
                .' · '.$event->oversightLabel();

            if (filled($event->description)) {
                $line .= "\n  ".Str::limit(preg_replace('/\s+/', ' ', $event->description), 120);
            }

            return $line;
        })->join("\n");

        return $this->ok(
            $wantsPast ? 'Here are your past events:' : 'Here are your upcoming events:',
            $lines,
            'Open My Events for full details.',
            'events_what',
            0.94
        );
    }

    private function isEventLiveOrUpcoming(Event $event): bool
    {
        if (in_array($event->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        return in_array(
            ScheduleClock::phase($event->start_time, $event->end_time, $event->status),
            ['upcoming', 'now'],
            true
        );
    }

    private function asksNext(string $text): bool
    {
        return (bool) preg_match('/\bnext\b/u', $text)
            || $this->mentions($text, ['nearest event', 'soonest event']);
    }

    private function asksUpcoming(string $text): bool
    {
        return $this->mentions($text, [
            'upcoming', 'coming up', 'coming soon', 'future event', 'not yet',
            'haven\'t started', 'still ahead',
        ]);
    }

    private function asksPast(string $text): bool
    {
        return $this->mentions($text, [
            'past event', 'previous event', 'completed event', 'finished event',
            'old event', 'already ended', 'were my events', 'history of events',
        ]);
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerWho(User $volunteer, ?User $organization, string $text, $eventIds): array
    {
        if ($this->asksAboutTasks($text)) {
            return $this->answerTasks($volunteer, $text, $organization, $eventIds, 'who');
        }
        if ($this->asksAboutEvents($text)) {
            return $this->answerEvents($volunteer, $text, $organization, $eventIds, 'who');
        }

        if ($organization) {
            $bits = collect([
                'You\'re signed in as '.$volunteer->name
                    .($volunteer->isMember() ? ', an approved member of '.$organization->name.'.' : ', with membership still pending at '.$organization->name.'.'),
                filled($organization->representative_name) ? 'Org contact: '.$organization->representative_name : null,
                filled($organization->contact_email) ? 'Email: '.$organization->contact_email : null,
            ])->filter()->join(' ');

            return $this->ok(
                $bits,
                'Ask “Who is the president?” if you want the current leadership.',
                null,
                'who'
            );
        }

        return $this->ok(
            'You\'re signed in as '.$volunteer->name.', but no organization is linked yet.',
            null,
            null,
            'who',
            0.85
        );
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerOrganization(User $organization, string $lens): array
    {
        $bio = trim((string) $organization->bio);
        $where = filled($organization->address) ? $organization->address : 'Address not saved yet';
        $contact = collect([
            filled($organization->representative_name) ? '• Contact — '.$organization->representative_name : null,
            filled($organization->contact_email) ? '• Email — '.$organization->contact_email : null,
            filled($organization->phone) ? '• Phone — '.$organization->phone : null,
        ])->filter()->join("\n");

        return match ($lens) {
            'who' => $this->ok(
                'Your organization is '.$organization->name.'.',
                $contact !== '' ? $contact : null,
                'Ask “Who is the president?” for officers.',
                'org_who'
            ),
            'where' => $this->ok(
                $organization->name.' is listed at '.$where.'.',
                null,
                null,
                'org_where'
            ),
            'why' => $this->ok(
                $bio !== '' ? $bio : $organization->name.' hasn\'t saved a description yet.',
                null,
                null,
                'org_why',
                0.88
            ),
            'how' => $this->ok(
                'Working with '.$organization->name.' looks like this:',
                "1. Finish membership interview if you\'re still an applicant\n2. Accept assigned tasks\n3. Use Messages when you need a person",
                null,
                'org_how',
                0.88
            ),
            default => $this->ok(
                $organization->name.($bio !== '' ? "\n\n".$bio : ''),
                ($contact !== '' ? $contact."\n" : '').'• Location — '.$where,
                'Ask about officers if you want leadership names.',
                'organization',
                0.88
            ),
        };
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerSkills(User $volunteer, string $lens): array
    {
        $skills = $volunteer->skills()->orderBy('name')->pluck('name');
        $list = $skills->isEmpty()
            ? 'You haven\'t saved any skills yet.'
            : "Your skills:\n".$skills->map(fn ($name) => '• '.$name)->join("\n");

        return match ($lens) {
            'how' => $this->ok(
                'Add or remove skills on Skill Recommendations — pick from the catalog or type a custom one.',
                $list,
                null,
                'skills_how'
            ),
            'why' => $this->ok(
                'Skills help organizations match you to the right roles.',
                $list,
                null,
                'skills_why',
                0.88
            ),
            default => $this->ok($list, null, null, 'skills', 0.88),
        };
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerApplications(User $volunteer, $eventIds, string $lens): array
    {
        $applications = TaskApplication::where('user_id', $volunteer->id)
            ->when($eventIds !== null, fn ($query) => $query->whereIn('event_id', $eventIds->isEmpty() ? [-1] : $eventIds))
            ->with(['task', 'event'])
            ->latest()
            ->get();

        if ($applications->isEmpty()) {
            return $this->ok('You don\'t have any task applications on record.', null, null, 'applications', 0.86);
        }

        $lines = $applications->take(8)->map(function (TaskApplication $application) {
            return '• '.($application->task->title ?? 'Task').' — '.($application->event->title ?? 'Event')
                .' ('.str_replace('_', ' ', $application->status).')';
        })->join("\n");

        $intro = match ($lens) {
            'when' => 'Here\'s the status of your applications:',
            'who' => 'Here\'s what you applied for:',
            'how' => 'The organization reviews applications, then may assign the role. Yours:',
            default => 'Here are your applications:',
        };

        return $this->ok($intro, $lines, null, 'applications', 0.88);
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerCertificates(User $volunteer, ?User $organization, $eventIds, string $lens): array
    {
        $certificates = Certificate::where('user_id', $volunteer->id)
            ->when($organization, fn ($query) => $query->whereIn('event_id', $eventIds))
            ->with('event')
            ->orderByDesc('issued_at')
            ->get();

        if ($certificates->isEmpty()) {
            return $this->ok(
                'You don\'t have any certificates yet.',
                'Finish assigned tasks, submit evidence, and wait for organization approval — then certificates can be issued.',
                null,
                'certificates',
                0.88
            );
        }

        $lines = $certificates->take(8)->map(function (Certificate $certificate) {
            $when = $certificate->issued_at ? ScheduleClock::stamp($certificate->issued_at) : null;

            return '• '.($certificate->event->title ?? 'Event')
                .($when ? ' — issued '.$when : '')
                .($certificate->certificate_code ? ' · '.$certificate->certificate_code : '');
        })->join("\n");

        return match ($lens) {
            'when' => $this->ok('Here\'s when your certificates were issued:', $lines, null, 'certificates_when', 0.92),
            'how' => $this->ok(
                'Complete tasks → submit evidence → get approval → download from Certificates.',
                "You already have:\n".$lines,
                null,
                'certificates_how'
            ),
            'why' => $this->ok(
                'Certificates recognize completed volunteer service after approval.',
                $lines,
                null,
                'certificates_why'
            ),
            default => $this->ok('Here are your certificates:', $lines, null, 'certificates'),
        };
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerMembership(User $volunteer, ?User $organization, string $lens): array
    {
        $application = $volunteer->pendingOrganizationApplication();
        $org = $organization ?? $application?->organization;

        if ($volunteer->isMember() && $org) {
            return $this->ok(
                'You\'re an approved member of '.$org->name.'.',
                null,
                null,
                'membership'
            );
        }

        if ($application && $org) {
            $when = $application->interview_at ? ScheduleClock::stamp($application->interview_at) : null;
            $where = $application->interview_place ?: null;
            $status = $application->statusLabel();

            return match ($lens) {
                'when' => $this->ok(
                    $when
                        ? 'Your interview with '.$org->name.' is set for '.$when.($where ? ' at '.$where : '').'.'
                        : $org->name.' hasn\'t scheduled your interview yet. Status: '.$status.'.',
                    null,
                    null,
                    'membership'
                ),
                'where' => $this->ok(
                    $where
                        ? 'Your interview is at '.$where.($when ? ' on '.$when : '').'.'
                        : 'The interview place isn\'t set yet. Status: '.$status.'.',
                    null,
                    null,
                    'membership'
                ),
                'how' => $this->ok(
                    'Membership works like this:',
                    "1. Apply to an organization\n2. Attend the personal interview\n3. Wait for approval",
                    null,
                    'membership'
                ),
                'why' => $this->ok(
                    $org->name.' interviews applicants before approving membership.',
                    null,
                    null,
                    'membership'
                ),
                'who' => $this->ok(
                    'You applied to '.$org->name.'. Current status: '.$status.'.',
                    null,
                    null,
                    'membership'
                ),
                default => $this->ok(
                    'Your membership with '.$org->name.' is “'.$status.'”.',
                    $when
                        ? 'Interview: '.$when.($where ? ' at '.$where : '').'.'
                        : 'A personal interview is required before approval.',
                    null,
                    'membership'
                ),
            };
        }

        return $this->ok(
            'You don\'t have an organization membership yet.',
            'Apply during registration, or contact an organization for an interview.',
            null,
            'membership',
            0.85
        );
    }

    private function questionLens(string $text): string
    {
        if (preg_match('/\bwho\b/u', $text) || $this->mentions($text, ['who assigned', 'who is my', 'which organization'])) {
            return 'who';
        }
        if (preg_match('/\bwhen\b/u', $text) || $this->mentions($text, ['schedule', 'deadline', 'due date', 'what time', 'what date'])) {
            return 'when';
        }
        if (preg_match('/\bwhere\b/u', $text) || $this->mentions($text, ['location', 'venue', 'place', 'address'])) {
            return 'where';
        }
        if (preg_match('/\bwhy\b/u', $text) || $this->mentions($text, ['reason', 'purpose'])) {
            return 'why';
        }
        if (preg_match('/\bhow\b/u', $text) || $this->mentions($text, ['how do i', 'how can i', 'steps', 'process'])) {
            return 'how';
        }
        if (preg_match('/\b(what|which|list|show|tell me)\b/u', $text)) {
            return 'what';
        }

        return 'general';
    }

    private function taskTitle(Assignment $assignment): string
    {
        return $assignment->task->title ?? 'Task';
    }

    private function taskStatus(Assignment $assignment): string
    {
        return VolunteerDuty::label(
            $assignment->status,
            ScheduleClock::phase($assignment->event?->start_time, $assignment->event?->end_time, $assignment->event?->status),
            $assignment->feedback
        );
    }

    private function assignmentWhen(Assignment $assignment): string
    {
        $event = $assignment->event;
        $parts = [];

        if ($event?->start_time || $event?->end_time) {
            $parts[] = ScheduleClock::range($event->start_time, $event->end_time);
        }
        if ($assignment->task?->due_date) {
            $parts[] = 'Due '.ScheduleClock::format($assignment->task->due_date);
        }
        if ($event?->location) {
            $parts[] = $event->location;
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Schedule not saved';
    }

    private function sortAssignments(Collection $assignments): Collection
    {
        return $assignments->sortBy(function (Assignment $assignment) {
            $start = $assignment->event?->start_time?->timestamp ?? PHP_INT_MAX;
            $due = $assignment->task?->due_date?->timestamp ?? $start;

            return min($start, $due);
        })->values();
    }

    private function asksAboutOfficers(string $text): bool
    {
        return $this->mentions($text, [
            'officer', 'officers', 'president', 'vice president', 'secretary', 'treasurer', 'auditor',
            'board of', 'leadership', 'executive board', 'who leads', 'who is leading',
        ]) || (bool) preg_match('/\bwho\b.{0,40}\b(president|vp|secretary|treasurer|auditor|officer)\b/u', $text);
    }

    private function asksAboutRegister(string $text): bool
    {
        return $this->mentions($text, [
            'register', 'registration', 'sign up', 'signup', 'create an account', 'create account',
            'how to join', 'how do i join',
        ]);
    }

    /**
     * @return array{response: string, intent: string, confidence: float}
     */
    private function answerRegister(User $volunteer, string $text): array
    {
        $wantsOrg = $this->mentions($text, ['organization', 'organisation', 'org account', 'as an org', 'as organization']);

        if ($wantsOrg) {
            return $this->ok(
                'To register an organization on VolunteerHub:',
                "1. Go to the Login page and open Organization registration (/register/organization)\n2. Fill in the organization profile and contact details\n3. Submit for admin verification\n4. After approval, sign in and manage events, tasks, and volunteers",
                'You\'re currently signed in as volunteer '.$volunteer->name.'. Log out first (or use another browser) if you need to create an organization account.',
                'register_org',
                0.95
            );
        }

        return $this->ok(
            'Here\'s how registration works on VolunteerHub:',
            "Volunteer account\n1. Open Volunteer registration from the Login page (/register/volunteer)\n2. Enter your name, email, password, and profile details\n3. Choose an organization to apply to — membership still needs a personal interview and approval\n4. Sign in at Login\n\n"
                ."Organization account\n1. Open Organization registration (/register/organization)\n2. Submit organization details for admin verification\n3. After approval, manage events and volunteers from the org portal",
            'You\'re already signed in as '.$volunteer->name.'. If you meant joining or changing membership, ask about your membership or interview status.',
            'register',
            0.95
        );
    }

    private function asksAboutWho(string $text, ?User $organization): bool
    {
        if (! preg_match('/\bwho\b/u', $text) && ! $this->mentions($text, ['who assigned', 'my organization'])) {
            return false;
        }

        return ! $this->asksAboutTasks($text)
            && ! $this->asksAboutEvents($text)
            && ! $this->asksAboutOfficers($text);
    }

    private function asksAboutTasks(string $text): bool
    {
        return $this->mentions($text, ['task', 'assignment', 'duty', 'duties', 'assigned role', 'my role'])
            || (bool) preg_match('/\b(who|what|which|when|where|why|how|list|show|my).{0,48}\b(tasks?|assignments?|duties|roles?)\b/u', $text)
            || (bool) preg_match('/\b(tasks?|assignments?|duties|roles?).{0,30}\b(who|what|when|where|why|how|schedule|date|time|due)\b/u', $text);
    }

    private function asksAboutEvents(string $text): bool
    {
        return (bool) preg_match('/\b(who|what|which|when|where|why|how|list|show|my|upcoming).{0,48}\bevents?\b/u', $text)
            || (bool) preg_match('/\bevents?.{0,30}\b(who|what|when|where|why|how|schedule|date|time|location)\b/u', $text)
            || $this->mentions($text, ['my event', 'upcoming event', 'event schedule']);
    }

    private function asksAboutApplications(string $text): bool
    {
        return $this->mentions($text, ['application', 'applied', 'apply status', 'task application']);
    }

    private function asksAboutSkills(string $text): bool
    {
        return $this->mentions($text, ['skill']) && ! $this->asksAboutTasks($text);
    }

    private function asksAboutCertificates(string $text): bool
    {
        return $this->mentions($text, ['certific', 'award']);
    }

    private function asksAboutHours(string $text): bool
    {
        return $this->mentions($text, ['hour', 'hours logged', 'service hours', 'volunteer hours']);
    }

    private function asksAboutMembership(string $text): bool
    {
        return $this->mentions($text, ['membership', 'interview', 'become a member', 'join the organization', 'applicant', 'am i a member', 'member status']);
    }

    private function asksAboutAccount(string $text): bool
    {
        return $this->mentions($text, [
            'my account', 'my profile', 'my email', 'my phone', 'my name', 'my contact',
            'profile', 'password', 'setting', 'account', 'change my', 'update my',
            'logged in as', 'who am i',
        ]);
    }

    private function asksAboutAvailability(string $text): bool
    {
        return $this->mentions($text, ['availab', 'when can i volunteer', 'am i available']);
    }

    private function asksAboutNotifications(string $text): bool
    {
        return $this->mentions($text, ['notification', 'notif', 'unread', 'the bell', 'alerts']);
    }

    private function asksAboutMessages(string $text): bool
    {
        // Direct “talk to a person” requests are handled by wantsHumanAgent().
        if ($this->wantsHumanAgent($text)) {
            return false;
        }

        return $this->mentions($text, [
            'message', 'messages', 'inbox', 'how do i message', 'where are messages',
            'chat thread', 'conversation',
        ]);
    }

    private function wantsHumanAgent(string $text): bool
    {
        if ($this->wantsAssistantBack($text)) {
            return false;
        }

        return $this->mentions($text, [
            'talk to the organization', 'talk to my organization', 'talk to organization',
            'talk to the org', 'talk to my org', 'talk to org',
            'speak to the organization', 'speak to my organization', 'speak to a person',
            'speak to someone', 'talk to someone', 'talk to a human', 'talk to human',
            'human agent', 'real person', 'live agent', 'customer service',
            'message the organization', 'message my organization', 'message the org',
            'contact the organization', 'contact my organization', 'contact the org',
            'ask the organization', 'ask my organization', 'ask the org',
            'call the organization', 'need the organization', 'need my organization',
            'transfer me', 'hand me over', 'connect me to',
            'i want to talk to', 'i need to talk to', 'let me talk to',
            'can i talk to', 'can i speak to', 'please talk to',
        ]) || (bool) preg_match(
            '/\b(talk|speak|chat|message|contact|connect)\b.{0,24}\b(organization|organisation|org|officer|president|human|person|someone)\b/u',
            $text
        );
    }

    private function wantsAssistantBack(string $text): bool
    {
        return $this->mentions($text, [
            'talk to the assistant', 'talk to assistant', 'talk to the ai', 'talk to ai',
            'speak to the assistant', 'speak to assistant', 'speak to the ai', 'speak to ai',
            'back to assistant', 'back to the assistant', 'back to ai', 'back to the ai',
            'return to assistant', 'return to the assistant', 'return to ai',
            'switch to assistant', 'switch to the assistant', 'switch to ai',
            'ask the assistant', 'ask the ai', 'use the assistant', 'use the ai',
            'end chat with organization', 'end chat with org', 'stop talking to organization',
            'stop talking to org', 'leave organization chat', 'leave org chat',
            'i want the assistant', 'i want the ai', 'bring back the assistant',
            'bring back ai', 'assistant please',
        ]) || (bool) preg_match(
            '/\b(back|return|switch|talk|speak|chat)\b.{0,20}\b(assistant|ai|bot|chatbot)\b/u',
            $text
        );
    }

    private function asksAboutSystem(string $text): bool
    {
        return $this->mentions($text, [
            'volunteerhub', 'volunteer hub', 'this system', 'this portal', 'this website',
            'this app', 'how does this work', 'what can i do', 'what is this',
            'what is volunteerhub', 'about volunteerhub', 'about this portal',
            'dashboard', 'dark mode', 'theme', 'help', 'navigation', 'pages',
            'faq', 'common question', 'how to use', 'getting started', 'features',
        ]);
    }

    private function asksCurrent(string $text): bool
    {
        return $this->asksNext($text)
            || $this->asksUpcoming($text)
            || $this->mentions($text, ['current', 'active', 'open', 'pending', 'assigned to me', 'my task', 'my duty', 'right now', 'today', 'now']);
    }

    private function mentions(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function assignments(User $volunteer, $eventIds): Collection
    {
        return Assignment::where('user_id', $volunteer->id)
            ->when($eventIds !== null, function ($query) use ($eventIds) {
                $query->whereIn('event_id', $eventIds->isEmpty() ? [-1] : $eventIds);
            })
            ->with(['task', 'event'])
            ->latest()
            ->get();
    }

    private function isGreeting(string $text): bool
    {
        return in_array($text, ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'thanks', 'thank you'], true);
    }
}
