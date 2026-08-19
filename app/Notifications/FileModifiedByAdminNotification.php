<?php

namespace App\Notifications;

use App\Models\File;
use App\Models\User;

class FileModifiedByAdminNotification extends FileEventNotification
{
    public function __construct(
        File $file,
        User $actor,
        private readonly string $action,
    ) {
        parent::__construct($file, $actor);
    }

    protected function message(): string
    {
        return "El superadministrador {$this->actorName()} {$this->action} tu archivo «{$this->file->display_name}».";
    }
}
