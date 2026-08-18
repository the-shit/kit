<?php

return [
    'provider' => env('AI_DEFAULT_PROVIDER', 'xai'),
    'model' => env('KIT_MODEL', 'grok-4-fast'),

    'bikes_v2' => env('KIT_BIKES_V2', '/home/jordan/projects/bikes-v2'),
    'catalog_path' => env('KIT_CATALOG_PATH', '/home/jordan/projects/bikes-v2/public/models/catalog.json'),
    'look_report' => env('KIT_LOOK_REPORT', '/home/jordan/projects/bikes-v2/tmp/rider-look/report.json'),
    'status_path' => env('KIT_STATUS_PATH', '/home/jordan/.cache/kit/status.json'),
    'memory_path' => env('KIT_MEMORY_PATH', storage_path('app/kit/memory.json')),
    'conversations_path' => env('KIT_CONVERSATIONS_PATH', storage_path('app/kit/conversations')),
    'kitd_health' => env('KIT_KITD_HEALTH', 'http://127.0.0.1:8787/health'),
    'ytdlp' => env('KIT_YTDLP', '/home/jordan/.local/bin/yt-dlp'),
    'ask_lexi' => env('KIT_ASK_LEXI', '/home/jordan/.grok/kit/ask-lexi'),

    'peer_token' => env('KIT_PEER_TOKEN', ''),
    'webhook_token' => env('KIT_WEBHOOK_TOKEN', ''),

    'mattermost' => [
        'url' => env('KIT_MATTERMOST_URL', 'http://100.68.122.24:8065'),
        'token' => env('KIT_MATTERMOST_TOKEN', ''),
        'user_id' => env('KIT_MATTERMOST_USER_ID', 'zdunkip7xjy1ukn9xd8wt5kqrc'),
        'jordan_user_id' => env('JORDAN_MATTERMOST_USER_ID', 'zh5bhphuqfdtffks1nys4e76ie'),
        'channel_id' => env('KIT_MATTERMOST_CHANNEL', '3r1tnxhe5fgcmjxgrspm7316oc'),
    ],
];
