<?php

namespace App\Notifications;

class DepartmentFileUploadedNotification extends FileEventNotification
{
    protected function message(): string
    {
        return "{$this->actorName()} subió «{$this->file->display_name}» al contenido colaborativo de tu departamento.";
    }
}
