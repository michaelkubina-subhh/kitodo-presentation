<?php

namespace Kitodo\Dlf\Tests\Functional\Controller;

use Kitodo\Dlf\Common\MyTest;
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Controller\ListViewController;
use Kitodo\Dlf\Domain\Model\SolrCore;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Mvc\Request;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class ListViewControllerTest extends FunctionalTestCase
{
    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @var DocumentRepository
     */
    protected $documentRepository;

    /**
     * @var SolrCoreRepository
     */
    protected $solrCoreRepository;

    /**
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @var persistenceManager
     */
    protected $persistenceManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);

        $this->collectionRepository = $this->initializeRepository(CollectionRepository::class, 20000);
        $this->documentRepository = $this->initializeRepository(DocumentRepository::class, 20000);
        $this->solrCoreRepository = $this->initializeRepository(SolrCoreRepository::class, 20000);
        $this->metadataRepository = $this->initializeRepository(MetadataRepository::class, 20000);

        $this->importDataSet(__DIR__ . '/../../Fixtures/Controller/documents_1.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Controller/metadata.xml');
    }

    /**
     * @test
     * @group action
     */
    public function canMainAction(): void
    {
        $core = $this->createSolrCore();
        $this->importSolrDocuments($core->solr, __DIR__ . '/../../Fixtures/Common/documents_1.solr.json');

        // TODO: Must be available inside the main action $this->settings
        $settings = [
            'solrcore' => $core->solr->core,
            'storagePid' => 20000,
        ];

        $request = new Request();
        $request->setPluginName('ListView');
        $request->setControllerExtensionName('ListViewControllerTest');
        $request->setControllerName('ListView');
        $request->setFormat('html');
        $request->setControllerExtensionName('dlf');

        $request->setControllerActionName('main');
        /* TODO: $request->setArgument('tx_dlf', ['double' => 10, 'page' => 1234]); */
        /* TODO: $request->setArgument('page', 1234); */
        // $request->setArgument('searchParameter', ['query' => '*', 'collection' => '1101']);
        $request->setArgument('searchParameter', ['query' => '*']);

        $request->setArgument('widgetPage', ['currentPage' => 1]);
        // Or is it like this: $request->setArgument('@widget_0', 1);

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var  ListViewController $subject */
        $subject = $this->get(ListViewController::class);
        $response =  $objectManager->get(Response::class);

        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();
        $GLOBALS['TSFE']->id = 1234; // TODO: Value not available in controller action.

        $viewResolverMock = $this->getMockBuilder( GenericViewResolver::class)->disableOriginalConstructor()->getMock();
        $view = new StandaloneView();
        $view->setTemplateSource(
            '<html xmlns:v="http://typo3.org/ns/FluidTYPO3/Vhs/ViewHelpers">
                <f:spaceless>
                pageUid: {viewData.pageUid}
                uniqueId-length: <v:count.bytes>{viewData.uniqueId}</v:count.bytes>
                page: {viewData.requestData.page}
                double: {viewData.requestData.double}
                </f:spaceless>

                widgetPage: {widgetPage}

                lastSearch.query: {lastSearch.query}

                sortableMetadata:
                <f:for each="{sortableMetadata}" as="sMetadata">
                    {sMetadata.uid}/{sMetadata.label}
                </f:for>

                listedMetadata:
                <f:for each="{listedMetadata}" as="lMetadata">
                    {lMetadata.uid}/{lMetadata.label}
                </f:for>

                <f:for each="{documents}" as="paginatedDocuments">
                  <f:for each="{paginatedDocuments}" as="document" iteration="docIterator">
                    <f:variable name="docTitle" value="{f:if(condition:\'{document.title}\', then:\'{document.title}\', else:\'{document.metsOrderlabel}\')}" />
                    document: {docTitle}
                  </f:for>
                </f:for>

                numResults: {numResults}

            </html>'
        );

        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);

        $subject->injectViewResolver($viewResolverMock);

        // Test run
        $subject->processRequest($request, $response);

        $output =  $response->getContent();

        echo $output;

        // TODO: assertions
    }

    protected function createSolrCore(): object
    {
        $coreName = Solr::createCore();
        $solr = Solr::getInstance($coreName);

        $model = GeneralUtility::makeInstance(SolrCore::class);
        $model->setLabel('Testing Solr Core');
        $model->setIndexName($coreName);
        $this->solrCoreRepository->add($model);
        $this->persistenceManager->persistAll();

        return (object) compact('solr', 'model');
    }
}
