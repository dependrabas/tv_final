<?php
declare(strict_types=1);

function entity_definitions(): array
{
    $publication = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];
    $priorities = ['normal' => 'Normal', 'important' => 'Important', 'urgent' => 'Urgent'];
    return [
        'announcements' => [
            'label' => 'Announcements', 'singular' => 'Announcement', 'active' => 'announcements', 'icon' => 'bi-megaphone',
            'date_window' => ['start' => 'publish_date', 'end' => 'expiry_date'],
            'columns' => ['title' => 'Title', 'priority' => 'Priority', 'publish_date' => 'Published', 'expiry_date' => 'Expires', 'status' => 'Status'],
            'fields' => [
                'title' => ['label' => 'Announcement title', 'type' => 'text', 'required' => true, 'full' => true],
                'description' => ['label' => 'Short description', 'type' => 'textarea', 'required' => true, 'full' => true],
                'publish_date' => ['label' => 'Publication date', 'type' => 'date', 'required' => true],
                'expiry_date' => ['label' => 'Expiry date', 'type' => 'date'],
                'priority' => ['label' => 'Priority', 'type' => 'select', 'options' => $priorities, 'required' => true],
                'status' => ['label' => 'Publication status', 'type' => 'select', 'options' => $publication, 'required' => true],
                'display_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'news' => [
            'label' => 'Latest News', 'singular' => 'News story', 'active' => 'news', 'icon' => 'bi-newspaper', 'file_column' => 'featured_image',
            'date_window' => ['start' => 'publication_date', 'end' => 'expiry_date'],
            'columns' => ['title' => 'Title', 'publication_date' => 'Published', 'expiry_date' => 'Expires', 'status' => 'Status'],
            'fields' => [
                'title' => ['label' => 'News title', 'type' => 'text', 'required' => true, 'full' => true],
                'description' => ['label' => 'Short description', 'type' => 'textarea', 'required' => true, 'full' => true],
                'featured_image' => ['label' => 'Featured image', 'type' => 'file', 'folder' => 'news', 'accept' => 'image/jpeg,image/png,image/webp,image/gif', 'mimes' => ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'], 'full' => true],
                'publication_date' => ['label' => 'Publication date', 'type' => 'date', 'required' => true],
                'expiry_date' => ['label' => 'Expiry date', 'type' => 'date'],
                'status' => ['label' => 'Publication status', 'type' => 'select', 'options' => $publication, 'required' => true],
                'display_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'staff' => [
            'label' => 'Staff Directory', 'singular' => 'Staff member', 'active' => 'staff', 'icon' => 'bi-people', 'file_column' => 'photo',
            'columns' => ['name' => 'Name', 'position' => 'Position', 'department' => 'Department', 'status' => 'Status'],
            'fields' => [
                'name' => ['label' => 'Full name', 'type' => 'text', 'required' => true],
                'position' => ['label' => 'Position', 'type' => 'text', 'required' => true],
                'department' => ['label' => 'Department', 'type' => 'text'],
                'email' => ['label' => 'Email address', 'type' => 'email'],
                'photo' => ['label' => 'Staff photograph', 'type' => 'file', 'folder' => 'staff', 'accept' => 'image/jpeg,image/png,image/webp', 'mimes' => ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'], 'full' => true],
                'status' => ['label' => 'Publication status', 'type' => 'select', 'options' => $publication, 'required' => true],
            ],
        ],
        'staff_leave' => [
            'label' => 'Staff Leave', 'singular' => 'Leave record', 'active' => 'leave', 'icon' => 'bi-calendar2-week',
            'columns' => ['staff_id' => 'Staff member', 'leave_type' => 'Leave type', 'start_date' => 'Starts', 'end_date' => 'Ends', 'status' => 'Status'],
            'fields' => [
                'staff_id' => ['label' => 'Staff member', 'type' => 'staff_select', 'required' => true],
                'leave_type' => ['label' => 'Type of leave', 'type' => 'text', 'required' => true],
                'start_date' => ['label' => 'Start date', 'type' => 'date', 'required' => true],
                'end_date' => ['label' => 'End date', 'type' => 'date', 'required' => true],
                'note' => ['label' => 'Public note', 'type' => 'textarea', 'full' => true],
                'status' => ['label' => 'Publication status', 'type' => 'select', 'options' => $publication, 'required' => true],
                'display_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'programmes' => [
            'label' => 'Academic Programmes', 'singular' => 'Programme', 'active' => 'programmes', 'icon' => 'bi-mortarboard', 'status_column' => 'publication_status',
            'columns' => ['name' => 'Programme', 'abbreviation' => 'Abbreviation', 'duration' => 'Duration', 'programme_status' => 'Availability', 'publication_status' => 'Status'],
            'fields' => [
                'name' => ['label' => 'Programme name', 'type' => 'text', 'required' => true, 'full' => true],
                'abbreviation' => ['label' => 'Abbreviation', 'type' => 'text', 'required' => true],
                'duration' => ['label' => 'Duration', 'type' => 'text', 'required' => true],
                'description' => ['label' => 'Short description', 'type' => 'textarea', 'required' => true, 'full' => true],
                'programme_status' => ['label' => 'Programme status', 'type' => 'select', 'options' => ['active'=>'Active','inactive'=>'Inactive'], 'required' => true],
                'publication_status' => ['label' => 'Publication status', 'type' => 'select', 'options' => $publication, 'required' => true],
                'display_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'ticker_notices' => [
            'label' => 'Ticker Notices', 'singular' => 'Ticker notice', 'active' => 'ticker', 'icon' => 'bi-broadcast-pin',
            'date_window' => ['start' => 'start_date', 'end' => 'expiry_date'],
            'columns' => ['message' => 'Notice', 'priority' => 'Priority', 'start_date' => 'Starts', 'expiry_date' => 'Expires', 'status' => 'Status'],
            'fields' => [
                'message' => ['label' => 'Notice message', 'type' => 'textarea', 'required' => true, 'full' => true],
                'priority' => ['label' => 'Priority', 'type' => 'select', 'options' => $priorities, 'required' => true],
                'start_date' => ['label' => 'Start date', 'type' => 'date', 'required' => true],
                'expiry_date' => ['label' => 'Expiry date', 'type' => 'date'],
                'status' => ['label' => 'Publication status', 'type' => 'select', 'options' => $publication, 'required' => true],
                'display_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            ],
        ],
        'media_slides' => [
            'label' => 'Media Slideshow', 'singular' => 'Media slide', 'active' => 'slides', 'icon' => 'bi-images', 'file_column' => 'media_path',
            'date_window' => ['start' => 'start_date', 'end' => 'expiry_date'],
            'columns' => ['title' => 'Title', 'media_type' => 'Type', 'duration_seconds' => 'Duration', 'status' => 'Status', 'display_order' => 'Order'],
            'fields' => [
                'title' => ['label' => 'Slide title', 'type' => 'text', 'required' => true, 'full' => true],
                'caption' => ['label' => 'Caption', 'type' => 'textarea', 'full' => true],
                'media_type' => ['label' => 'Media type', 'type' => 'select', 'options' => ['image'=>'Image','video'=>'Video'], 'required' => true],
                'media_path' => ['label' => 'Image or video file', 'type' => 'file', 'folder' => 'slides', 'accept' => 'image/jpeg,image/png,image/webp,video/mp4,video/webm', 'mimes' => ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','video/mp4'=>'mp4','video/webm'=>'webm'], 'full' => true],
                'duration_seconds' => ['label' => 'Display duration (seconds)', 'type' => 'number', 'default' => 8, 'required' => true],
                'start_date' => ['label' => 'Start date', 'type' => 'date'],
                'expiry_date' => ['label' => 'Expiry date', 'type' => 'date'],
                'status' => ['label' => 'Publication status', 'type' => 'select', 'options' => $publication, 'required' => true],
                'display_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            ],
        ],
    ];
}

