<?php

namespace App\Support;

class StatusBadge
{
    public static function classes(?string $label): string
    {
        $key = strtolower(trim((string) $label));

        foreach (self::groups() as $tone => $labels) {
            if (in_array($key, $labels, true)) {
                return self::tones()[$tone];
            }
        }

        return self::tones()['slate'];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return [
            'sky' => ['upcoming', 'published', 'you are replying', 'admin', 'created', 'updated'],
            'teal' => ['ongoing', 'happening now', 'in progress', 'organization'],
            'indigo' => ['assigned', 'accepted', 'volunteer'],
            'emerald' => ['completed', 'approved', 'available', 'verified', 'active', 'success'],
            'violet' => ['archived', 'other', 'deleted'],
            'amber' => ['pending', 'pending review', 'pending response', 'submitted', 'submitted for review', 'revision requested', 'needs review', 'awaiting verification', 'awaiting approval', 'needs your reply', 'additional information required'],
            'rose' => ['cancelled', 'canceled', 'declined', 'rejected', 'uncompleted', 'overdue', 'unavailable', 'inactive', 'unread', 'failed'],
            'slate' => ['ended', 'past', 'read', 'assistant answers'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tones(): array
    {
        return [
            'sky' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
            'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-200',
            'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
            'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200',
            'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200',
            'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        ];
    }
}
