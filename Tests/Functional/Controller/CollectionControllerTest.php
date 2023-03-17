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

namespace Kitodo\Dlf\Tests\Functional\Controller;

use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Controller\CollectionController;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

class CollectionControllerTest extends FunctionalTestCase {

    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/Controller/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Controller/solrcores.xml');

        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $this->solrCoreRepository = $this->initializeRepository(SolrCoreRepository::class, 2);

        $this->setUpSolr();
    }

    private function setUpSolr()
    {
        // Setup Solr only once for all tests in this suite
        static $solr = null;

        if ($solr === null) {
            $coreName = Solr::createCore();
            $solr = Solr::getInstance($coreName);

            $this->importSolrDocuments($solr, __DIR__ . '/../../Fixtures/Controller/documents.solr.json');
        }

        $coreModel = $this->solrCoreRepository->findByUid(4);
        $coreModel->setIndexName($solr->core);
        $this->solrCoreRepository->update($coreModel);
        $this->persistenceManager->persistAll();
    }

    private function setUpRequest($actionName, $argumentName = false, $argumentValue = false): Request
    {
        $request = new Request();
        $request->setControllerActionName($actionName);
        if ($argumentName && $argumentValue) {
            $request->setArgument($argumentName, $argumentValue);
        }
        return $request;
    }

    private function setUpController($settings, $templateHtml = ''): CollectionController
    {
        $view = new StandaloneView();
        $view->setTemplateSource($templateHtml);

        $controller = $this->get(CollectionController::class);
        $viewResolverMock = $this->getMockBuilder( GenericViewResolver::class)
            ->disableOriginalConstructor()->getMock();
        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);
        $controller->injectViewResolver($viewResolverMock);
        $controller->setSettingsForTest($settings);
        return $controller;
    }

    /**
     * @test
     */
    public function canListAction()
    {
        $settings = [
            'solrcore' => 4,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $templateHtml = '<html><f:for each="{collections}" as="item">{item.collection.indexName}</f:for></html>';
        $subject = $this->setUpController($settings, $templateHtml);
        $request = $this->setUpRequest('list', 'id', 1);
        $response = $this->objectManager->get(Response::class);

        $subject->processRequest($request, $response);

        $actual = $response->getContent();
        $expected = '<html>test-collection</html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canListActionForwardToShow()
    {
        $settings = [
            'solrcore' => 4,
            'collections' => '1',
            'randomize' => ''
        ];
        $subject = $this->setUpController($settings);
        $request = $this->setUpRequest('list', 'id', 1);
        $response = $this->objectManager->get(Response::class);

        $this->expectException(StopActionException::class);
        $subject->processRequest($request, $response);
    }

    /**
     * @test
     */
    public function canShowAction()
    {
        $settings = [
            'solrcore' => 4,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $templateHtml = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"><f:for each="{documents.solrResults.documents}" as="page" iteration="docIterator">{page.title},</f:for></html>';

        $subject = $this->setUpController($settings, $templateHtml);
        $request = $this->setUpRequest('show', 'collection', '1');
        $response = $this->objectManager->get(Response::class);

        $subject->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">10 Keyboard pieces - Go. S. 658,Beigefügte Quellenbeschreibung,Beigefügtes Inhaltsverzeichnis,</html>';
        $this->assertEquals($expected, $actual);

    }

    /**
     * @test
     */
    public function canShowSortedAction()
    {
        $settings = [
            'solrcore' => 4,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $subject = $this->setUpController($settings);
        $request = $this->setUpRequest('showSorted');
        $response = $this->objectManager->get(Response::class);

        $this->expectException(StopActionException::class);
        $subject->processRequest($request, $response);
    }
}
