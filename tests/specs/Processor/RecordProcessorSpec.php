<?php

declare(strict_types=1);

namespace specs\App\Processor;

use App\Processor\Converter\DataConverter;
use App\Processor\ValueKeyGenerator;
use PhpSpec\ObjectBehavior;

class RecordProcessorSpec extends ObjectBehavior
{
    function let(ValueKeyGenerator $valueKeyGenerator, DataConverter $dataConverter)
    {
        $this->beConstructedWith($valueKeyGenerator, $dataConverter);
    }

    function it_processes_a_record_line(ValueKeyGenerator $valueKeyGenerator, DataConverter $dataConverter)
    {
        $line = [
            'code' => 'ikea',
            'description-mobile-fr_FR' => 'Description française',
            'description-mobile-en_US' => 'English description',
            'founded' => '1977'
        ];

        $descriptionAttribute = [
            'code' => 'description',
            'value_per_channel' => true,
            'value_per_locale' => true,
        ];

        $foundedAttribute = [
            'code' => 'founded',
            'value_per_channel' => false,
            'value_per_locale' => false,
        ];

        $attributes = [
            $descriptionAttribute,
            $foundedAttribute,
        ];

        $indexedValueKeys = [
            'description' => [
                'description-mobile-en_US',
                'description-mobile-fr_FR',
            ],
            'founded' => [
                'founded'
            ]
        ];

        $valueKeyGenerator->extract($descriptionAttribute, 'description-mobile-en_US')->willReturn([
            'attribute' => 'description',
            'channel' => 'mobile',
            'locale' => 'en_US'
        ]);

        $valueKeyGenerator->extract($descriptionAttribute, 'description-mobile-fr_FR')->willReturn([
            'attribute' => 'description',
            'channel' => 'mobile',
            'locale' => 'fr_FR'
        ]);

        $valueKeyGenerator->extract($foundedAttribute, 'founded')->willReturn([
            'attribute' => 'founded',
            'channel' => null,
            'locale' => null
        ]);

        $dataConverter->convert($descriptionAttribute, 'Description française')->willReturn('Description française');
        $dataConverter->convert($descriptionAttribute, 'English description')->willReturn('English description');
        $dataConverter->convert($foundedAttribute, '1977')->willReturn('1977');

        $this->process($line, $attributes, $indexedValueKeys)->shouldReturn([
            'code' => 'ikea',
            'values' => [
                'description' => [
                    [
                        'channel' => 'mobile',
                        'locale' => 'en_US',
                        'data' => 'English description'
                    ],
                    [
                        'channel' => 'mobile',
                        'locale' => 'fr_FR',
                        'data' => 'Description française'
                    ],
                ],
                'founded' => [
                    [
                        'channel' => null,
                        'locale' => null,
                        'data' => '1977'
                    ],
                ]
            ]
        ]);
    }
}
