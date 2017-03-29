<?php

namespace PhpPackage;

class DocumentTest extends \PHPUnit_Framework_TestCase
{

    public function testInheritance()
    {
        $document = new Document();
        
        $this->assertInstanceOf("\PhpPackage\Node", $document);
    }
}
