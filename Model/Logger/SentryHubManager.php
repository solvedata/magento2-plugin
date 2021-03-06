<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Logger;

use Jean85\PrettyVersions;
use Magento\Framework\Module\ModuleList;
use Nyholm\Psr7\Factory\Psr17Factory;
use Sentry\Client;
use Sentry\ClientBuilder;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\Transport\DefaultTransportFactory;
use SolveData\Events\Model\Config;

class SentryHubManager
{
    private $config;
    
    private $last_dsn;
    private $sentry_hub;
    private $moduleList;

    public function __construct(
        Config $config,
        ModuleList $moduleList
    ) {
        $this->config = $config;
        $this->moduleList = $moduleList;

        $this->last_dsn = null;
        $this->sentry_hub = null;
    }

    public function getHub(): ?HubInterface
    {
        try {
            $dsn = $this->config->getSentryDsn();
            if (empty($dsn)) {
                return null;
            }
    
            $hasValidDsn = !empty($dsn) && filter_var($dsn, FILTER_VALIDATE_URL);
            $hasDsnChanged = $this->last_dsn !== $dsn;
    
            if ($hasValidDsn && (empty($this->last_dsn) || $hasDsnChanged)) {
                // Set max_value_length, otherwise Sentry defaults to 1kib
                // which is not even enough for the stacktrace.
                $client = ClientBuilder::create([
                    'dsn' => $dsn,
                    'release' => $this->getExtensionVersion(),
                    'max_value_length' => 8 * 1024,
                ])
                    ->setTransportFactory($this->createTransportFactory())
                    ->getClient();
    
                $this->sentry_hub = new Hub($client);
                $this->last_dsn = $dsn;
            }
    
            return $this->sentry_hub;
        } catch (\Throwable $t) {
            // Fail silently if there is an unexpected error creating the Sentry client/hub.
            // This is to avoid a theoretical situation where the error handling code recursively throws errors.
            return null;
        }
    }

    private function createTransportFactory(): DefaultTransportFactory
    {
        $psr17Factory = new Psr17Factory();
        $httpClient = null;
        $logger = null;

        $httpClientFactory = new HttpClientFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $httpClient,
            Client::SDK_IDENTIFIER,
            PrettyVersions::getVersion('sentry/sentry')->getPrettyVersion()
        );

        return new DefaultTransportFactory(
            $psr17Factory,
            $psr17Factory,
            $httpClientFactory,
            $logger
        );
    }

    /**
     * Returns the current Magento plugin's version.
     *
     * @return string
     */
    private function getExtensionVersion(): string
    {
        try {
            return $this->moduleList->getOne('SolveData_Events')['setup_version'];
        } catch (\Throwable $t) {
            $this->logger->error('Failed to get extension version.', ['exception' => $t]);
            return 'unknown';
        }
    }
}
