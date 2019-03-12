<?php

namespace specs\App\Processor;

use App\Processor\ValueKeyGenerator;
use PhpSpec\ObjectBehavior;

class ValueKeyGeneratorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ValueKeyGenerator::class);
    }

    function it_generates_all_value_keys_for_the_given_attribute_on_all_given_channels()
    {
        $channels = [
            [
                'code' => 'mobile',
                'locales' => ['en_US', 'fr_FR']
            ],
            [
                'code' => 'ecommerce',
                'locales' => ['en_US']
            ],
        ];

        // Not scopable and not localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => false,
            'value_per_locale' => false,
        ];
        $this->generate($attribute, $channels)->shouldReturn(['name' => $attribute]);

        // Not scopable and localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => false,
            'value_per_locale' => true,
        ];
        $this->generate($attribute, $channels)->shouldReturn([
            'name-en_US' => $attribute,
            'name-fr_FR' => $attribute,
        ]);

        // Scopable and not localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => true,
            'value_per_locale' => false,
        ];
        $this->generate($attribute, $channels)->shouldReturn([
            'name-mobile' => $attribute,
            'name-ecommerce' => $attribute,
        ]);

        // Scopable and localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => true,
            'value_per_locale' => true,
        ];
        $this->generate($attribute, $channels)->shouldReturn([
            'name-en_US-mobile' => $attribute,
            'name-fr_FR-mobile' => $attribute,
            'name-en_US-ecommerce' => $attribute,
        ]);
    }

    function it_extracts_information_from_the_given_value_key_about_the_given_attribute()
    {
        // Not scopable and not localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => false,
            'value_per_locale' => false,
        ];
        $this->extract($attribute, 'name')->shouldReturn([
            'attribute' => 'name',
            'channel' => null,
            'locale' => null,
        ]);

        // Not scopable and localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => false,
            'value_per_locale' => true,
        ];
        $this->extract($attribute, 'name-fr_FR')->shouldReturn([
            'attribute' => 'name',
            'locale' => 'fr_FR',
            'channel' => null,
        ]);

        // Scopable and not localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => true,
            'value_per_locale' => false,
        ];
        $this->extract($attribute, 'name-mobile')->shouldReturn([
            'attribute' => 'name',
            'channel' => 'mobile',
            'locale' => null,
        ]);

        // Scopable and localizable attribute
        $attribute = [
            'code' => 'name',
            'value_per_channel' => true,
            'value_per_locale' => true,
        ];
        $this->extract($attribute, 'name-fr_FR-mobile')->shouldReturn([
            'attribute' => 'name',
            'locale' => 'fr_FR',
            'channel' => 'mobile',
        ]);
    }
}
