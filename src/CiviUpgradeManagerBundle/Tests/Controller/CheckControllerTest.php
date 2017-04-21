<?php

namespace CiviUpgradeManagerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckControllerTest extends WebTestCase {

  /**
   * @param $file
   * @param $regex
   * @dataProvider getDownloadExamples
   */
  public function testDownload($file, $regex) {
    $client = static::createClient();
    $client->request('GET', "/download/$file");
    $this->assertRegex(';^https://storage.googleapis.com/civicrm;', $client->getResponse()->headers->get('Location'));
    $this->assertRegex($regex, $client->getResponse()->headers->get('Location'));
  }

  public function getDownloadExamples() {
    $cases = array();
    $cases[0] = array('civicrm-STABLE-drupal.tar.gz', ';/civicrm-stable/civicrm-[\d\.]+-drupal.tar.gz;');
    $cases[1] = array('civicrm-STABLE-joomla.zip', ';/civicrm-stable/civicrm-[\d\.]+-joomla.zip;');
    $cases[2] = array('civicrm-STABLE-wordpress.zip', ';/civicrm-stable/civicrm-[\d\.]+-wordpress.zip;');
    $cases[3] = array('civicrm-STABLE-l10n.tar.gz', ';/civicrm-stable/civicrm-[\d\.]+-l10n.tar.gz;');
    $cases[4] = array('civicrm-NIGHTLY-drupal.tar.gz', ';/civicrm-build/.*/civicrm-[\d\.]+-drupal-\d+.tar.gz;');
    $cases[5] = array('civicrm-NIGHTLY-drupal6.tar.gz', ';/civicrm-build/.*/civicrm-[\d\.]+-drupal6-\d+.tar.gz;');
    $cases[6] = array('civicrm-NIGHTLY-l10n.tar.gz', ';/civicrm-build/.*/civicrm-[\d\.]+-l10n-?\d+.tar.gz;');
    $cases[7] = array('civicrm-RC-wordpress.zip', ';/civicrm-[\d\.]+-wordpress-?\d*.zip;');
    $cases[8] = array('civicrm-RC-joomla.zip', ';/civicrm-[\d\.]+-joomla-?\d*.zip;');
    $cases[9] = array('civicrm-RC-backdrop.tar.gz', ';/civicrm-[\d\.]+-backdrop(-unstable)?-?\d*.tar.gz;');

    return $cases;
  }

  public function testCheckRc() {
    $client = static::createClient();

    $client->request('GET', '/check', array(
      'stability' => 'rc',
    ));
    $response = $client->getResponse();
    $this->assertEquals('application/json',
      $response->headers->get('Content-type'));
    $data = json_decode($response->getContent(), 1);

    $this->assertRegex(';^[a-zA-Z0-9\-\.\+_]+$;', $data['rev']);
    $this->assertRegex(';^https://(download.civicrm.org|storage.googleapis.com)/.*civicrm-[\d\.]+-drupal(\-\d+)?.tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://(download.civicrm.org|storage.googleapis.com)/.*civicrm-[\d\.]+-drupal6(\-\d+)?.tar.gz;',
      $data['tar']['Drupal6']);
    $this->assertRegex(';^https://(download.civicrm.org|storage.googleapis.com)/.*civicrm-[\d\.]+-wordpress(\-\d+)?.zip;',
      $data['tar']['WordPress']);
  }

  public function testCheckNightly() {
    $client = static::createClient();

    $client->request('GET', '/check', array(
      'stability' => 'nightly',
    ));
    $response = $client->getResponse();
    $this->assertEquals('application/json',
      $response->headers->get('Content-type'));
    $data = json_decode($response->getContent(), 1);

    $this->assertRegex(';^[a-zA-Z0-9\-\.\+_]+$;', $data['rev']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm-build/master/civicrm-[\d\.]+-drupal(\-\d+).tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm-build/master/civicrm-[\d\.]+-drupal6(\-\d+).tar.gz;',
      $data['tar']['Drupal6']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm-build/master/civicrm-[\d\.]+-wordpress(\-\d+).zip;',
      $data['tar']['WordPress']);
  }

  public function testCheckStable() {
    $client = static::createClient();

    $client->request('GET', '/check', array(
      'stability' => 'stable',
    ));
    $response = $client->getResponse();
    $this->assertEquals('application/json',
      $response->headers->get('Content-type'));
    $data = json_decode($response->getContent(), 1);

    $this->assertRegex(';^[a-zA-Z0-9\-\.\+_]+$;', $data['rev']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm/civicrm-stable/civicrm-[\d\.]+-drupal.tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm/civicrm-stable/civicrm-[\d\.]+-drupal6.tar.gz;',
      $data['tar']['Drupal6']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm/civicrm-stable/civicrm-[\d\.]+-wordpress.zip;',
      $data['tar']['WordPress']);
  }

  public function testCheckUnknown() {
    $client = static::createClient();

    $client->request('GET', '/check');
    $response = $client->getResponse();
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertEquals('application/json',
      $response->headers->get('Content-type'));
    $data = json_decode($response->getContent(), 1);
    $this->assertTrue(!empty($data['message']));
  }


  public function assertRegex($pattern, $data) {
    $this->assertTrue((bool) preg_match($pattern, $data),
      "Assert that pattern ($pattern) matches data ($data)");
  }

}
