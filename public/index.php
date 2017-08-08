<?php
$valid = true;
if (!version_compare(PHP_VERSION, '5.5.9', '>=')) {
    echo "ERROR: PHP 5.5.9 or higher is required.<br />";
    exit(0);
}
if (!extension_loaded('mbstring')) {
    echo "ERROR: The requested PHP extension mbstring is missing from your system.<br />";
    exit(0);
}

if (!empty(ini_get('open_basedir'))) {
    echo "ERROR: Please disable the <strong>open_basedir</strong> setting to continue.<br />";
    exit(0);
}

if (!(file_exists('../storage/app') && is_dir('../storage/app') && (is_writable('../storage/app')))) {
    echo "ERROR: The directory [/storage/app] must be writable by the web server.<br />";
    $valid = false;
}
if (!(file_exists('../storage/framework') && is_dir('../storage/framework') && (is_writable('../storage/framework')))) {
    echo "ERROR: The directory [/storage/framework] must be writable by the web server.<br />";
    $valid = false;
}
if (!(file_exists('../storage/logs') && is_dir('../storage/logs') && (is_writable('../storage/logs')))) {
    echo "ERROR: The directory [/storage/logs] must be writable by the web server.<br />";
    $valid = false;
}
if (!(file_exists('../bootstrap/cache') && is_dir('../bootstrap/cache') && (is_writable('../bootstrap/cache')))) {
    echo "ERROR: The directory [/bootstrap/cache] must be writable by the web server.<br />";
    $valid = false;
}

if($valid) {
    require 'main.php';
}
