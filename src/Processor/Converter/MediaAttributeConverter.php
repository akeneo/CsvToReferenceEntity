<?php

declare(strict_types=1);

namespace App\Processor\Converter;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Symfony\Component\Filesystem\Filesystem;

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

    /** @var Filesystem */
    private $filesystem;

    public function __construct(AkeneoPimEnterpriseClientInterface $pimClient, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->pimClient = $pimClient;
    }

    public function support(array $attribute): bool
    {
        return self::IMAGE_ATTRIBUTE_TYPE === $attribute['type'];
    }

    public function convert(array $attribute, string $data, array $context)
    {
        $mediaFilePath = $this->mediaFilePath($data, $context);
        $this->checkMediaExists($mediaFilePath);
        $mediaIdentifier = $this->uploadMediaToPIM($mediaFilePath);

        return $mediaIdentifier;
    }

    private function mediaFilePath(string $relativeMediaPath, array $context): string
    {
        $fileToImportPath = $context['filePath'];

        return sprintf('%s%s%s', dirname($fileToImportPath), DIRECTORY_SEPARATOR, $relativeMediaPath);
    }

    private function checkMediaExists(string $mediaFilePath): void
    {
        if (!$this->filesystem->exists($mediaFilePath)) {
            throw new \RuntimeException(sprintf('media file at path "%s" was not found.', $mediaFilePath));
        }
    }

    private function uploadMediaToPIM(string $mediaFilePath): string
    {
        try {
            $mediaIdentifier = $this->pimClient->getReferenceEntityMediaFileApi()->create($mediaFilePath);

            return $mediaIdentifier;
        } catch (\Exception $exception) {
            $message = sprintf(
                'An error occured while uploading the media at path "%s" to the PIM: %s',
                $mediaFilePath,
                $exception->getMessage()
            );

            throw new \RuntimeException($message);
        }
    }
}
