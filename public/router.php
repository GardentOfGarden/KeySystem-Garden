<?php
$path = $_SERVER['REQUEST_URI'];
if (file_exists(__DIR__ . $path)) {
    return false;
}
include __DIR__ . '/index.html';
?>
