<?php
// Ellenőrizzük, hogy még nincs-e elindítva session
if (session_status() === PHP_SESSION_NONE) {
    // Session beállítások
    ini_set('session.cookie_lifetime', 86400); // 24 óra
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);
    
    // Session indítása
    session_start();
} 