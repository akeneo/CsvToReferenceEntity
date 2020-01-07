<?php

declare(strict_types=1);

namespace App\Writer;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

class AssetWriter
{
    /** @var AkeneoPimEnterpriseClientInterface */
    private $client;

    public function __construct(AkeneoPimEnterpriseClientInterface $client)
    {
        $this->client = $client;
    }

    public function write(string $assetCode, array $assets): array
    {
        return $this->client->getAssetManagerApi()->upsertList($assetCode, $assets);
    }
}
