<?php

declare(strict_types=1);

namespace specs\App\Processor\Converter;

use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\ReferenceEntityMultipleLinksConverter;
use PhpSpec\ObjectBehavior;

class ReferenceEntityMultipleLinksConverterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DataConverterInterface::class);
        $this->shouldBeAnInstanceOf(ReferenceEntityMultipleLinksConverter::class);
    }

    function it_supports_an_attribute()
    {
        $attribute = ['type' => 'other'];
        $this->support($attribute)->shouldReturn(false);

        $attribute = ['type' => 'reference_entity_multiple_links'];
        $this->support($attribute)->shouldReturn(true);
    }

    function it_converts_data_for_a_specific_attribute()
    {
        $attribute = ['type' => 'reference_entity_multiple_links'];

        $data = 'blue';
        $this->convert($attribute, $data, [])->shouldReturn(['blue']);

        $data = 'first_code,second_code';
        $this->convert($attribute, $data, [])->shouldReturn(['first_code', 'second_code']);

        $data = '';
        $this->convert($attribute, $data, [])->shouldReturn([]);
    }
}
