<?php

namespace PhpcTest;

use Phpc\OMeta;

class OmetaTest extends \PHPUnit_Framework_TestCase
{
    public $ometa;

    public function setUp()
    {
        $this->ometa = new OMeta();
    }

    public function tearDown()
    {
        unset($ometa);
    }

    public function testMethod_exactly()
    {
        $data = "foo";
        $ometa = $this->ometa->parse($data);

        list($v, $e) = $ometa->rule_exactly("f");
        $this->assertEquals($v, "f");
        $this->assertEquals($e[0], 0);
    }
}
