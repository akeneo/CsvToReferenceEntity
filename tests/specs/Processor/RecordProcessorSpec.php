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
        $filePath = '/path/to/the/file/to/import.csv';
        $line = [
            'code' => 'ikea',
            'description-fr_FR-mobile' => 'Description française',
            'description-en_US-mobile' => 'English description',
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

        $valueKeyGenerator->extract($descriptionAttribute, 'description-en_US-mobile')->willReturn([
            'attribute' => 'description',
            'channel' => 'mobile',
            'locale' => 'en_US'
        ]);

        $valueKeyGenerator->extract($descriptionAttribute, 'description-fr_FR-mobile')->willReturn([
            'attribute' => 'description',
            'channel' => 'mobile',
            'locale' => 'fr_FR'
        ]);

        $valueKeyGenerator->extract($foundedAttribute, 'founded')->willReturn([
            'attribute' => 'founded',
            'channel' => null,
            'locale' => null
        ]);

        $context = ['filePath' => $filePath];
        $dataConverter->convert($descriptionAttribute, 'Description française', $context)->willReturn('Description française');
        $dataConverter->convert($descriptionAttribute, 'English description', $context)->willReturn('English description');
        $dataConverter->convert($foundedAttribute, '1977', $context)->willReturn('1977');

        $validStructure = [
            'description-en_US-mobile' => $descriptionAttribute,
            'description-fr_FR-mobile' => $descriptionAttribute,
            'founded' => $foundedAttribute
        ];

        $this->process($line, $validStructure, $filePath)->shouldReturn([
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
