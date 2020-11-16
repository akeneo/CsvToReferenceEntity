<?php

namespace specs\App\Processor\Converter;

use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\NumberConverter;
use PhpSpec\ObjectBehavior;

class NumberConverterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DataConverterInterface::class);
        $this->shouldBeAnInstanceOf(NumberConverter::class);
    }

    function it_supports_a_number_attribute()
    {
        $attribute = ['type' => 'other'];
        $this->support($attribute)->shouldReturn(false);

        $attribute = ['type' => 'number'];
        $this->support($attribute)->shouldReturn(true);
    }

    function it_converts_data_for_a_numbers()
    {
        $attribute = ['type' => 'number'];

        $data = 15;
        $this->convert($attribute, $data, [])->shouldReturn('15');

        $data = '16';
        $this->convert($attribute, $data, [])->shouldReturn('16');

        $data = 17.18;
        $this->convert($attribute, $data, [])->shouldReturn('17.18');
    }
}
