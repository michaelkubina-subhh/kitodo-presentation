<?php

namespace Kitodo\Dlf\Tests\Functional\Controller;

use Kitodo\Dlf\Common\MyTest;
use Kitodo\Dlf\Controller\ListViewController;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Mvc\Request;
use Kitodo\Dlf\Domain\Repository\MailRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class ListViewControllerTest extends FunctionalTestCase
{
    /**
     * @var MailRepository
     */
    protected MailRepository $mailRepository;

    /**
     * @var Prophet;
     */
    protected Prophet $prophet;

    public function setUp(): void
    {
        parent::setUp();

      //  $this->importDataSet(__DIR__ . '/../../Fixtures/Repository/mail.xml');

   /*     $collectionRepository
             CollectionRepository $collectionRepository
            $metadataRepository
                MetadataRepository $metadataRepository
    */
    }

    /**
     * @test
     * @group action
     */
    public function canMainAction(): void
    {
        $request = new Request();
        $request->setPluginName('ListView');
        $request->setControllerExtensionName('ListViewControllerTest');
        $request->setControllerName('ListView');
        $request->setFormat('html');

        $request->setControllerActionName('main');
        $request->setArgument('searchParameter', 'test');
        $request->setArgument('searchParametefgfr', 'test');

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var  ListViewController $subject */
        $subject = $this->get(ListViewController::class);
        $response =  $objectManager->get(Response::class);

        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

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
            </html>'
        );

        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);

        $subject->injectViewResolver($viewResolverMock);

        // Test run
        $subject->processRequest($request, $response);

        $output =  $response->getContent();
echo $output;
        /*
        https://dlfdemo.ddev.site/?tx_dlf_listview%5Baction%5D=main&tx_dlf_listview%5Bcontroller%5D=ListView&tx_dlf_listview%5BsearchParameter%5D%5Bfulltext%5D=0&tx_dlf_listview%5BsearchParameter%5D%5Border%5D=&tx_dlf_listview%5BsearchParameter%5D%5BorderBy%5D=&tx_dlf_listview%5BsearchParameter%5D%5Bquery%5D=%2A&tx_dlf_listview%5BwidgetPage%5D%5BcurrentPage%5D=1&cHash=37535ceb0e731d1d9ae297eff2451a5c
          */
    }
}
