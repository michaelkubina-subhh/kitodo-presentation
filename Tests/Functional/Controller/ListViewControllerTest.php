<?php

namespace Kitodo\Dlf\Tests\Functional\Controller;

use Kitodo\Dlf\Common\MyTest;
use Kitodo\Dlf\Controller\ListViewController;
use Prophecy\Argument;
use Prophecy\Exception\Prediction\PredictionException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Mvc\Request;
use Kitodo\Dlf\Domain\Repository\MailRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Fluid\View\TemplateView;

class ListViewControllerTest extends FunctionalTestCase
{
  //  use ProphecyTrait;

    /**
     * @var MailRepository
     */
    protected $mailRepository;

    /**
     * @var Prophet;
     */
    protected Prophet $prophet;

    public function setUp(): void
    {
        parent::setUp();

        $this->prophet =  new Prophet;
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


        //$p = new Prophet();

        $view = $this->getMockBuilder(TemplateView::class)->getMock();
        ////$view = $this->prophesize(TemplateView::class);
        //GeneralUtility::addInstance(TemplateView::class, $view->reveal());

        $viewResolverMock = $this->getMockBuilder( GenericViewResolver::class)->disableOriginalConstructor()->getMock();
        //$viewResolverMock = $this->prophesize();
        //$viewResolverMock->willImplement(ViewResolverInterface::class);
        //GeneralUtility::addInstance(ViewResolverInterface::class, $viewResolverMock->reveal());



      //  $t = $this->prophesize(MyTest::class);
        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);
        //$viewResolverMock->resolve()->shouldBeCalled()->willReturn($view->reveal());

       $subject->injectViewResolver($viewResolverMock);


       $view->expects(self::atLeastOnce())->method('canRender');
       $view->expects(self::atLeastOnce())->method('setControllerContext');
       $view->expects(self::atLeastOnce())->method('initializeView');


       $view->expects(self::once())->method('assign')->with('settings');
        $view->expects(self::once())->method('assign')->with('viewData');
        $view->expects(self::once())->method('assign')->with('documents');
        $view->expects(self::once())->method('assign')->with('numResults');
        $view->expects(self::once())->method('assign')->with('widgetPage');

        $view->expects(self::once())->method('assign')->with('lastSearch');
        $view->expects(self::once())->method('assign')->with('sortableMetadata');

        $view->expects(self::once())->method('assign')->with('listedMetadata');


        $view->expects(self::atLeastOnce())->method('render');
        $view->expects(self::atLeastOnce())->method('renderSection');


       // $view->expects(self::atLeast(1))->method('assign')->with('viewDatas', []);
       // $view->expects(self::exactly(1))->method('assign')->with('viewData');

      //  $view->expects(self::once())->method('assign')->with('documents', $solrResults);
      //  $view->expects(self::once())->method('assign')->with('numResults', $numResults);
      //  $view->expects(self::once())->method('assign')->with('widgetPage', $widgetPage);
      //  $view->expects(self::once())->method('assign')->with('lastSearch', 'QWEWE');
      //  $view->expects(self::once())->method('assign')->with('sortableMetadata', $sortableMetadata);
      //  $view->expects(self::once())->method('assign')->with('listedMetadata', $listedMetadata);

//$t->assign()->shouldBeCalled();

/*
        $view->canRender(Argument::any())->shouldBeCalled();
        $view->setControllerContext(Argument::any())->shouldBeCalled();
        $view->initializeView()->shouldBeCalled();
        $view->assign('settings', Argument::any())->shouldBeCalled();
       // $view->assign('viewData', ["pageUid" => null, "uniqueId" => Argument::any(), "requestData" => ["page" => 1, "double" => 0]])->shouldBeCalled();


        $view->assign('viewData', Argument::any())->shouldNotBeCalled();
        $view->assign('documents', Argument::any())->shouldBeCalled();
        $view->assign('numResults', Argument::any())->shouldBeCalled();
        $view->assign('widgetPage',Argument::any())->shouldBeCalled();
        $view->assign('lastSearch', Argument::any())->shouldBeCalled();

        $view->assign('sortableMetadata', Argument::any())->shouldBeCalled();
        $view->assign('listedMetadata', Argument::any())->shouldBeCalled();

        $view->render()->shouldBeCalled();
        $view->renderSection(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();
*/
        // Test run
        $subject->processRequest($request, $response);



        //echo $response->getContent();

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

        //$this->assertTrue(false);

    }
}
