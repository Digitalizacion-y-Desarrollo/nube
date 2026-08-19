<?php

namespace App\Notifications;

class PublicFileUploadedNotification extends FileEventNotification
{
    protected function message(): string
    {
        return "{$this->actorName()} publicó «{$this->file->display_name}» en los archivos públicos.";
    }
}
