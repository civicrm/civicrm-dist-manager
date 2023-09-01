<?php

namespace CiviDistManagerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AboutControllerTest extends WebTestCase {

  /**
   * @param $version
   * @dataProvider getGoodVersions
   */
  public function testGetWellformedResponses($version) {
    $client = static::createClient();
    $client->request('GET', '/release/' . $version, array());
    $this->assertFalse($client->getResponse()->isRedirect());
    $this->assertRegexp(";$version;", $client->getResponse()->getContent());
  }

  public function getGoodVersions() {
    return [
      ['4.7.25'],
      ['4.7.31'],
      ['5.0.0'],
    ];
  }

  /**
   * @param $version
   * @dataProvider getBadVersions
   */
  public function testGetBadResponses($version) {
    $client = static::createClient();
    $client->request('GET', '/release/' . $version, array());
    $this->assertTrue($client->getResponse()->getStatusCode() >= 400);
  }

  public function getBadVersions() {
    return [
      ['4.7.9999'],
      ['+4.7.25'],
    ];
  }

}
