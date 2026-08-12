<?php

declare(strict_types=1);

return [
    'show_warnings' => env('DOMPDF_SHOW_WARNINGS', false),
    'orientation' => env('DOMPDF_ORIENTATION', 'portrait'),
    'defines' => [
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),
        'prepend_child' => false,
        'log_output_file' => null,
        'font_height_ratio' => env('DOMPDF_FONT_HEIGHT_RATIO', 1.1),
        'is_php_enabled' => env('DOMPDF_ENABLE_PHP', false),
        'is_remote_enabled' => env('DOMPDF_ENABLE_REMOTE', true),
        'is_javascript_enabled' => env('DOMPDF_ENABLE_JAVASCRIPT', false),
        'is_html5_parser_enabled' => env('DOMPDF_ENABLE_HTML5PARSER', true),
        'is_font_subsetting_enabled' => env('DOMPDF_ENABLE_FONT_SUBSETTING', false),
        'is_pdf_background_image' => env('DOMPDF_ENABLE_PDF_BACKGROUND_IMAGE', true),
    ],
];
