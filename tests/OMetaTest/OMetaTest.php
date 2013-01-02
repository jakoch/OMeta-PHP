<?php

namespace OMetaTest;

use OMeta\OMetaBase;

class OMetaBaseTest extends \PHPUnit_Framework_TestCase
{
    public $ometa;

    public function setUp()
    {
        include_once __DIR__ . '/../../src/OMetaBase.php';
        $this->ometa = new OMetaBase();
    }

    public function tearDown()
    {
        unset($ometa);
    }

    public function testMethod_exactly()
    {
        $data = "foo";
        $ometa = $this->ometa->match($data); /* parse */

        list($v, $e) = $ometa->rule_exactly("f");
        $this->assertEquals($v, "f");
        $this->assertEquals($e[0], 0);
    }

    public function testMethod_matchAll()
    {
        $grammar = "
            // a simple recognizer
            ometa L {
              number   = digit+,
              addExpr  = addExpr '+' mulExpr
                       | addExpr '-' mulExpr
                       | mulExpr,
              mulExpr  = mulExpr '*' primExpr
                       | mulExpr '/' primExpr
                       | primExpr,
              primExpr = '(' expr ')'
                       | number,
              expr     = <addExpr>
        }";

        echo $this->ometa->matchAll('6*(4+3)', 'expr');
    }
}
