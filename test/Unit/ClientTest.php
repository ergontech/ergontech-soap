<?php
/**
 * Created by IntelliJ IDEA.
 * User: john
 * Date: 1/31/19
 * Time: 9:33 AM
 */

namespace ErgonTech\Soap\Test\Unit;

use PHPUnit\Framework\TestCase;
use ErgonTech\Soap\Client;

class ClientTest extends TestCase
{
    public $client;

    public function setUp()
    {
        $this->client = new Client(null, ['uri'=>"http://someuri", 'location' => 'http://somelocation']);
    }

    /**
     * @dataProvider getTestNameSpacedXsiObjectXml
     */
    public function testRemoveNameSpacedXsiObjectAttributeValues($requestXml, $expectedResultXml)
    {
        $result = $this->client->removeNameSpacedXsiObjectAttributeValues($requestXml);
        $this->assertXmlStringEqualsXmlString($result, $expectedResultXml);
    }

    public function getTestNameSpacedXsiObjectXml()
    {
        return [
            [
                '<?xml version="1.0"?><root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:test_ns="http://testuri"><test_element xsi:type="test_ns:test_value" /></root>',
                '<?xml version="1.0"?><root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:test_ns="http://testuri"><test_element xmlns="http://testuri" xsi:type="test_value" /></root>'
            ]
        ];
    }


}
