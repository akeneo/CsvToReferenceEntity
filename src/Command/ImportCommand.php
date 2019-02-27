<?php

declare(strict_types=1);

namespace App\Command;

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

    /** @var StructureGenerator */
    private $structureGenerator;

    /** @var AkeneoPimEnterpriseClientBuilder */
    private $clientBuilder;

    /** @var DataConverter */
    private $converter;

    /** @var RecordProcessor */
    private $processor;

    /** @var FileLogger */
    private $logger;

    /** @var InvalidFileGenerator */
    private $invalidFileGenerator;

    public function __construct(
        StructureGenerator $structureGenerator,
        AkeneoPimEnterpriseClientBuilder $clientBuilder,
        DataConverter $converter,
        RecordProcessor $processor,
        FileLogger $logger,
        InvalidFileGenerator $invalidFileGenerator
    ) {
        parent::__construct(static::$defaultName);

        $this->structureGenerator = $structureGenerator;
        $this->clientBuilder = $clientBuilder;
        $this->converter = $converter;
        $this->processor = $processor;
        $this->logger = $logger;
        $this->invalidFileGenerator = $invalidFileGenerator;
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
        $io = new SymfonyStyle($input, $output);

        $referenceEntityCode = $input->getArgument('referenceEntityCode');

        $apiClientId = $input->getOption('apiClientId');
        $apiClientSecret = $input->getOption('apiClientSecret');
        $apiUsername = $input->getOption('apiUsername');
        $apiPassword = $input->getOption('apiPassword');

        $batchSize = 100;

        $filePath = $input->getArgument('filePath');

        try {
            $reader = new CsvReader(
                $filePath, [
                    'fieldDelimiter' => ';',
                    'fieldEnclosure' => '"',
                    'endOfLineCharacter' => "\n",
                ]
            );
        } catch (IOException|UnsupportedTypeException|ReaderNotOpenedException $e) {
            $io->error($e->getMessage());

            return;
        }

        $client = $this->clientBuilder->buildAuthenticatedByPassword(
            $apiClientId,
            $apiClientSecret,
            $apiUsername,
            $apiPassword
        );
        $recordWriter = new RecordWriter($client);

        $io->title('Custom entity bundle migration tool');
        $io->text([
            'Welcome to this migration tool made to help migrate your records from the Custom',
            'Entity bundle to the new Reference entity feature You are currently using the "interactive mode".',
            'If you want to automate this process or don\'t want to use default values, add the --no-interaction flag when you call this command.'
        ]);

        $io->newLine(2);
        $io->title(sprintf(
            'Retrieving information from your Akeneo PIM instance (%s)... ',
            getenv('AKENEO_API_BASE_URI')
        ));

        $attributes = $client->getReferenceEntityAttributeApi()->all($referenceEntityCode);
        $channels = iterator_to_array($client->getChannelApi()->all(100));

        $io->success('OK');

        $headers = $reader->getHeaders();
        $indexedValueKeysToProcess = $this->structureGenerator->generate($attributes, $headers, $channels);
        $valueKeysToProcess = $this->flattenValueKeys($indexedValueKeysToProcess);

        $valueKeysToSkip = array_diff($headers, array_merge($valueKeysToProcess, ['code']));
        if (count($valueKeysToSkip) > 0) {
            $io->title('The following properties won\'t be imported by this tool:');
            $io->listing($valueKeysToSkip);
            $io->text('They are either not defined in your PIM for this reference entity, or their context is not valid (channel or locale unrecognized)');
            $continue = $io->confirm('Do you still want to proceed?');

            if (!$continue) return;
        }

        $indexedAttributes = $this->indexAttributes($attributes);

        $attributesToProcess = array_intersect_key($indexedAttributes, $indexedValueKeysToProcess);

        $this->logger->startLogging();
        $this->logger->info(sprintf('Skipped colums: %s', json_encode(array_values($valueKeysToSkip))));

        $io->title(sprintf('Start importing file "%s" for reference entity "%s"', $filePath, $referenceEntityCode));
        $io->text('Everything will be logged here:');
        $io->text($this->logger->getLogFilePath());
        $io->newLine(2);

        $progressBar = new ProgressBar($output, $reader->count());
        $progressBar->start();

        $recordsToWrite = [];
        $linesToWrite = [];
        foreach ($reader as $lineNumber => $row) {
            if ($lineNumber === 1) continue;

            if (count($headers) !== count($row)) {
                $this->logger->warning(
                    sprintf(
                        'Skipped line %s: the number of values is not equal to the number of headers',
                        $lineNumber
                    )
                );
                $this->logger->numSkipped++;
                $this->invalidFileGenerator->fromRow($row, $filePath, $headers);

                continue;
            }

            $line = array_combine($headers, $row);
            $recordsToWrite[] = $this->processor->process($line, $attributesToProcess, $indexedValueKeysToProcess);
            $linesToWrite[] = $line;

            if (count($recordsToWrite) === $batchSize) {
                $responses = $recordWriter->write($referenceEntityCode, $recordsToWrite);
                $this->logger->logResponses($responses);
                $this->invalidFileGenerator->fromResponses($responses, $linesToWrite, $filePath, $headers);

                $recordsToWrite = [];
                $linesToWrite = [];
                $progressBar->advance($batchSize);
            }
        }

        if (!empty($recordsToWrite)) {
            $responses = $recordWriter->write($referenceEntityCode, $recordsToWrite);
            $this->logger->logResponses($responses);
            $this->invalidFileGenerator->fromResponses($responses, $linesToWrite, $filePath, $headers);

            $progressBar->advance(count($recordsToWrite));
        }

        $progressBar->finish();

        $io->newLine(2);
        $io->success(sprintf(
            'Done (%s created, %s updated, %s skipped)',
            $this->logger->numCreated,
            $this->logger->numUpdated,
            $this->logger->numSkipped
        ));
    }

    private function indexAttributes(array $attributes) {
        return array_reduce($attributes, function ($indexedAttributes, $attribute) {
            $indexedAttributes[$attribute['code']] = $attribute;

            return $indexedAttributes;
        }, []);
    }

    private function flattenValueKeys(array $indexedValueKeysToProcess) {
        return array_reduce($indexedValueKeysToProcess, function (array $valueKeysToProcess, array $indexedValueKeyToProcess) {
            $valueKeysToProcess += $indexedValueKeyToProcess;

            return $valueKeysToProcess;
        }, []);
    }
}
