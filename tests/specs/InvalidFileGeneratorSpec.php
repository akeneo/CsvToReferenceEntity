<?php

namespace specs\App;

use PhpSpec\ObjectBehavior;
use App\InvalidFileGenerator;
use Box\Spout\Writer\CSV\Writer;
use Prophecy\Argument;

class InvalidFileGeneratorSpec extends ObjectBehavior
{
    function let(Writer $writer)
    {
        $this->beConstructedWith($writer);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(InvalidFileGenerator::class);
    }

    function it_should_write_invalid_line_for_the_given_invalid_response($writer) {
        $writer->openToFile(Argument::any())->shouldBeCalled();
        $writer->setGlobalFunctionsHelper(Argument::any())->shouldBeCalled();
        $writer->addRow(['code', 'label-en_US'])->shouldBeCalled();
        $writer->addRow(['ikea',12])->shouldBeCalled();

        $this->fromResponses(
            [['status_code' => 400]],
            [['ikea', 12]],
            'original_file.csv',
            ['code', 'label-en_US']
        );
    }

    function it_should_write_invalid_line_for_the_given_valid_response($writer) {
        $writer->openToFile(Argument::any())->shouldNotBeCalled();
        $writer->setGlobalFunctionsHelper(Argument::any())->shouldNotBeCalled();
        $writer->addRow(['code', 'label-en_US'])->shouldNotBeCalled();
        $writer->addRow(['ikea',12])->shouldNotBeCalled();

        $this->fromResponses(
            [['status_code' => 200]],
            [['ikea', 12]],
            'original_file.csv',
            ['code', 'label-en_US']
        );
    }

    function it_should_write_invalid_line_for_the_given_invalid_row($writer) {
        $writer->openToFile(Argument::any())->shouldBeCalled();
        $writer->setGlobalFunctionsHelper(Argument::any())->shouldBeCalled();
        $writer->addRow(['code', 'label-en_US'])->shouldBeCalled();
        $writer->addRow(['ikea',12])->shouldBeCalled();
        $writer->addRow(['akeneo',15])->shouldBeCalled();

        $this->fromRow(
            ['ikea', 12],
            'original_file.csv',
            ['code', 'label-en_US']
        );
        $this->fromRow(
            ['akeneo', 15],
            'original_file.csv',
            ['code', 'label-en_US']
        );
    }
}
