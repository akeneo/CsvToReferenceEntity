<?php

namespace specs\App\Processor\Converter;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityMediaFileApiInterface;
use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\MediaAttributeConverter;
use PhpSpec\ObjectBehavior;

class MediaAttributeConverterSpec extends ObjectBehavior
{
    function let(AkeneoPimEnterpriseClientInterface $pimClient)
    {
        $this->beConstructedWith($pimClient);
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
        AkeneoPimEnterpriseClientInterface $pimClient,
        ReferenceEntityMediaFileApiInterface $referenceEntityMediaFileApi
    ) {
        $filePath = '/path/to/file/to/import.csv';
        $attribute = ['type' => 'image'];
        $data = 'images/directory/image.jpg';
        $mediaToUploadFilePath = '/path/to/file/to/images/directory/image.jpg';
        $mediaIdentifier = 'pim_image_identifier';
        $pimClient->getReferenceEntityMediaFileApi()->willReturn($referenceEntityMediaFileApi);
        $referenceEntityMediaFileApi->create($mediaToUploadFilePath)->willReturn($mediaIdentifier);

        $this->convert($attribute, $data, ['filePath' => $filePath])->shouldReturn($mediaIdentifier);
    }
}
