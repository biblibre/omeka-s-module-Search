<?php

namespace Search\Test\Controller\Admin;

use Search\Test\Controller\SearchControllerTestCase;

class IndexControllerTest extends SearchControllerTestCase
{
    public function testIndexAction()
    {
        $this->dispatch('/admin/search');
        $this->assertResponseStatusCode(200);

        $this->assertXpathQueryContentRegex('//table[1]//td[1]', '/TestIndex/');
        $this->assertXpathQueryContentRegex('//table[1]//td[2]', '/TestAdapter/');

        $this->assertXpathQueryContentRegex('//table[2]//td[1]', '/TestPage/');
        $this->assertXpathQueryContentRegex('//table[2]//td[2]', '/test\/search/');
        $this->assertXpathQueryContentRegex('//table[2]//td[3]', '/TestIndex/');
        $this->assertXpathQueryContentRegex('//table[2]//td[4]', '/Standard/');
    }
}
