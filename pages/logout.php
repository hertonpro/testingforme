<?php
// ============================================================
// Page : Déconnexion
// ============================================================

Auth::logout();
session_destroy();
redirect('index.php?page=login');
