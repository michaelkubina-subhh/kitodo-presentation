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

use Kitodo\Dlf\Controller\PageGridController;

class PageGridControllerTest extends AbstractControllerTest
{
    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.xml',
        __DIR__ . '/../../Fixtures/Controller/pages.xml',
        __DIR__ . '/../../Fixtures/Controller/solrcores.xml'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
    }

    /**
     * @test
     */
    public function canMainAction()
    {
        $arguments = [
            'id' => 1001
        ];
        $settings = [];
        $templateHtml = '<html>
            pageGridEntries:<f:count subject="{pageGridEntries}"/>
            pageGridEntries[1]:{pageGridEntries.1.pagination}, {pageGridEntries.1.thumbnail}
            pageGridEntries[66]:{pageGridEntries.66.pagination}, {pageGridEntries.66.thumbnail}
            docUid:{docUid}
        </html>';
        $controller = $this->setUpController(PageGridController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main', $arguments);
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
            pageGridEntries:76
            pageGridEntries[1]: - , https://digital.slub-dresden.de/data/kitodo/10Kepi_476251419/10Kepi_476251419_tif/jpegs/00000002.tif.thumbnail.jpg
            pageGridEntries[66]:65, https://digital.slub-dresden.de/data/kitodo/10Kepi_476251419/10Kepi_476251419_tif/jpegs/00000067.tif.thumbnail.jpg
            docUid:1001
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
