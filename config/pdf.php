<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default PDF Engine Driver
    |--------------------------------------------------------------------------
    |
    | Driver yang digunakan untuk pembuatan dokumen surat resmi.
    | Pilihan yang didukung:
    | - 'dompdf' : 100% PHP murni menggunakan barryvdh/laravel-dompdf (sangat ringan & portabel)
    | - 'spatie' : Engine berbasis Chromium Headless (spatie/laravel-pdf & Browsershot)
    |
    */
    'driver' => env('PDF_DRIVER', 'dompdf'),

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Kertas & Format Default
    |--------------------------------------------------------------------------
    */
    'paper' => [
        'format' => 'a4',
        'orientation' => 'portrait',
    ],
];
