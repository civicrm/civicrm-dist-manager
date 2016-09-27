<?php

namespace CiviUpgradeManagerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckControllerTest extends WebTestCase {

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
    $this->assertRegex(';^https://dist.civicrm.org/by-hash/.*/civicrm-[\d\.]+-drupal(\-\d+).tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://dist.civicrm.org/by-hash/.*/civicrm-[\d\.]+-drupal6(\-\d+).tar.gz;',
      $data['tar']['Drupal6']);
    $this->assertRegex(';^https://dist.civicrm.org/by-hash/.*/civicrm-[\d\.]+-wordpress(\-\d+).zip;',
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
    $this->assertRegex(';^https://dist.civicrm.org/by-hash/.*/civicrm-[\d\.]+-drupal(\-\d+).tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://dist.civicrm.org/by-hash/.*/civicrm-[\d\.]+-drupal6(\-\d+).tar.gz;',
      $data['tar']['Drupal6']);
    $this->assertRegex(';^https://dist.civicrm.org/by-hash/.*/civicrm-[\d\.]+-wordpress(\-\d+).zip;',
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
    $this->assertRegex(';^https://download.civicrm.org/civicrm-[\d\.]+-drupal.tar.gz;',
      $data['tar']['Drupal']);
    $this->assertRegex(';^https://download.civicrm.org/civicrm-[\d\.]+-drupal6.tar.gz;',
      $data['tar']['Drupal6']);
    $this->assertRegex(';^https://download.civicrm.org/civicrm-[\d\.]+-wordpress.zip;',
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
