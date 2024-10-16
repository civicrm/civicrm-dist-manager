<?php

namespace CiviDistManagerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckControllerTest extends WebTestCase {

  public function testDownloadRedirect() {
    $client = static::createClient();
    $client->request('GET', '/latest', array());
    $this->assertRegex(';/latest/$;',
      $client->getResponse()->headers->get('Location'));
  }

  public function testDownloadList() {
    $client = static::createClient();
    $client->request('GET', '/latest/', array());
    $this->assertRegex(';\<a href="[^"]*civicrm-NIGHTLY-drupal.tar.gz"\>;',
      $client->getResponse()->getContent());
  }

  /**
   * @param $file
   * @param $regex
   * @dataProvider getDownloadExamples
   */
  public function testDownload($file, $regex) {
    $client = static::createClient();
    $client->request('GET', "/latest/$file");
    $this->assertRegex(';^https://storage.googleapis.com/civicrm;', $client->getResponse()->headers->get('Location'));
    $this->assertRegex($regex, $client->getResponse()->headers->get('Location'));
  }

  public function getDownloadExamples() {
    $cases = array();
    $cases[0] = array('civicrm-STABLE-drupal.tar.gz', ';/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-drupal.tar.gz;');
    $cases[1] = array('civicrm-STABLE-joomla.zip', ';/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-joomla.zip;');
    $cases[2] = array('civicrm-STABLE-wordpress.zip', ';/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-wordpress.zip;');
    $cases[3] = array('civicrm-STABLE-l10n.tar.gz', ';/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-l10n.tar.gz;');
    $cases[4] = array('civicrm-NIGHTLY-drupal.tar.gz', ';/civicrm-build/.*/civicrm-(\d|\.|alpha|beta)+-drupal-\d+.tar.gz;');
    $cases[5] = array('civicrm-STABLE-backdrop.tar.gz', ';/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-backdrop.tar.gz;');
    $cases[6] = array('civicrm-NIGHTLY-l10n.tar.gz', ';/civicrm-build/.*/civicrm-(\d|\.|alpha|beta)+-l10n-?\d+.tar.gz;');
    $cases[7] = array('civicrm-RC-wordpress.zip', ';/civicrm-(\d|\.|alpha|beta)+-wordpress-?\d*.zip;');
    $cases[8] = array('civicrm-RC-joomla.zip', ';/civicrm-(\d|\.|alpha|beta)+-joomla-?\d*.zip;');
    $cases[9] = array('civicrm-RC-backdrop.tar.gz', ';/civicrm-(\d|\.|alpha|beta)+-backdrop-\d*.tar.gz;');
    $cases[10] = array('civicrm-RC-joomla5.zip', ';/civicrm-(\d|\.|alpha|beta)+-joomla5-?\d*.zip;');
    // $cases[10] = array('civicrm-46NIGHTLY-drupal.tar.gz', ';/civicrm-build/4.6/civicrm-[\d\.]+-drupal-\d+.tar.gz;');

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
    $this->assertRegex(';^https://(download.civicrm.org|storage.googleapis.com)/.*civicrm-(\d|\.|alpha|beta)+-drupal(\-\d+)?.tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://(download.civicrm.org|storage.googleapis.com)/.*civicrm-(\d|\.|alpha|beta)+-backdrop(\-\d+)?.tar.gz;',
      $data['tar']['Backdrop']);
    $this->assertRegex(';^https://(download.civicrm.org|storage.googleapis.com)/.*civicrm-(\d|\.|alpha|beta)+-wordpress(\-\d+)?.zip;',
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
    $this->assertRegex(';^https://storage.googleapis.com/civicrm-build/master/civicrm-(\d|\.|alpha|beta)+-drupal(\-\d+).tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm-build/master/civicrm-(\d|\.|alpha|beta)+-backdrop(\-\d+).tar.gz;',
      $data['tar']['Backdrop']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm-build/master/civicrm-(\d|\.|alpha|beta)+-wordpress(\-\d+).zip;',
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
    $this->assertRegex(';^https://storage.googleapis.com/civicrm/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-drupal.tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-backdrop.tar.gz;',
      $data['tar']['Backdrop']);
    $this->assertRegex(';^https://storage.googleapis.com/civicrm/civicrm-stable/[\d\.]+/civicrm-[\d\.]+-wordpress.zip;',
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
