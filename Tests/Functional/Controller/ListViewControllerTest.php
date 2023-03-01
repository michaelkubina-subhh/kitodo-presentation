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

use Kitodo\Dlf\Controller\ListViewController;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class ListViewControllerTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

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

        /** @var  ListViewController $subject */
        $subject = $this->get(ListViewController::class);
        $response =  $this->objectManager->get(Response::class);

        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

        $view = new StandaloneView();
        $view->setTemplateSource(
            '<html xmlns:v="http://typo3.org/ns/FluidTYPO3/Vhs/ViewHelpers">
                <f:spaceless>
                uniqueId-length: <v:count.bytes>{viewData.uniqueId}</v:count.bytes>
                page: {viewData.requestData.page}
                double: {viewData.requestData.double}
                widgetPage: {widgetPage.currentPage}
                lastSearch.query: {lastSearch}
                numResults: {numResults}
                </f:spaceless>
            </html>'
        );
        $viewResolverMock = $this->getMockBuilder( GenericViewResolver::class)->disableOriginalConstructor()->getMock();
        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);
        $subject->injectViewResolver($viewResolverMock);

        // Test run
        $subject->processRequest($request, $response);
        $actual =  $response->getContent();
        $expected = '<html xmlns:v="http://typo3.org/ns/FluidTYPO3/Vhs/ViewHelpers">
                uniqueId-length: 13
                page: 1
                double: 0
                widgetPage: 1
                lastSearch.query: test
                numResults: 0
            </html>';
        $this->assertEquals($expected, $actual);
    }
}
