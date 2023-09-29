<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Command;

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Command\BaseCommand;
use Kitodo\Dlf\Common\Indexer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * CLI Command for re-indexing collections into database and Solr.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ReindexCommand extends BaseCommand
{
    /**
     * Configure the command by defining the name, options and arguments
     *
     * @access public
     *
     * @return void
     */
    public function configure()
    {
        $this
            ->setDescription('Reindex a collection into database and Solr.')
            ->setHelp('')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not actually be processed but the location URI is shown.'
            )
            ->addOption(
                'coll',
                'c',
                InputOption::VALUE_REQUIRED,
                'UID of the collection.'
            )
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'UID of the page the documents should be added to.'
            )
            ->addOption(
                'solr',
                's',
                InputOption::VALUE_REQUIRED,
                '[UID|index_name] of the Solr core the document should be added to.'
            )
            ->addOption(
                'owner',
                'o',
                InputOption::VALUE_OPTIONAL,
                '[UID|index_name] of the Library which should be set as owner of the documents.'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Reindex all documents on the given page.'
            );
    }

    /**
     * Executes the command to index the given document to DB and SOLR.
     *
     * @access protected
     *
     * @param InputInterface $input The input parameters
     * @param OutputInterface $output The Symfony interface for outputs on console
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run') != false ? true : false;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $this->initializeRepositories($input->getOption('pid'));

        if ($this->storagePid == 0) {
            $io->error('ERROR: No valid PID (' . $this->storagePid . ') given.');
            exit(1);
        }

        if (
            !empty($input->getOption('solr'))
            && !is_array($input->getOption('solr'))
        ) {
            $allSolrCores = $this->getSolrCores($this->storagePid);
            $solrCoreUid = $this->getSolrCoreUid($allSolrCores, $input->getOption('solr'));

            // Abort if solrCoreUid is empty or not in the array of allowed solr cores.
            if (empty($solrCoreUid) || !in_array($solrCoreUid, $allSolrCores)) {
                $output_solrCores = [];
                foreach ($allSolrCores as $index_name => $uid) {
                    $output_solrCores[] = $uid . ' : ' . $index_name;
                }
                if (empty($output_solrCores)) {
                    $io->error('ERROR: No valid Solr core ("' . $input->getOption('solr') . '") given. No valid cores found on PID ' . $this->storagePid . ".\n");
                    exit(1);
                } else {
                    $io->error('ERROR: No valid Solr core ("' . $input->getOption('solr') . '") given. ' . "Valid cores are (<uid>:<index_name>):\n" . implode("\n", $output_solrCores) . "\n");
                    exit(1);
                }
            }
        } else {
            $io->error('ERROR: Required parameter --solr|-s is missing or array.');
            exit(1);
        }

        if (!empty($input->getOption('owner'))) {
            if (MathUtility::canBeInterpretedAsInteger($input->getOption('owner'))) {
                $this->owner = $this->libraryRepository->findByUid(MathUtility::forceIntegerInRange((int) $input->getOption('owner'), 1));
            } else {
                $this->owner = $this->libraryRepository->findOneByIndexName((string) $input->getOption('owner'));
            }
        } else {
            $this->owner = null;
        }

        if (!empty($input->getOption('all'))) {
            // Get all documents.
            $documents = $this->documentRepository->findAll();
        } elseif (
            !empty($input->getOption('coll'))
            && !is_array($input->getOption('coll'))
        ) {
            // "coll" may be a single integer or a comma-separated list of integers.
            if (empty(array_filter(GeneralUtility::intExplode(',', $input->getOption('coll'), true)))) {
                $io->error('ERROR: Parameter --coll|-c is not a valid comma-separated list of collection UIDs.');
                exit(1);
            }
            // Get all documents of given collections.
            $documents = $this->documentRepository->findAllByCollectionsLimited(GeneralUtility::intExplode(',', $input->getOption('coll'), true), 0);
        } else {
            $io->error('ERROR: One of parameters --all|-a or --coll|-c must be given.');
            exit(1);
        }

        foreach ($documents as $id => $document) {
            $doc = AbstractDocument::getInstance($document->getLocation(), ['storagePid' => $this->storagePid], true);

            if ($doc === null) {
                $io->warning('WARNING: Document "' . $document->getLocation() . '" could not be loaded. Skip to next document.');
                continue;
            }

            if ($dryRun) {
                $io->writeln('DRY RUN: Would index ' . ($id + 1) . '/' . count($documents) . '  with UID "' . $document->getUid() . '" ("' . $document->getLocation() . '") on PID ' . $this->storagePid . ' and Solr core ' . $solrCoreUid . '.');
            } else {
                if ($io->isVerbose()) {
                    $io->writeln(date('Y-m-d H:i:s') . ' Indexing ' . ($id + 1) . '/' . count($documents) . ' with UID "' . $document->getUid() . '" ("' . $document->getLocation() . '") on PID ' . $this->storagePid . ' and Solr core ' . $solrCoreUid . '.');
                }
                $document->setCurrentDocument($doc);
                // save to database
                $this->saveToDatabase($document);
                // add to index
                Indexer::add($document, $this->documentRepository);
            }
            // Clear document registry to prevent memory exhaustion.
            AbstractDocument::clearRegistry();
        }

        $io->success('All done!');

        return 0;
    }
}
