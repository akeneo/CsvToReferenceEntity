<?php

declare(strict_types=1);

namespace specs\App\Processor\Converter;

use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\ReferenceEntitySingleLinkConverter;
use PhpSpec\ObjectBehavior;

class ReferenceEntitySingleLinkConverterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DataConverterInterface::class);
        $this->shouldBeAnInstanceOf(ReferenceEntitySingleLinkConverter::class);
    }

    function it_supports_an_attribute()
    {
        $attribute = ['type' => 'other'];
        $this->support($attribute)->shouldReturn(false);

        $attribute = ['type' => 'reference_entity_single_link'];
        $this->support($attribute)->shouldReturn(true);
    }

    function it_converts_data_for_a_specific_attribute()
    {
        $attribute = ['type' => 'reference_entity_single_link'];

        $data = 'blue';
        $this->convert($attribute, $data, [])->shouldReturn('blue');

        $data = 'invalid,code';
        $this->convert($attribute, $data, [])->shouldReturn('invalid,code');
    }
}
