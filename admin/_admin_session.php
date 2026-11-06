<?php
// admin/_admin_session.php

// WAJIB sebelum ada output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_name('ADMINSESSID');

    // opsional tapi bagus: cookie hanya untuk folder /admin
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/web_tokobangunan/admin', // sesuaikan dengan nama folder project kamu
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false, // kalau sudah HTTPS hosting -> true
    ]);

    session_start();
}

// (opsional) hardening biar session tidak gampang dibajak
if (!isset($_SESSION['__admin_inited'])) {
    session_regenerate_id(true);
    $_SESSION['__admin_inited'] = 1;
}