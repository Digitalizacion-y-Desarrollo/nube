<?php

namespace App\Notifications;

class FileSharedNotification extends FileEventNotification
{
    protected function message(): string
    {
        return "{$this->actorName()} compartió «{$this->file->display_name}» contigo.";
    }
}
