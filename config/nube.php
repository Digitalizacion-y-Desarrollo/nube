<?php

return [
    'trash_retention_days' => (int) env('NUBE_TRASH_RETENTION_DAYS', 30),

    'files' => [
        'max_size_kb' => (int) env('NUBE_MAX_FILE_SIZE_KB', 204800),
        'extensions' => [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt',
            'csv',
            'jpg',
            'jpeg',
            'png',
            'zip',
        ],
        'mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/csv',
            'image/jpeg',
            'image/png',
            'application/zip',
            'application/x-zip-compressed',
        ],
    ],
];
