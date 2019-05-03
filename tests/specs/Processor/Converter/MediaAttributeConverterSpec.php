<?php

namespace specs\App\Processor\Converter;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityMediaFileApiInterface;
use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\MediaAttributeConverter;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Filesystem\Filesystem;

class MediaAttributeConverterSpec extends ObjectBehavior
{
    function let(
        AkeneoPimEnterpriseClientInterface $pimClient,
        ReferenceEntityMediaFileApiInterface $referenceEntityMediaFileApi,
        Filesystem $filesystem
    ) {
        $pimClient->getReferenceEntityMediaFileApi()->willReturn($referenceEntityMediaFileApi);

        $this->beConstructedWith($pimClient, $filesystem);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DataConverterInterface::class);
        $this->shouldBeAnInstanceOf(MediaAttributeConverter::class);
    }

    function it_supports_media_attributes_only()
    {
        $this->support(['type' => 'image'])->shouldReturn(true);
        $this->support(['type' => 'other'])->shouldReturn(false);
    }

    function it_uploads_the_media_and_replaces_the_media_code_with_the_returned_media_identifier(
        ReferenceEntityMediaFileApiInterface $referenceEntityMediaFileApi,
        Filesystem $filesystem
    ) {
        $csvFileToImport = '/path/to/file/to/import.csv';
        $mediaToUpload = 'images/directory/image.jpg';
        $mediaToUploadAbsolutePath = '/path/to/file/to/images/directory/image.jpg';
        $mediaIdentifier = 'pim_image_identifier';

        $filesystem->exists($mediaToUploadAbsolutePath)->willReturn(true);
        $referenceEntityMediaFileApi->create($mediaToUploadAbsolutePath)->willReturn($mediaIdentifier);

        $this->convert([], $mediaToUpload, ['filePath' => $csvFileToImport])->shouldReturn($mediaIdentifier);
    }

    function it_throws_if_the_media_does_not_exists(Filesystem $filesystem)
    {
        $csvFileToImport = '/path/to/file/to/import.csv';
        $wrongMediaFilePath = 'images/directory/image.jpg';
        $wrongMediaAbsolutePath = '/path/to/file/to/images/directory/image.jpg';

        $filesystem->exists($wrongMediaAbsolutePath)->willReturn(false);

        $this->shouldThrow(\RuntimeException::class)
            ->during('convert', [[], $wrongMediaFilePath, ['filePath' => $csvFileToImport]]);
    }

    function it_throws_if_the_media_creation_in_the_pim_failed(
        ReferenceEntityMediaFileApiInterface $referenceEntityMediaFileApi,
        Filesystem $filesystem
    ) {
        $csvFileToImport = '/path/to/file/to/import.csv';
        $mediaFilePath = 'images/directory/image.jpg';
        $mediaToUploadAbsolutePath = '/path/to/file/to/images/directory/image.jpg';

        $filesystem->exists($mediaToUploadAbsolutePath)->willReturn(true);
        $referenceEntityMediaFileApi->create($mediaToUploadAbsolutePath)
            ->willThrow(new \Exception('500 internal server error'));

        $this->shouldThrow(\RuntimeException::class)
            ->during('convert', [[], $mediaFilePath, ['filePath' => $csvFileToImport]]);
    }
}
