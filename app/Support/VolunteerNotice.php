<?php

namespace App\Support;

use App\Mail\VolunteerNoticeMail;
use App\Models\Assignment;
use App\Models\Event;
use App\Models\MembershipSeminar;
use App\Models\OrganizationNotice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VolunteerNotice
{
    /**
     * Send one notice to the volunteers it applies to, on the portal and by email.
     *
     * @return array{recipients: int, emails: int}
     */
    public static function broadcast(User $organization, string $type, string $title, string $message): array
    {
        $recipients = 0;
        $emails = 0;
        $addresses = [];
        $failures = [];

        foreach (self::recipients($organization, $type) as $volunteer) {
            $events = self::upcomingEvents($organization);
            $assignments = self::openAssignments($organization, $volunteer);
            if ($type === 'task' && $assignments->isEmpty()) {
                continue;
            }

            $recipients++;
            $address = trim((string) $volunteer->email);
            $error = null;
            $delivered = self::deliver($volunteer, $organization, $type, $title, $message, $events, $assignments, null, null, $error);
            if ($delivered) {
                $emails++;
                $addresses[] = $address;
            } elseif ($address !== '') {
                $failures[$address] = $error;
            }
        }

        if ($recipients > 0) {
            self::record($organization, $type, $title, $message, $recipients, $emails);
        }

        return ['recipients' => $recipients, 'emails' => $emails, 'addresses' => $addresses, 'failures' => $failures];
    }

    /**
     * Tell the newly assigned volunteer about this task (with accept/decline).
     * When reassigned, the previous volunteer only gets a "no longer assigned" notice.
     */
    public static function notifyAssignment(
        User $organization,
        User $volunteer,
        Task $task,
        ?User $previous = null,
        ?Assignment $assignment = null
    ): void {
        $task->loadMissing('event');
        $reassigned = $previous && (int) $previous->id !== (int) $volunteer->id;

        $assignment = $assignment
            ?? Assignment::where('task_id', $task->id)
                ->where('user_id', $volunteer->id)
                ->with(['task', 'event'])
                ->first();

        if ($assignment) {
            $assignment->loadMissing(['task', 'event']);
            if ((int) $assignment->user_id !== (int) $volunteer->id) {
                $assignment = Assignment::where('task_id', $task->id)
                    ->where('user_id', $volunteer->id)
                    ->with(['task', 'event'])
                    ->first();
            }
        }

        $title = $reassigned ? 'Task reassigned to you' : 'You have been assigned a task';
        $message = $reassigned
            ? $organization->name.' reassigned a volunteer role to you. Please confirm whether you can take it.'
            : $organization->name.' assigned you a volunteer role. Please confirm whether you can take it.';
        $focus = $assignment ? collect([$assignment]) : collect();
        [$acceptUrl, $declineUrl] = self::assignmentActionUrls($assignment, $volunteer);

        // New assignee always gets portal + email (with accept/decline).
        $emailError = null;
        $emailed = self::deliver(
            $volunteer,
            $organization,
            'task',
            $title,
            $message,
            collect(),
            $focus,
            $acceptUrl,
            $declineUrl,
            $emailError,
            true,
            true,
        ) ? 1 : 0;
        $recipients = 1;

        // Previous volunteer: portal only (no email) so reassignment mail goes only to the new assignee.
        if ($reassigned && $previous && (int) $previous->id !== (int) $volunteer->id) {
            $previousTitle = 'You were removed from a task';
            $previousMessage = $organization->name.' reassigned "'.$task->title.'" to '.$volunteer->name
                .'. You are no longer responsible for this task on "'.($task->event->title ?? 'the event').'".';
            $recipients++;
            self::deliver(
                $previous,
                $organization,
                'task',
                $previousTitle,
                $previousMessage,
                collect(),
                collect(),
                null,
                null,
                $emailError,
                true,
                false,
            );
        }

        self::record(
            $organization,
            'task',
            $title,
            $message."\n\n".self::assignmentFacts($task, $reassigned ? $previous : null, $volunteer),
            $recipients,
            $emailed
        );
    }

    /**
     * Tell someone who just registered what the membership seminar requires.
     */
    public static function notifyApplicant(User $organization, User $volunteer): void
    {
        $seminar = self::nextSeminar($organization);
        $title = 'Membership seminar';
        $message = $volunteer->name.' registered with '.$organization->name.'. '
            .'A person becomes a member only after the membership fee is paid and they attend a membership seminar.';
        if ($seminar) {
            $message .= "\n\n".self::seminarFacts($seminar);
        } else {
            $message .= "\n\nThe organization has not scheduled the next membership seminar yet.";
        }

        $emailed = self::deliver($volunteer, $organization, 'seminar', $title, $message, collect(), collect());
        self::record($organization, 'seminar', $title, $message, 1, $emailed ? 1 : 0);
    }

    /**
     * Tell applicants the date, place, and fee of a newly scheduled seminar.
     */
    public static function notifyScheduledSeminar(User $organization, MembershipSeminar $seminar): void
    {
        $title = 'Membership seminar scheduled';
        $message = $organization->name.' scheduled a membership seminar. Attend this seminar and pay the membership fee to become a member.'
            ."\n\n".self::seminarFacts($seminar);
        $recipients = 0;
        $emails = 0;

        foreach (self::applicants($organization) as $volunteer) {
            $recipients++;
            if (self::deliver($volunteer, $organization, 'seminar', $title, $message, collect(), collect())) {
                $emails++;
            }
        }

        if ($recipients > 0) {
            self::record($organization, 'seminar', $title, $message, $recipients, $emails);
        }
    }

    /**
     * Tell current members that someone finished the fee and the seminar.
     */
    public static function notifyNewMember(User $organization, User $member): void
    {
        $member->loadMissing('membershipSeminar');
        $seminar = $member->membershipSeminar;
        $title = 'New member';
        $message = $member->name.' is now a member of '.$organization->name.'. '
            .'The membership fee was recorded'
            .($member->membership_fee_paid_at ? ' on '.$member->membership_fee_paid_at->format('M d, Y') : '')
            .' and seminar attendance was recorded'
            .($member->membership_attended_at ? ' on '.$member->membership_attended_at->format('M d, Y') : '')
            .'.';
        if ($seminar) {
            $message .= "\n\n".self::seminarFacts($seminar);
        }

        $recipients = 0;
        $emails = 0;
        foreach (self::members($organization) as $volunteer) {
            if ((int) $volunteer->id === (int) $member->id) {
                continue;
            }
            $recipients++;
            if (self::deliver($volunteer, $organization, 'member', $title, $message, collect(), collect())) {
                $emails++;
            }
        }

        $ownMessage = 'You are now a member of '.$organization->name.' because the membership fee and seminar attendance are both recorded.';
        if ($seminar) {
            $ownMessage .= "\n\n".self::seminarFacts($seminar);
        }
        $recipients++;
        if (self::deliver($member, $organization, 'member', 'You are now a member', $ownMessage, collect(), collect())) {
            $emails++;
        }

        self::record($organization, 'member', $title, $message, $recipients, $emails);
    }

    /**
     * Tell the organization's volunteers that an event is now visible.
     */
    public static function notifyPublishedEvent(Event $event): void
    {
        $event->loadMissing('organization');
        $organization = $event->organization;
        if (!$organization) {
            return;
        }

        $when = $event->start_time ? $event->start_time->format('M d, Y g:i A') : 'a scheduled date';
        $title = 'New event from '.$organization->name;
        $message = '"'.$event->title.'" is now open'
            .($event->location ? ' at '.$event->location : '')
            .' on '.$when.'.';
        $focus = collect([$event]);
        $recipients = 0;
        $emails = 0;

        foreach (self::recipients($organization, 'event') as $volunteer) {
            $recipients++;
            if (self::deliver($volunteer, $organization, 'event', $title, $message, $focus, collect())) {
                $emails++;
            }
        }

        if ($recipients > 0) {
            self::record($organization, 'event', $title, $message, $recipients, $emails);
        }
    }

    public static function linkedVolunteers(User $organization): Collection
    {
        return self::recipients($organization, 'reminder');
    }

    public static function mailIsReady(): bool
    {
        return config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && config('mail.mailers.smtp.host') !== '127.0.0.1';
    }

    /**
     * Save a Gmail mailbox and use it for the next send in this request.
     */
    public static function connectGmail(string $address, string $password): void
    {
        $address = trim($address);
        $password = preg_replace('/\s+/', '', trim($password)) ?? '';

        self::writeEnv([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.gmail.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => $address,
            'MAIL_PASSWORD' => $password,
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => $address,
            'MAIL_FROM_NAME' => 'VolunteerHub',
        ]);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.encryption' => 'tls',
            'mail.mailers.smtp.username' => $address,
            'mail.mailers.smtp.password' => $password,
            'mail.from.address' => $address,
            'mail.from.name' => 'VolunteerHub',
        ]);

        Mail::purge('smtp');
    }

    private static function deliver(
        User $volunteer,
        User $organization,
        string $type,
        string $title,
        string $message,
        Collection $events,
        Collection $assignments,
        ?string $acceptUrl = null,
        ?string $declineUrl = null,
        ?string &$error = null,
        bool $forcePortal = false,
        bool $forceEmail = false,
    ): bool {
        $error = null;
        $wantsNotice = $forceEmail || $volunteer->wantsVolunteerNotice($type);

        $section = match ($type) {
            'task' => 'tasks',
            'event' => 'events',
            'certificate' => 'certificates',
            default => 'notifications',
        };
        $dashboardUrl = url('/volunteer/dashboard?section='.$section);
        // Email buttons go through /email/continue so guests hit login instead of a 403.
        $emailPortalUrl = route('email.continue', ['section' => $section]);

        // Assignment changes should always appear in the portal inbox even if email is opted out.
        if ($forcePortal || $wantsNotice) {
            PortalNotifier::send(
                (int) $volunteer->id,
                $title,
                Str::limit(preg_replace('/\s+/', ' ', $message) ?? $message, 160),
                $message,
                match ($type) {
                    'event' => 'fa-calendar',
                    'task' => 'fa-list-check',
                    default => 'fa-bell',
                },
                $dashboardUrl
            );
        }

        if (!$wantsNotice && !$forceEmail) {
            return false;
        }

        $address = trim((string) $volunteer->email);
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $error = 'No email address is saved on this account.';

            return false;
        }

        $mail = new VolunteerNoticeMail(
            $volunteer,
            $organization,
            $title,
            $message,
            $events,
            $assignments,
            $type,
            $acceptUrl,
            $declineUrl,
            $emailPortalUrl,
        );

        $from = filter_var($organization->email, FILTER_VALIDATE_EMAIL)
            ? $organization->email
            : 'org@volunteerhub.ph';

        $smtpError = null;
        if (self::mailIsReady()) {
            try {
                Mail::to($address)->send($mail);

                return true;
            } catch (\Throwable $exception) {
                report($exception);
                $smtpError = $exception->getMessage();
            }
        }

        try {
            RegisteredMailbox::send($address, $title, $mail->render(), $from, $organization->name);

            return true;
        } catch (\Throwable $exception) {
            report($exception);
            $error = self::deliveryError($smtpError, $exception->getMessage());

            return false;
        }
    }

    /**
     * Build accept/decline links with an HMAC token (more reliable than host-bound signed URLs).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function assignmentActionUrls(?Assignment $assignment, User $volunteer): array
    {
        if (!$assignment || (int) $assignment->user_id !== (int) $volunteer->id) {
            return [null, null];
        }

        return [
            self::assignmentActionUrl($assignment, $volunteer, 'accept'),
            self::assignmentActionUrl($assignment, $volunteer, 'decline'),
        ];
    }

    public static function assignmentActionUrl(Assignment $assignment, User $volunteer, string $decision): string
    {
        $expires = now()->addDays(7)->timestamp;
        $token = self::assignmentActionToken((int) $assignment->id, (int) $volunteer->id, $decision, $expires);

        return url('/volunteer/assignments/'.$assignment->id.'/email-respond/'.$decision)
            .'?user='.$volunteer->id
            .'&expires='.$expires
            .'&token='.urlencode($token);
    }

    public static function assignmentActionToken(int $assignmentId, int $userId, string $decision, int $expires): string
    {
        return hash_hmac(
            'sha256',
            $assignmentId.'|'.$userId.'|'.$decision.'|'.$expires,
            (string) config('app.key')
        );
    }

    public static function assignmentActionTokenIsValid(
        int $assignmentId,
        int $userId,
        string $decision,
        int $expires,
        string $token
    ): bool {
        if ($expires < now()->timestamp) {
            return false;
        }

        $expected = self::assignmentActionToken($assignmentId, $userId, $decision, $expires);

        return hash_equals($expected, $token);
    }

    private static function recipients(User $organization, string $type): Collection
    {
        $eventIds = Event::where('organization_id', $organization->id)->pluck('id');
        $assignedIds = $eventIds->isEmpty()
            ? collect()
            : Assignment::whereIn('event_id', $eventIds)->pluck('user_id')->filter()->unique()->values();

        if ($assignedIds->isNotEmpty()) {
            User::where('role', 'volunteer')
                ->where('status', 'approved')
                ->whereNull('organization_id')
                ->whereIn('id', $assignedIds)
                ->update(['organization_id' => $organization->id]);
        }

        $volunteers = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get();

        if ($type !== 'task') {
            return $volunteers;
        }

        return $volunteers->filter(function (User $volunteer) use ($organization) {
            return self::openAssignments($organization, $volunteer)->isNotEmpty();
        })->values();
    }

    private static function members(User $organization): Collection
    {
        return User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('membership_fee_paid_at')
            ->whereNotNull('membership_attended_at')
            ->orderBy('name')
            ->get();
    }

    private static function applicants(User $organization): Collection
    {
        return User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($query) {
                $query->whereNull('membership_fee_paid_at')
                    ->orWhereNull('membership_attended_at');
            })
            ->orderBy('name')
            ->get();
    }

    private static function nextSeminar(User $organization): ?MembershipSeminar
    {
        return MembershipSeminar::where('organization_id', $organization->id)
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->first();
    }

    private static function seminarFacts(MembershipSeminar $seminar): string
    {
        return implode("\n", array_filter([
            'Seminar: '.$seminar->title,
            'Schedule: '.$seminar->starts_at->format('M d, Y g:i A').' to '.$seminar->ends_at->format('g:i A'),
            'Location: '.$seminar->location,
            'Membership fee: PHP '.number_format((float) $seminar->fee_amount, 2),
            $seminar->notes ? 'Note: '.$seminar->notes : null,
        ]));
    }

    private static function assignmentFacts(Task $task, ?User $previous, User $current): string
    {
        $event = $task->event;
        $lines = [
            'Task: '.$task->title,
            'Event: '.($event->title ?? 'Event'),
            'Location: '.($event->location ?: 'Not set'),
            'Schedule: '.($event && $event->start_time ? $event->start_time->format('M d, Y g:i A') : 'Not set'),
            'Deadline: '.($task->due_date ? $task->due_date->format('M d, Y g:i A') : 'Not set'),
        ];
        if ($previous) {
            $lines[] = 'Previous volunteer: '.$previous->name;
        }
        $lines[] = 'Assigned volunteer: '.$current->name;

        return implode("\n", array_filter($lines, fn ($line) => $line !== null));
    }

    private static function upcomingEvents(User $organization): Collection
    {
        return Event::where('organization_id', $organization->id)
            ->where('status', 'published')
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();
    }

    private static function openAssignments(User $organization, User $volunteer): Collection
    {
        $eventIds = Event::where('organization_id', $organization->id)->pluck('id');
        if ($eventIds->isEmpty()) {
            return collect();
        }

        return Assignment::where('user_id', $volunteer->id)
            ->whereIn('event_id', $eventIds)
            ->whereIn('status', ['approved', 'assigned', 'submitted', 'in_progress'])
            ->with(['task', 'event'])
            ->get();
    }

    private static function digest(string $message, Collection $events, Collection $assignments): string
    {
        $parts = [trim($message)];
        if ($events->isNotEmpty()) {
            $parts[] = 'Events: '.$events->pluck('title')->join(', ').'.';
        }
        if ($assignments->isNotEmpty()) {
            $parts[] = 'Tasks: '.$assignments->map(fn (Assignment $assignment) => $assignment->task->title ?? 'Assigned task')->join(', ').'.';
        }

        return implode(' ', array_filter($parts));
    }

    private static function deliveryError(?string $smtpError, string $directError): string
    {
        $smtp = $smtpError ?? '';
        if (str_contains($smtp, '535') || str_contains($smtp, 'BadCredentials')) {
            return 'Google rejected the sign-in for the sending Gmail account. The notice is addressed to the volunteer\'s registered email, and it will be delivered after that app password is accepted.';
        }

        if (str_contains($directError, 'NotAuthorized') || str_contains($directError, 'IPv6Auth')) {
            return 'The volunteer address is correct, but Gmail will not accept mail from this computer until a sending account signs in with an app password.';
        }

        return trim($directError);
    }

    private static function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $contents = file_get_contents($path);
        if ($contents === false) {
            return;
        }

        foreach ($values as $key => $value) {
            $line = $key.'='.self::quoteEnv((string) $value);
            if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $contents)) {
                $contents = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $contents, 1);
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($path, $contents);
    }

    private static function quoteEnv(string $value): string
    {
        if ($value === '' || preg_match('/[\s#="]/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    private static function record(User $organization, string $type, string $title, string $message, int $recipients, int $emails): void
    {
        OrganizationNotice::create([
            'organization_id' => $organization->id,
            'notice_type' => $type,
            'title' => $title,
            'message' => $message,
            'recipients' => $recipients,
            'emails_sent' => $emails,
        ]);
    }
}
