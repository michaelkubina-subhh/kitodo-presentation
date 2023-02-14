<?php

namespace Kitodo\Dlf\Tests\Functional\Controller;

use Kitodo\Dlf\Controller\ListViewController;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Mvc\Request;
use Kitodo\Dlf\Domain\Repository\MailRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Fluid\View\TemplateView;

class ListViewControllerTest extends FunctionalTestCase
{
    /**
     * @var MailRepository
     */
    protected $mailRepository;

    public function setUp(): void
    {
        parent::setUp();

        //$this->mailRepository = $this->initializeRepository(
        //    MailRepository::class,
        //    20000
        //);

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

        $subject = $this->get(ListViewController::class);
        $response =  $objectManager->get(Response::class);

        //$GLOBALS['TSFE'] = new \stdClass();


        $view = $this->getMockBuilder(TemplateView::class)->getMock();


        $viewResolverMock = $this->getMockBuilder( GenericViewResolver::class)->disableOriginalConstructor()->getMock();

        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);
        $subject->injectViewResolver($viewResolverMock);

        $view->expects(self::once())->method('assign')->with('viewData','dfgfdg');

$t = new ListViewController;


        // Test run
        $subject->processRequest($request, $response);



        echo $response->getContent();

/*
       tx_dlf_listview%5Baction%5D=main
        &tx_dlf_listview%5Bcontroller%5D=ListView
        &tx_dlf_listview%5BsearchParameter%5D%5Bfulltext%5D=0
        &tx_dlf_listview%5BsearchParameter%5D%5Border%5D=
        &tx_dlf_listview%5BsearchParameter%5D%5BorderBy%5D=
        &tx_dlf_listview%5BsearchParameter%5D%5Bquery%5D=%2A
        &tx_dlf_listview%5BwidgetPage%5D%5BcurrentPage%5D=1
        &cHash=37535ceb0e731d1d9ae297eff2451a5c
*/
        /*

        https://dlfdemo.ddev.site/?tx_dlf_listview%5Baction%5D=main&tx_dlf_listview%5Bcontroller%5D=ListView&tx_dlf_listview%5BsearchParameter%5D%5Bfulltext%5D=0&tx_dlf_listview%5BsearchParameter%5D%5Border%5D=&tx_dlf_listview%5BsearchParameter%5D%5BorderBy%5D=&tx_dlf_listview%5BsearchParameter%5D%5Bquery%5D=%2A&tx_dlf_listview%5BwidgetPage%5D%5BcurrentPage%5D=1&cHash=37535ceb0e731d1d9ae297eff2451a5c


          */


        /*
        $mails = $this->mailRepository->findAllWithPid(30000);
        $this->assertNotNull($mails);
        $this->assertInstanceOf(QueryResult::class, $mails);

        $mailByLabel = [];
        foreach ($mails as $mail) {
            $mailByLabel[$mail->getLabel()] = $mail;
        }

        $this->assertEquals(2, $mails->count());
        $this->assertArrayHasKey('Mail-Label-1', $mailByLabel);
        $this->assertArrayHasKey('Mail-Label-2', $mailByLabel);
        */

        $this->assertTrue(false);

    }
}
