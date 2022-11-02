<?php

namespace Kitodo\Dlf\Tests\Unit\Common;

use Kitodo\Dlf\Common\Helper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HelperTest extends UnitTestCase
{
    public function assertInvalidXml($xml)
    {
        $result = Helper::getXmlFileAsString($xml);
        $this->assertEquals(false, $result);
    }

    /**
     * @test
     * @group getXmlFileAsString
     */
    public function invalidXmlYieldsFalse(): void
    {
        $this->assertInvalidXml(false);
        $this->assertInvalidXml(null);
        $this->assertInvalidXml(1);
        $this->assertInvalidXml([]);
        $this->assertInvalidXml(new \stdClass());
        $this->assertInvalidXml('');
        $this->assertInvalidXml('not xml');
        $this->assertInvalidXml('<tag-not-closed>');
    }

    /**
     * @test
     * @group getXmlFileAsString
     */
    public function validXmlIsAccepted(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <single />
</root>
XML;
        $node = Helper::getXmlFileAsString($xml);
        $this->assertIsObject($node);
        $this->assertEquals('root', $node->getName());
    }

    /**
     * @test
     * @group checkIdentifier
     */
    public function checkIdentifierWithEachType(): void
    {
        $this->assertEquals(true, Helper::checkIdentifier('118512676', 'PPN'));
        $this->assertEquals(true, Helper::checkIdentifier('118512676', 'IDN'));
        $this->assertEquals(true, Helper::checkIdentifier('118512676', 'PND'));
        $this->assertEquals(true, Helper::checkIdentifier('50102311-9', 'ZDB'));
        $this->assertEquals(true, Helper::checkIdentifier('50102311-2', 'SWD'));
        $this->assertEquals(true, Helper::checkIdentifier('21448281-9', 'GKD'));

    }

    /**
     * @test
     *
     */
    public function digestCheck(): void
    {
        $this->assertEquals('1075cb0a57915c075220e01a9cb677698804e6cfc0ff579f6674d31dd4d12c8e', Helper::digest('BeispielText'));
    }

    /**
     * @test
     */
    public function getCleanStringCheck(): void
    {
        $this->assertEquals('lower', Helper::getCleanString('LOWER'));
        $this->assertEquals('elbe', Helper::getCleanString('$€§elb()()+e'));
        $this->assertEquals('-text-', Helper::getCleanString('// text //'));
    }

    /**
     * @test
     */
    public function getURNCheckIfURNIsValid(): void
    {
        $this->assertEquals('urn:nbn:de:0008-20120314013', Helper::getURN('urn:nbn:de:', '0008-2012031401'));
    }

    /**
     * @test
     */
    public function isValidHttpUrl(): void
    {
        $this->assertTrue(Helper::isValidHttpUrl('https://presentation-demo.kitodo.org/'));
        $this->assertFalse(Helper::isValidHttpUrl('sptth://@presentation-demo.kitodo.org/'));
    }


}
