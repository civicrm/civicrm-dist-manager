<?php

namespace CiviDistManagerBundle\Tests\Controller;

use CiviDistManagerBundle\Entity\UpgradeReport;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpgradeReportControllerTest extends WebTestCase {

  public function setUp() {
    parent::setUp();
    $em = $this->createEntityManager();
    $em->getConnection()->executeQuery('DELETE FROM UpgradeReport WHERE siteId LIKE "UpgradeReport%"');
  }

  /**
   * If a required field is omitted, return an error.
   */
  public function testRequired() {
    // Create a new client to browse the application
    $client = static::createClient();
    $siteId = 'UpgradeReportControllerTest';
    $name = md5(rand() . rand() . rand() . uniqid() . time());

    $client->request('POST', '/report', array(
      'name' => $name,
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 400);
    $this->assertEquals('Missing required argument: siteId', $json['message']);

    $client->request('POST', '/report', array(
      'siteId' => $siteId,
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 400);
    $this->assertEquals('Missing required argument: name', $json['message']);
  }

  /**
   * Correctly create an UpgradeReport and view it.
   */
  public function testCompleteScenario() {
    // Create a new client to browse the application
    $client = static::createClient();
    $siteId = 'UpgradeReportControllerTest';
    $name = md5(rand() . rand() . rand() . uniqid() . time());

    $client->request('POST', '/report', array(
      'siteId' => $siteId,
      'name' => $name,
      'revision' => '1.0.2',
      'started' => strtotime('2001-02-03 12:00'),
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 200);
    $this->assertEquals('Saved', $json['message']);

    // Go to the list view
    $crawler = $client->request('GET', '/report/review');
    $this->assertEquals(200, $client->getResponse()->getStatusCode(),
      "Unexpected HTTP status code for GET /UpgradeReport/");
    $this->assertContains($name, $crawler->text());
    $this->assertNotContains($siteId, $crawler->text());

    // Go to the show view
    $crawler = $client->click($crawler->selectLink('show')->link());
    $this->assertEquals(200, $client->getResponse()->getStatusCode(),
      "Unexpected HTTP status code");
    $this->assertContains($name, $crawler->text());
    $this->assertNotContains($siteId, $crawler->text());
    $this->assertContains('2001-02-03', $crawler->text());
  }

  /**
   * Create a record; then fill in an empty property later.
   */
  public function testFillProperty() {
    // Create a new client to browse the application
    $client = static::createClient();
    $siteId = 'UpgradeReportControllerTest';
    $name = md5(rand() . rand() . rand() . uniqid() . time());

    $client->request('POST', '/report', array(
      'siteId' => $siteId,
      'name' => $name,
      'revision' => '1.0.2',
      'started' => strtotime('2001-03-03 12:00'),
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 200);
    $this->assertEquals('Saved', $json['message']);

    /** @var UpgradeReport $upgradeReport */
    $em = $this->createEntityManager();
    $upgradeReport = $em->find(UpgradeReport::class, $name);
    $this->assertEquals(strtotime('2001-03-03 12:00'), $upgradeReport->getStarted()->getTimestamp());
    $this->assertEquals(NULL, $upgradeReport->getFinished());
    $this->assertEquals('downloading', $upgradeReport->getStage());

    $client->request('POST', '/report', array(
      'siteId' => $siteId,
      'name' => $name,
      'finished' => strtotime('2001-03-05 12:00'),
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 200);
    $this->assertEquals('Saved', $json['message']);

    $em->refresh($upgradeReport);
    $this->assertEquals(strtotime('2001-03-03 12:00'), $upgradeReport->getStarted()->getTimestamp());
    $this->assertEquals(strtotime('2001-03-05 12:00'), $upgradeReport->getFinished()->getTimestamp());
    $this->assertEquals('finished', $upgradeReport->getStage());
  }

  /**
   * Attempt to update an existing record -- but this should fail if the
   * update uses the wrong siteId.
   */
  public function testWrongSiteId() {
    // Create a new client to browse the application
    $client = static::createClient();
    $siteId = 'UpgradeReportControllerTest';
    $name = md5(rand() . rand() . rand() . uniqid() . time());

    $client->request('POST', '/report', array(
      'siteId' => $siteId,
      'name' => $name,
      'revision' => '1.0.2',
      'started' => strtotime('2001-03-03 12:00'),
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 200);
    $this->assertEquals('Saved', $json['message']);

    /** @var UpgradeReport $upgradeReport */
    $em = $this->createEntityManager();
    $upgradeReport = $em->find(UpgradeReport::class, $name);
    $this->assertEquals(strtotime('2001-03-03 12:00'), $upgradeReport->getStarted()->getTimestamp());
    $this->assertEquals(NULL, $upgradeReport->getFinished());

    $client->request('POST', '/report', array(
      'siteId' => 'wrongSiteId',
      'name' => $name,
      'finished' => strtotime('2001-03-05 12:00'),
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 400);
    $this->assertEquals('Report already exists. Claimed by different siteId.',
      $json['message']);

    $em->refresh($upgradeReport);
    $this->assertEquals(strtotime('2001-03-03 12:00'), $upgradeReport->getStarted()->getTimestamp());
    $this->assertEquals(NULL, $upgradeReport->getFinished());
  }

  /**
   * Create a record; then fill in an empty property later.
   */
  public function testChangeProhibited() {
    // Create a new client to browse the application
    $client = static::createClient();
    $siteId = 'UpgradeReportControllerTest';
    $name = md5(rand() . rand() . rand() . uniqid() . time());

    $client->request('POST', '/report', array(
      'siteId' => $siteId,
      'name' => $name,
      'revision' => '1.0.2',
      'started' => strtotime('2001-03-03 12:00'),
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 200);
    $this->assertEquals('Saved', $json['message']);

    /** @var UpgradeReport $upgradeReport */
    $em = $this->createEntityManager();
    $upgradeReport = $em->find(UpgradeReport::class, $name);
    $this->assertEquals(strtotime('2001-03-03 12:00'), $upgradeReport->getStarted()->getTimestamp());
    $this->assertEquals(NULL, $upgradeReport->getFinished());
    $this->assertEquals('downloading', $upgradeReport->getStage());

    $client->request('POST', '/report', array(
      'siteId' => $siteId,
      'name' => $name,
      'started' => strtotime('2001-03-05 12:00'),
    ));
    $json = $this->checkJsonResponse($client->getResponse(), 400);
    $this->assertEquals('Field "started" has already been set', $json['message']);

    $em->refresh($upgradeReport);
    $this->assertEquals(strtotime('2001-03-03 12:00'), $upgradeReport->getStarted()->getTimestamp());
  }


  protected function checkJsonResponse(Response $response, $expectCode) {
    $this->assertEquals('application/json',
      $response->headers->get('Content-type'));
    $this->assertEquals($expectCode, $response->getStatusCode(),
      "Unexpected HTTP status code. Content:" . $response->getContent());
    $data = json_decode($response->getContent(), 1);
    return $data;
  }

  /**
   * @return \Doctrine\ORM\EntityManager
   */
  protected function createEntityManager() {
    $client = static::createClient();
    /** @var EntityManager $em */
    $em = $client->getKernel()->getContainer()->get('doctrine')->getManager();
    return $em;
  }

}
