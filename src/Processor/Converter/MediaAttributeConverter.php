<?php

declare(strict_types=1);

namespace App\Processor\Converter;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MediaAttributeConverter implements DataConverterInterface
{
    private const IMAGE_ATTRIBUTE_TYPE = 'image';

    /** @var AkeneoPimEnterpriseClientInterface */
    private $pimClient;

    public function __construct(AkeneoPimEnterpriseClientInterface $pimClient)
    {
        $this->pimClient = $pimClient;
    }

    public function support(array $attribute): bool
    {
        return self::IMAGE_ATTRIBUTE_TYPE === $attribute['type'];
    }

    public function convert(array $attribute, string $data, array $context)
    {
        $mediaFilePath = $this->mediaFilePath($data, $context);
        $mediaIdentifier = $this->pimClient->getReferenceEntityMediaFileApi()->create($mediaFilePath);

        return $mediaIdentifier;
    }

    private function mediaFilePath(string $relativeMediaPath, array $context): string
    {
        $fileToImportPath = $context['filePath'];

        return sprintf('%s%s%s', dirname($fileToImportPath), DIRECTORY_SEPARATOR, $relativeMediaPath);
    }
}
