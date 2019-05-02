<?php

namespace specs\App\Processor\Converter;

use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\TextAttributeConverter;
use PhpSpec\ObjectBehavior;

class TextAttributeConverterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DataConverterInterface::class);
        $this->shouldBeAnInstanceOf(TextAttributeConverter::class);
    }

    function it_supports_an_attribute()
    {
        $attribute = ['type' => 'other'];
        $this->support($attribute)->shouldReturn(false);

        $attribute = ['type' => 'text'];
        $this->support($attribute)->shouldReturn(true);
    }

    function it_converts_data_for_a_specific_attribute()
    {
        $attribute = ['type' => 'text'];

        $data = 'This is a lovely data';
        $this->convert($attribute, $data, [])->shouldReturn('This is a lovely data');

        $data = 'this,is,not,a,list';
        $this->convert($attribute, $data, [])->shouldReturn('this,is,not,a,list');
    }
}
