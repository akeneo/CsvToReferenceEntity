<?php

declare(strict_types=1);

namespace specs\App\Processor\Converter;

use App\Processor\Converter\DataConverterInterface;
use App\Processor\Converter\SingleOptionConverter;
use PhpSpec\ObjectBehavior;

class SingleOptionConverterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DataConverterInterface::class);
        $this->shouldBeAnInstanceOf(SingleOptionConverter::class);
    }

    function it_supports_an_attribute()
    {
        $attribute = ['type' => 'other'];
        $this->support($attribute)->shouldReturn(false);

        $attribute = ['type' => 'single_option'];
        $this->support($attribute)->shouldReturn(true);
    }

    function it_converts_data_for_a_specific_attribute()
    {
        $attribute = ['type' => 'single_option'];

        $data = 'blue';
        $this->convert($attribute, $data, [])->shouldReturn('blue');

        $data = 'invalid,code';
        $this->convert($attribute, $data, [])->shouldReturn('invalid,code');
    }
}
