@php
    $type = $noticeType ?? 'general';
    $event = $events->first();
    $assignment = $assignments->first();
    $task = $assignment?->task;
    $assignmentEvent = $assignment?->event ?? $task?->event;
    $portal = $portalUrl ?: route('email.continue', ['section' => 'notifications']);
    $loginUrl = route('email.continue', ['section' => 'notifications']);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $noticeTitle }}</title>
</head>
<body style="margin: 0; padding: 0; background: #eef3f8; color: #0f172a; font-family: Arial, Helvetica, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto; padding: 32px 16px;">
        <div style="text-align: center; margin-bottom: 16px;">
            <p style="margin: 0; font-size: 13px; font-weight: bold; letter-spacing: .12em; text-transform: uppercase; color: #0097d6;">VolunteerHub</p>
            <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">JCI Surigao · Volunteer portal</p>
        </div>

        <div style="background: #ffffff; border: 1px solid #dbe4ee; border-radius: 14px; overflow: hidden;">
            <div style="background: #0097d6; color: #ffffff; padding: 24px 28px;">
                <p style="margin: 0 0 8px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; opacity: .85;">{{ $organization->name }}</p>
                <h1 style="margin: 0; font-size: 22px; line-height: 1.35; font-weight: bold;">{{ $noticeTitle }}</h1>
            </div>

            <div style="padding: 28px;">
                <p style="margin: 0 0 18px; font-size: 15px; line-height: 1.6; color: #334155;">
                    Hello {{ $volunteer->name }},
                </p>

                @if($type === 'event' && $event)
                    <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.65; color: #334155;">
                        {{ $organization->name }} posted a new event. Review the details below, then sign in to VolunteerHub to follow it and pick up related tasks when they are posted.
                    </p>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin: 0 0 24px;">
                        <p style="margin: 0 0 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; color: #0097d6;">Event details</p>
                        <p style="margin: 0 0 12px; font-size: 18px; font-weight: bold; color: #0f172a; line-height: 1.35;">{{ $event->title }}</p>
                        @if($event->description)
                            <p style="margin: 0 0 14px; font-size: 14px; line-height: 1.6; color: #475569;">{{ \Illuminate\Support\Str::limit(strip_tags($event->description), 220) }}</p>
                        @endif
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size: 13px; color: #334155; line-height: 1.55;">
                            <tr>
                                <td style="padding: 0 0 8px; width: 88px; vertical-align: top; color: #64748b;">Location</td>
                                <td style="padding: 0 0 8px; vertical-align: top; font-weight: bold;">{{ $event->location ?: 'To be announced' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 0 0 8px; vertical-align: top; color: #64748b;">Starts</td>
                                <td style="padding: 0 0 8px; vertical-align: top; font-weight: bold;">{{ $event->start_time ? $event->start_time->format('D, M j, Y · g:i A') : 'To be announced' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 0 0 8px; vertical-align: top; color: #64748b;">Ends</td>
                                <td style="padding: 0 0 8px; vertical-align: top; font-weight: bold;">{{ $event->end_time ? $event->end_time->format('D, M j, Y · g:i A') : 'To be announced' }}</td>
                            </tr>
                            @if($event->capacity)
                                <tr>
                                    <td style="padding: 0; vertical-align: top; color: #64748b;">Capacity</td>
                                    <td style="padding: 0; vertical-align: top; font-weight: bold;">{{ $event->capacity }} volunteers</td>
                                </tr>
                            @endif
                        </table>
                    </div>

                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 8px;">
                        <tr>
                            <td>
                                <a href="{{ $portal }}"
                                   style="display: inline-block; background: #0097d6; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: bold; padding: 13px 24px; border-radius: 8px;">
                                    Sign in to VolunteerHub
                                </a>
                            </td>
                        </tr>
                    </table>
                    <p style="margin: 12px 0 0; font-size: 13px; line-height: 1.5; color: #64748b;">
                        Opens your Events board after you sign in.
                    </p>

                @elseif($type === 'task' && ($task || $assignment))
                    <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.65; color: #334155;">
                        {{ $noticeMessage }}
                    </p>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin: 0 0 24px;">
                        <p style="margin: 0 0 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; color: #0097d6;">Assignment details</p>
                        <p style="margin: 0 0 12px; font-size: 18px; font-weight: bold; color: #0f172a; line-height: 1.35;">{{ $task->title ?? 'Assigned task' }}</p>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size: 13px; color: #334155; line-height: 1.55;">
                            <tr>
                                <td style="padding: 0 0 8px; width: 88px; vertical-align: top; color: #64748b;">Event</td>
                                <td style="padding: 0 0 8px; vertical-align: top; font-weight: bold;">{{ $assignmentEvent->title ?? 'Event' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 0 0 8px; vertical-align: top; color: #64748b;">Location</td>
                                <td style="padding: 0 0 8px; vertical-align: top; font-weight: bold;">{{ $assignmentEvent->location ?: 'Not set' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 0 0 8px; vertical-align: top; color: #64748b;">Schedule</td>
                                <td style="padding: 0 0 8px; vertical-align: top; font-weight: bold;">
                                    @if($assignmentEvent?->start_time)
                                        {{ $assignmentEvent->start_time->format('D, M j, Y · g:i A') }}
                                        @if($assignmentEvent->end_time)
                                            – {{ $assignmentEvent->end_time->format('g:i A') }}
                                        @endif
                                    @else
                                        Not set
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0; vertical-align: top; color: #64748b;">Deadline</td>
                                <td style="padding: 0; vertical-align: top; font-weight: bold;">
                                    {{ $task?->due_date ? $task->due_date->format('D, M j, Y · g:i A') : 'Not set' }}
                                </td>
                            </tr>
                        </table>
                    </div>

                    @if($acceptUrl || $declineUrl)
                        <p style="margin: 0 0 14px; font-size: 14px; font-weight: bold; color: #0f172a;">Your response</p>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 14px;">
                            <tr>
                                @if($acceptUrl)
                                    <td style="padding-right: 10px;">
                                        <a href="{{ $acceptUrl }}"
                                           style="display: inline-block; background: #059669; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: bold; padding: 13px 22px; border-radius: 8px;">
                                            Accept task
                                        </a>
                                    </td>
                                @endif
                                @if($declineUrl)
                                    <td>
                                        <a href="{{ $declineUrl }}"
                                           style="display: inline-block; background: #ffffff; color: #be123c; text-decoration: none; font-size: 14px; font-weight: bold; padding: 12px 22px; border-radius: 8px; border: 1px solid #fb7185;">
                                            Decline
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        </table>
                        <p style="margin: 0 0 18px; font-size: 12px; line-height: 1.55; color: #64748b;">
                            Accept and Decline links are personal to your account and expire in 7 days.
                        </p>
                    @endif

                    <p style="margin: 0; font-size: 13px; line-height: 1.55; color: #475569;">
                        Prefer to review first?
                        <a href="{{ $portal }}" style="color: #0097d6; font-weight: bold; text-decoration: none;">Sign in to VolunteerHub</a>
                        and open your Tasks board.
                    </p>

                @else
                    <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.65; color: #334155; white-space: pre-line;">{{ $noticeMessage }}</p>

                    @if($events->isNotEmpty())
                        <p style="margin: 0 0 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; color: #0097d6;">Events</p>
                        @foreach($events as $listedEvent)
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin: 0 0 8px;">
                                <p style="margin: 0 0 4px; font-size: 14px; font-weight: bold;">{{ $listedEvent->title }}</p>
                                <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                                    {{ $listedEvent->location }}
                                    @if($listedEvent->start_time)
                                        · {{ $listedEvent->start_time->format('M d, Y g:i A') }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    @endif

                    @if($assignments->isNotEmpty())
                        <p style="margin: 18px 0 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; color: #0097d6;">Your tasks</p>
                        @foreach($assignments as $listedAssignment)
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin: 0 0 8px;">
                                <p style="margin: 0 0 4px; font-size: 14px; font-weight: bold;">{{ $listedAssignment->task->title ?? 'Assigned task' }}</p>
                                <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                                    {{ $listedAssignment->event->title ?? 'Event' }}
                                    @if($listedAssignment->task?->due_date)
                                        · Due {{ $listedAssignment->task->due_date->format('M d, Y g:i A') }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    @endif

                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 22px 0 0;">
                        <tr>
                            <td>
                                <a href="{{ $portal }}"
                                   style="display: inline-block; background: #0097d6; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: bold; padding: 13px 24px; border-radius: 8px;">
                                    Sign in to VolunteerHub
                                </a>
                            </td>
                        </tr>
                    </table>
                @endif
            </div>
        </div>

        <div style="padding: 20px 8px 0; text-align: center;">
            <p style="margin: 0 0 6px; font-size: 12px; line-height: 1.55; color: #64748b;">
                @if($type === 'event')
                    You received this because you are linked to {{ $organization->name }} on VolunteerHub.
                @elseif($type === 'task')
                    You received this because {{ $organization->name }} assigned this task to your VolunteerHub account.
                @else
                    You received this because you are linked to {{ $organization->name }} on VolunteerHub.
                @endif
            </p>
            <p style="margin: 0 0 6px; font-size: 12px; line-height: 1.55; color: #94a3b8;">
                This notice also appears in your VolunteerHub inbox.
                <a href="{{ $loginUrl }}" style="color: #0097d6; text-decoration: none;">Sign in</a>
                to manage notification preferences under Settings.
            </p>
            <p style="margin: 0; font-size: 11px; line-height: 1.5; color: #94a3b8;">
                Please do not reply to this email. Use VolunteerHub messages to contact your organization.
            </p>
        </div>
    </div>
</body>
</html>
