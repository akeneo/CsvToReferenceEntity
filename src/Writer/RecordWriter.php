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

    /**
     * @param string $referenceEntityCode
     * @param array $records
     * @param bool $importMedia
     *
     * @return array
     */
    public function write(string $referenceEntityCode, array $records, $importMedia = false): array
    {
        if ($importMedia) {
            // Create MediaFile if not already exist before to import to reference entity record
            foreach ($records as $key => $record) {

                foreach ($record['values'] as $i => $values) {
                    foreach ($values as $j => $value) {
                        if (!empty($value['data'])) {

                            $pathinfo = pathinfo($value['data'], PATHINFO_EXTENSION);

                            if (!empty($pathinfo) && @file_exists($value['data'])) {
                                $code = $this->client->getReferenceEntityMediaFileApi()->create($value['data']);
                                $records[$key]['values'][$i][$j] = [
                                    'channel' => $value['channel'],
                                    'locale'  => $value['locale'],
                                    'data'    => $code,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $this->client->getReferenceEntityRecordApi()->upsertList($referenceEntityCode, $records);
    }
}
