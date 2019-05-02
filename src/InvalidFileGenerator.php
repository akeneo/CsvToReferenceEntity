<?php

declare(strict_types=1);

namespace App;

use Box\Spout\Writer\WriterInterface;
use Box\Spout\Writer\CSV\Writer;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;

/**
 * Generate a file containing all invalid lines in the original file
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class InvalidFileGenerator
{
    /** @var WriterInterface */
    private $writer;

    /** @var boolean */
    private $isFirstLine = true;

    /** @var string|null */
    private $invalidPath = null;

    public function __construct(Writer $writer)
    {
        $this->writer = $writer;
    }

    public function fromResponses(array $responses, array $linesToWrite, string $filePath, array $headers): void
    {
        foreach ($responses as $lineNumber => $response) {
            $statusCode = $response['status_code'];

            if($statusCode < 200 || $statusCode >= 300) {
                if ($this->isFirstLine) {
                    $this->initializeFile($filePath, $headers);
                    $this->isFirstLine = false;
                }

                $this->writer->addRow($linesToWrite[$lineNumber]);
            }
        }
    }

    public function fromRow(array $row, string $filePath, array $headers): void
    {
        if ($this->isFirstLine) {
            $this->initializeFile($filePath, $headers);
            $this->isFirstLine = false;
        }

        $this->writer->addRow($row);
    }

    public function hasInvalidFile(): bool
    {
        return !$this->isFirstLine;
    }

    public function getInvalidFilePath(): string
    {
        return $this->invalidPath;
    }

    private function initializeFile(string $filePath, array $headers): void
    {
        $pathInfo = pathinfo($filePath);
        $this->writer->setGlobalFunctionsHelper(new GlobalFunctionsHelper());
        $this->invalidPath = sprintf('%s/%s_invalid_items_%s.csv', $pathInfo['dirname'], $pathInfo['filename'], time());
        $this->writer->openToFile($this->invalidPath);
        $this->writer->addRow($headers);
    }
}
