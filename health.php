<?php
// Health check for hosting platforms. Deliberately does not touch the database.
header('Content-Type: text/plain');
header('Cache-Control: no-store');
echo 'ok';
