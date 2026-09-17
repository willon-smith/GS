<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin.php';

unset($_SESSION['admin_id']);
session_regenerate_id(true);
flash('info', 'You have been signed out.');
redirect(admin_url('login.php'));
