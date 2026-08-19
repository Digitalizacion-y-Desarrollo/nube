<?php

namespace App\Notifications;

use App\Enums\FileVisibility;
use App\Models\File;
use App\Models\User;
use Illuminate\Notifications\Notification;

abstract class FileEventNotification extends Notification
{
    public function __construct(
        protected readonly File $file,
        protected readonly User $actor,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message(),
            'url' => $this->fileUrl(),
            'actor_name' => $this->actorName(),
            'actor_avatar' => $this->actor->avatarUrl(),
        ];
    }

    abstract protected function message(): string;

    protected function actorName(): string
    {
        return trim("{$this->actor->name} {$this->actor->last_name}") ?: 'Alguien';
    }

    protected function fileUrl(): string
    {
        $section = match ($this->file->visibility) {
            FileVisibility::Private => 'mine',
            FileVisibility::Collaborative => 'department',
            FileVisibility::Public => 'public',
        };

        return $this->file->folder_id === null
            ? route("folders.{$section}")
            : route("folders.{$section}.show", $this->file->folder_id);
    }
}
