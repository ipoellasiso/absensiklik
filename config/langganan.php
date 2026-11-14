<?php

return [
    'masa_uji_coba_hari' => 3,
    'masa_tenggang_hari' => 7,
    'batas_uji_coba_siswa' => 2,
    'pengingat_hari' => [14, 7, 3, 1],

    'penerbit' => env('LANGGANAN_PENERBIT', 'Nama Penyedia Layanan'),
    'kontak' => env('LANGGANAN_KONTAK', ''),
    'rekening' => [
        'bank' => env('LANGGANAN_BANK', 'Nama Bank'),
        'nomor' => env('LANGGANAN_REKENING', '0000000000'),
        'atas_nama' => env('LANGGANAN_ATAS_NAMA', 'Nama Pemilik Rekening'),
    ],

        'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    ],
];