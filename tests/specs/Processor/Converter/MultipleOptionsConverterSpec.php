<?php

declare(strict_types=1);

namespace specs\App\Processor\Converter;

use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\MultipleOptionsConverter;
use PhpSpec\ObjectBehavior;

class MultipleOptionsConverterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DataConverterInterface::class);
        $this->shouldBeAnInstanceOf(MultipleOptionsConverter::class);
    }

    function it_supports_an_attribute()
    {
        $attribute = ['type' => 'other'];
        $this->support($attribute)->shouldReturn(false);

        $attribute = ['type' => 'multiple_options'];
        $this->support($attribute)->shouldReturn(true);
    }

    function it_converts_data_for_a_specific_attribute()
    {
        $attribute = ['type' => 'multiple_options'];

        $data = 'blue';
        $this->convert($attribute, $data, [])->shouldReturn(['blue']);

        $data = 'first_code,second_code';
        $this->convert($attribute, $data, [])->shouldReturn(['first_code', 'second_code']);

        $data = '';
        $this->convert($attribute, $data, [])->shouldReturn([]);
    }
}
