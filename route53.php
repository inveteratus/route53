<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Classes\Route53;
use Dotenv\Dotenv;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryPeakUsageProcessor;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$path = __DIR__ . '/storage/logs';
if (!file_exists($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
}

$logger = new Logger('route53', [
    new RotatingFileHandler($path . '/route53.log', 7),
], [
    new MemoryPeakUsageProcessor(),
]);

$route53 = new Route53($logger, __DIR__ . '/storage');
$route53->run();
