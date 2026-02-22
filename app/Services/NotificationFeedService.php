<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationFeedService
{
    public function notifyNewMember(User $member): void
    {
        $displayName = trim((string) $member->name);
        $email = trim((string) $member->email);

        $this->createForUsers(
            collect([$member->id]),
            '[Welcome] Your account is ready. You will now receive updates about new members and new books.'
        );

        $adminIds = User::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', 'admin'))
            ->pluck('id');

        $this->createForUsers(
            $adminIds,
            "[Community] New member joined: {$displayName} ({$email})."
        );
    }

    public function notifyNewBook(Book $book): void
    {
        $title = trim((string) $book->title);
        $author = trim((string) $book->author);

        $recipientIds = User::query()
            ->where('status', 'active')
            ->pluck('id');

        $this->createForUsers(
            $recipientIds,
            "[Book] New book added: {$title} by {$author}."
        );
    }

    private function createForUsers(Collection $userIds, string $message, string $type = 'general'): void
    {
        $ids = $userIds
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $ids->map(fn ($userId) => [
            'user_id' => (int) $userId,
            'type' => $type,
            'message' => $message,
            'sent_at' => $now,
            'status' => 'sent',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Notification::insert($rows);
    }
}
