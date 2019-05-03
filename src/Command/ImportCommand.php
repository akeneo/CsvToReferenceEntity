<?php

declare(strict_types=1);

namespace App\Command;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use App\ApiClientFactory;
use App\FileLogger;
use App\Processor\Converter\DataConverter;
use App\Processor\RecordProcessor;
use App\Reader\CsvReader;
use App\Writer\RecordWriter;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use App\Processor\StructureGenerator;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\InvalidFileGenerator;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ImportCommand extends Command
{
    protected static $defaultName = 'app:import';

    private const BATCH_SIZE = 100;

    private const CSV_FIELD_DELIMITER = ';';
    private const CSV_FIELD_ENCLOSURE = '"';
    private const CSV_END_OF_LINE_CHARACTER = "\n";

    /** @var StructureGenerator */
    private $structureGenerator;

    /** @var DataConverter */
    private $converter;

    /** @var RecordProcessor */
    private $processor;

    /** @var FileLogger */
    private $logger;

    /** @var InvalidFileGenerator */
    private $invalidFileGenerator;

    /** @var SymfonyStyle */
    private $io;

    /** @var AkeneoPimEnterpriseClientInterface */
    private $apiClient;

    /** @var CsvReader */
    private $reader;

    public function __construct(
        StructureGenerator $structureGenerator,
        DataConverter $converter,
        RecordProcessor $processor,
        FileLogger $logger,
        InvalidFileGenerator $invalidFileGenerator,
        AkeneoPimEnterpriseClientInterface $apiClient
    ) {
        parent::__construct(static::$defaultName);

        $this->structureGenerator = $structureGenerator;
        $this->converter = $converter;
        $this->processor = $processor;
        $this->logger = $logger;
        $this->invalidFileGenerator = $invalidFileGenerator;
        $this->apiClient = $apiClient;
    }

    protected function configure()
    {
        $this
            ->setDescription('Import a CSV file as Reference Entity Records')
            ->addArgument('filePath', InputArgument::REQUIRED, 'The filePath of the file to import.')
            ->addArgument('referenceEntityCode', InputArgument::REQUIRED, 'The reference entity code the records belong to.')
            ->addOption('apiUsername', null, InputOption::VALUE_OPTIONAL, 'The username of the user.', getenv('AKENEO_API_USERNAME'))
            ->addOption('apiPassword', null, InputOption::VALUE_OPTIONAL, 'The password of the user.', getenv('AKENEO_API_PASSWORD'))
            ->addOption('apiClientId', null, InputOption::VALUE_OPTIONAL, '', getenv('AKENEO_API_CLIENT_ID'))
            ->addOption('apiClientSecret', null, InputOption::VALUE_OPTIONAL, '', getenv('AKENEO_API_CLIENT_SECRET'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->logger->startLogging();

        $referenceEntityCode = $input->getArgument('referenceEntityCode');
        $filePath = $input->getArgument('filePath');

        try {
            $this->reader = new CsvReader(
                $filePath, [
                    'fieldDelimiter' => self::CSV_FIELD_DELIMITER,
                    'fieldEnclosure' => self::CSV_FIELD_ENCLOSURE,
                    'endOfLineCharacter' => self::CSV_END_OF_LINE_CHARACTER,
                ]
            );
        } catch (IOException|UnsupportedTypeException|ReaderNotOpenedException $e) {
            $this->io->error($e->getMessage());

            exit;
        }

        $this->io->title('Custom entity bundle migration tool');
        $this->io->text([
            'Welcome to this migration tool made to help migrate your records from the Custom',
            'Entity bundle to the new Reference entity feature You are currently using the "interactive mode".',
            'If you want to automate this process or don\'t want to use default values, add the --no-interaction flag when you call this command.'
        ]);

        $this->io->newLine(2);
        $this->io->title(
            sprintf(
                'Retrieving information from your Akeneo PIM instance (%s)... ',
                getenv('AKENEO_API_BASE_URI')
            )
        );

        $attributes = $this->fetchReferenceEntityAttributes($referenceEntityCode);
        $channels = $this->fetchChannels();

        $this->io->success('OK');

        // Filter what we gonna process from file
        $validValueKeys = $this->filterValidValueKeys($attributes, $channels);

        $this->io->title(
            sprintf('Start importing file "%s" for reference entity "%s"', $filePath, $referenceEntityCode)
        );
        $this->io->text('Everything will be logged here:');
        $this->io->text($this->logger->getLogFilePath());
        $this->io->newLine(2);

        // Import records
        $this->importRecords($output, $filePath, $referenceEntityCode, $validValueKeys, $attributes, $channels);

        $this->io->newLine(2);
        $this->io->success(sprintf(
            'Done (%s created, %s updated, %s skipped)',
            $this->logger->numCreated,
            $this->logger->numUpdated,
            $this->logger->numSkipped
        ));

        if ($this->invalidFileGenerator->hasInvalidFile()) {
            $this->io->text(['Invalid items file generated here:', $this->invalidFileGenerator->getInvalidFilePath()]);
            $this->io->newLine(2);
        }
    }

    private function fetchReferenceEntityAttributes(string $referenceEntityCode): array
    {
        $attributes = $this->apiClient->getReferenceEntityAttributeApi()->all($referenceEntityCode);

        $indexedAttributes = [];
        foreach ($attributes as $attribute) {
            $indexedAttributes[$attribute['code']] = $attribute;
        }

        return $indexedAttributes;
    }

    private function fetchChannels(): array
    {
        return iterator_to_array($this->apiClient->getChannelApi()->all(100));
    }

    private function filterValidValueKeys(array $attributes, array $channels): array
    {
        $structure = $this->structureGenerator->generate($attributes, $channels);
        $validValueKeys = array_keys($structure);

        $invalidHeaders = array_diff($this->reader->getHeaders(), array_merge($validValueKeys, ['code']));
        $validHeaders = array_diff($this->reader->getHeaders(), $invalidHeaders);

        $unsupportedHeaders = array_filter($validHeaders, function ($header) use ($structure) {
            return 'code' !== $header && !$this->converter->support($structure[$header]);
        });

        if (!empty($invalidHeaders)) {
            $this->logger->info(sprintf('Invalid headers: %s', json_encode(array_values($invalidHeaders))));
        }

        if (!empty($unsupportedHeaders)) {
            $this->logger->info(sprintf('Unsupported headers: %s', json_encode(array_values($unsupportedHeaders))));
        }

        if (count($invalidHeaders) > 0) {
            $this->io->title('The following properties won\'t be imported by this tool:');
            $this->io->listing($invalidHeaders);
            $this->io->text(
                'They are either not defined in your PIM for this reference entity, or their context is not valid (channel or locale unrecognized)'
            );

            $this->io->title('The following properties are not supported by this tool and will be skipped:');
            $this->io->listing($unsupportedHeaders);
            $this->io->text(
                'There is no converter registered that supports these attributes'
            );

            $continue = $this->io->confirm('Do you still want to proceed?');

            if (!$continue) {
                exit;
            }
        }

        return array_diff($validHeaders, $unsupportedHeaders);
    }

    private function importRecords(
        OutputInterface $output,
        string $filePath,
        string $referenceEntityCode,
        array $validValueKeys,
        array $attributes,
        array $channels
    ): void {
        $progressBar = new ProgressBar($output, $this->reader->count());
        $progressBar->start();

        $recordWriter = new RecordWriter($this->apiClient);

        $recordsToWrite = [];
        $linesToWrite = [];

        foreach ($this->reader as $lineNumber => $row) {
            if ($lineNumber === 1) {
                continue;
            }

            if (!$this->isHeaderValid($row)) {
                $message = sprintf(
                    'Skipped line %s: the number of values is not equal to the number of headers',
                    $lineNumber
                );
                $this->skipRowWithMessage($filePath, $row, $message);

                continue;
            }

            $line = array_combine($this->reader->getHeaders(), $row);
            $validHeaders = array_intersect($this->reader->getHeaders(), $validValueKeys);

            $structure = $this->structureGenerator->generate($attributes, $channels);
            $validStructure = array_intersect_key($structure, array_flip($validHeaders));

            try {
                $recordsToWrite[] = $this->processor->process($line, $validStructure, $filePath);
            } catch (\Exception $e) {
                $this->skipRowWithMessage($filePath, $row, $e->getMessage());

                continue;
            }
            $linesToWrite[] = $line;

            if (count($recordsToWrite) === self::BATCH_SIZE) {
                $this->writeRecords($filePath, $referenceEntityCode, $recordWriter, $recordsToWrite, $linesToWrite);

                $recordsToWrite = [];
                $linesToWrite = [];
                $progressBar->advance(self::BATCH_SIZE);
            }
        }

        if (!empty($recordsToWrite)) {
            $this->writeRecords($filePath, $referenceEntityCode, $recordWriter, $recordsToWrite, $linesToWrite);
            $progressBar->advance(count($recordsToWrite));
        }

        $progressBar->finish();
    }

    private function writeRecords(
        string $filePath,
        string $referenceEntityCode,
        RecordWriter $recordWriter,
        array $recordsToWrite,
        array $linesToWrite
    ): void {
        $responses = $recordWriter->write($referenceEntityCode, $recordsToWrite);

        $this->logger->logResponses($responses);
        $this->invalidFileGenerator->fromResponses($responses, $linesToWrite, $filePath, $this->reader->getHeaders());
    }

    private function isHeaderValid($row): bool
    {
        return count($this->reader->getHeaders()) === count($row);
    }

    private function skipRowWithMessage(string $filePath, $row, string $message): void
    {
        $this->logger->skip($message);
        $this->invalidFileGenerator->fromRow($row, $filePath, $this->reader->getHeaders());
    }
}
