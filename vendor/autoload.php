<?php
// Douman Panel Pro - Safe Autoloader Fallback
// Enables cPanel, Shared Hosting, and environments without Composer to run seamlessly.

if (file_exists(__DIR__ . '/composer/autoload_real.php')) {
    require_once __DIR__ . '/composer/autoload_real.php';
    return ComposerAutoloaderInit::getLoader();
}

spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $classPath . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
