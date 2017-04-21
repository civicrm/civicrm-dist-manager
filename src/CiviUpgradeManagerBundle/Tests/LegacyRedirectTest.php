<?php

namespace CiviUpgradeManagerBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LegacyRedirectTest extends WebTestCase {

  /**
   * @param $file
   * @param $regex
   * @dataProvider getExamples
   */
  public function testDownload($file, $regex) {
    $client = static::createClient();
    $client->request('GET', $file);
    $response = $client->getResponse();
    $this->assertRegex(';^https://storage.googleapis.com/civicrm;', $response->headers->get('Location'));
    $this->assertRegex($regex, $response->headers->get('Location'));
  }

  public function getExamples() {
    $cases = array();

    $cases[] = array('/civicrm-4.7.alpha1-drupal6.tar.gz', ';civicrm-testing/4.7.alpha1/civicrm-4.7.alpha1-drupal6.tar.gz;');
    $cases[] = array('/civicrm-4.7.10-drupal6.tar.gz', ';civicrm-stable/4.7.10/civicrm-4.7.10-drupal6.tar.gz;');
    $cases[] = array('/civicrm-4.7.10-drupal.tar.gz?src=foobar', ';civicrm-stable/4.7.10/civicrm-4.7.10-drupal.tar.gz;');
    $cases[] = array('/civicrm-4.7.10-backdrop-unstable.tar.gz', ';civicrm-stable/4.7.10/civicrm-4.7.10-backdrop-unstable.tar.gz;');
    $cases[] = array('/civicrm-4.7.22-backdrop.tar.gz', ';civicrm-stable/4.7.22/civicrm-4.7.22-backdrop.tar.gz;');

    return $cases;
  }

  public function assertRegex($pattern, $data) {
    $this->assertTrue((bool) preg_match($pattern, $data),
      "Assert that pattern ($pattern) matches data ($data)");
  }

}
