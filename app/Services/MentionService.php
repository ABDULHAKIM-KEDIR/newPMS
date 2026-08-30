<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;

/**
 * Parses @mention handles out of task comment text and turns them into
 * Notification records. Users have no dedicated username column, so a
 * handle matches either the slugified full name ("@john-doe") or the
 * email local-part ("@jdoe").
 */
class MentionService
{
    /**
     * Extract mentioned users from a comment body.
     *
     * @param  string  $body  Raw comment text
     * @param  int  $authorId  The comment author (never notified)
     * @return array<int, User> Distinct active users keyed by user_id
     */
    public function extractMentionedUsers(string $body, int $authorId): array
    {
        preg_match_all('/@([\w\.\-]+)/', $body, $matches);

        $handles = collect($matches[1] ?? [])
            ->map(fn ($h) => strtolower(trim($h)))
            ->filter(fn ($h) => mb_strlen($h) >= 2)
            ->unique()
            ->values();

        if ($handles->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->where('status', 'Active')
            ->where('user_id', '!=', $authorId)
            ->get()
            ->filter(function (User $user) use ($handles) {
                return $handles->contains($this->slug($user->full_name))
                    || $handles->contains(strtolower((string) strstr((string) $user->email, '@', true)));
            })
            ->unique('user_id');

        return $users->all();
    }

    /**
     * Create unread mention notifications pointing at the task.
     *
     * @param  array<int, User>  $users
     */
    public function notifyMentionedUsers(Task $task, array $users, string $commentBody): int
    {
        $snippet = mb_substr(trim($commentBody), 0, 120);
        if (mb_strlen(trim($commentBody)) > 120) {
            $snippet .= '…';
        }

        $link = route('tasks.show', ['task' => $task->task_id]);
        $created = 0;

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->user_id,
                'message' => "You were mentioned in a comment on \"{$task->task_name}\": {$snippet}",
                'is_read' => false,
                'type' => 'mention',
                'link' => $link,
            ]);
            $created++;
        }

        return $created;
    }

    /** Lowercase kebab-case slug of a full name, e.g. "John Doe" → "john-doe". */
    private function slug(string $name): string
    {
        return strtolower(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-'));
    }
}
