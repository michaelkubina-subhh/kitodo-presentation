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

use Kitodo\Dlf\Controller\PageViewController;

class PageViewControllerTest extends AbstractControllerTest
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
        $templateHtml = '<html>
                docId:{docId}
                page:{page}
                images:<f:for each="{images}" as="image">
                    {image.url}
                    {image.mimetype}</f:for>
                viewerConfiguration:{viewerConfiguration}
            </html>';
        $controller = $this->setUpController(PageViewController::class, ['solrcore' => 4], $templateHtml);
        $arguments = [
            'id' => 1001,
            'page' => 12
        ];
        $request = $this->setUpRequest('main', $arguments);
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
                docId:1001
                page:12
                images:
                    https://digital.slub-dresden.de/data/kitodo/10Kepi_476251419/10Kepi_476251419_tif/jpegs/00000012.tif.large.jpg
                    image/jpeg
                viewerConfiguration:$(document).ready(function() {
                if (dlfUtils.exists(dlfViewer)) {
                    tx_dlf_viewer = new dlfViewer({
                        controls: [&quot;&quot;],
                        div: &quot;&quot;,
                        progressElementId: &quot;&quot;,
                        images: [{&quot;url&quot;:&quot;https:\/\/digital.slub-dresden.de\/data\/kitodo\/10Kepi_476251419\/10Kepi_476251419_tif\/jpegs\/00000012.tif.large.jpg&quot;,&quot;mimetype&quot;:&quot;image\/jpeg&quot;}],
                        fulltexts: [[]],
                        annotationContainers: [[]],
                        useInternalProxy: 0
                    });
                }
            });
            </html>';
        $this->assertEquals($expected, $actual);
    }
}
