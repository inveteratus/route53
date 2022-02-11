<?php
declare(strict_types=1);

namespace App\Classes;

use Aws\Credentials\Credentials;
use Aws\Exception\CredentialsException;
use Aws\Route53\Exception\Route53Exception;
use Aws\Route53\Route53Client;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Route53
{
    private LoggerInterface $logger;
    private string $storage;

    public function __construct(LoggerInterface $logger, string $storage)
    {
        $this->logger = $logger;
        $this->storage = $storage;
    }

    public function run(): void
    {
        $public = $this->get_public_ip();
        $cached = $this->get_cached_ip();

        if ($public !== $cached) {
            $this->set_public_ip($public);
            $this->set_cached_ip($public);

            $this->logger->debug('Address updated', [
                'old' => $cached,
                'new' => $public,
            ]);
        }
    }

    public function get_public_ip(): string
    {
        $ip = file_get_contents($_ENV['SERVICE']);
        if (!is_string($ip)) {
            $this->logger->error('Connection Error');
            exit(1);
        }

        $ip = trim($ip);
        if (!preg_match('`^\d+(\.\d+){3}$`', $ip)) {
            $this->logger->error('Service Error');
            exit(1);
        }

        return $ip;
    }

    public function set_public_ip(string $ip): void
    {
        try {
            $api = new Route53Client([
                'version' => 'latest',
                'region' => $_ENV['AWS_REGION'],
                'credentials' => new Credentials($_ENV['AWS_KEY'], $_ENV['AWS_SECRET']),
            ]);

            $api->changeResourceRecordSets([
                'HostedZoneId' => $_ENV['ROUTE53_ZONE'],
                'ChangeBatch' => [
                    'Comment' => 'Dynamic DNS',
                    'Changes' => [
                        [
                            'Action' => 'UPSERT',
                            'ResourceRecordSet' => [
                                'Name' => $_ENV['ROUTE53_RECORD'],
                                'Type' => 'A',
                                'TTL' => 300,
                                'ResourceRecords' => [
                                    [
                                        'Value' => $ip
                                    ]
                                ],
                            ]
                        ]
                    ],
                ],
            ]);
        }
        catch (Route53Exception $e) {
            $this->logger->error('Route53 Exception');
            $this->logger->error($e->getMessage());
            exit(1);
        }
        catch (CredentialsException $e) {
            $this->logger->error('Credentials Exception');
            $this->logger->error($e->getMessage());
            exit(1);
        }
        catch (Exception $e) {
            $this->logger->error($e->getMessage());
            exit(1);
        }
    }

    /**
     * @return string
     */
    private function cache_file(): string
    {
        return $this->storage . '/cache/current.ip';
    }

    /**
     * @param string $file
     * @throws RuntimeException
     */
    private function ensure_path_exists(string $file): void
    {
        $path = dirname($file);

        if (!file_exists($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    private function get_cached_ip(): string
    {
        $file = $this->cache_file();
        $this->ensure_path_exists($file);

        return file_exists($file) ? file_get_contents($file) : '0.0.0.0';
    }

    /**
     * @param string $ip
     * @throws RuntimeException
     */
    private function set_cached_ip(string $ip): void
    {
        $file = $this->cache_file();
        $this->ensure_path_exists($file);

        file_put_contents($file, $ip);
    }
}
