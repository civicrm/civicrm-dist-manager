<?php

namespace CiviUpgradeManagerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckControllerTest extends WebTestCase
{
    public function testCheck()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/check');
    }

}
