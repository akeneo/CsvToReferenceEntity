<?php

declare(strict_types=1);

namespace App\Writer;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

class RecordWriter
{
    /** @var AkeneoPimEnterpriseClientInterface */
    private $client;

    public function __construct(AkeneoPimEnterpriseClientInterface $client)
    {
        $this->client = $client;
    }

    public function write(string $referenceEntityCode, array $records): array
    {
        return $this->client->getReferenceEntityRecordApi()->upsertList($referenceEntityCode, $records);
    }
}
