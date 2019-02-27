<?php

declare(strict_types=1);

namespace App\Reader;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Reader\IteratorInterface;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 *
 * Use Spout library to iterate on each rows of file.
 *
 * Iterates over CSV files.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CsvReader implements FileReaderInterface
{
    /** @var string */
    private $filePath;

    /** @var ReaderInterface */
    private $reader;

    /** @var \SplFileInfo */
    private $fileInfo;

    /** @var IteratorInterface */
    private $rows;

    /** @var array */
    private $headers;

    /**
     * @throws FileNotFoundException
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function __construct(string $filePath, array $options = [])
    {
        $this->filePath = $filePath;
        $this->fileInfo = new \SplFileInfo($filePath);

        if (!$this->fileInfo->isFile()) {
            throw new FileNotFoundException(sprintf('File "%s" could not be found', $this->filePath));
        }

        $this->reader = ReaderFactory::create(Type::CSV);
        if (isset($options)) {
            $this->setReaderOptions($options);
        }
        $this->reader->open($this->filePath);
        $this->reader->getSheetIterator()->rewind();

        $sheet = $this->reader->getSheetIterator()->current();
        $sheet->getRowIterator()->rewind();

        $this->headers = $sheet->getRowIterator()->current();
        $this->rows = $sheet->getRowIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->rows->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $data = $this->rows->current();

        if (!$this->valid() || null === $data || empty($data)) {
            $this->rewind();

            return null;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->rows->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->rows->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->rows->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $num = 0;
        foreach ($this->rows as $line) {
            $num++;
        }

        return $num - 1;
    }

    /**
     * Add options to Spout reader
     *
     * @param array $readerOptions
     *
     * @throws \InvalidArgumentException
     */
    private function setReaderOptions(array $readerOptions = [])
    {
        foreach ($readerOptions as $name => $option) {
            $setter = 'set' . ucfirst($name);
            if (method_exists($this->reader, $setter)) {
                $this->reader->$setter($option);
            } else {
                $message = sprintf('Option "%s" does not exist in reader "%s"', $setter, get_class($this->reader));
                throw new \InvalidArgumentException($message);
            }
        }
    }
}
