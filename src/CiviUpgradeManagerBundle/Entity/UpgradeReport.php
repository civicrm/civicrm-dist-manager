<?php

namespace CiviUpgradeManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UpgradeReport
 *
 * @ORM\Table(name="UpgradeReport", indexes={@ORM\Index(name="revision_idx", columns={"revision", "status"})})
 * @ORM\Entity
 */
class UpgradeReport
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="siteId", type="string", length=64, nullable=false)
     */
    private $siteId;

    /**
     * @var string
     *
     * @ORM\Column(name="revision", type="string", length=64, nullable=false)
     */
    private $revision;

    /**
     * @var string
     *
     * @ORM\Column(name="reporter", type="string", length=255, nullable=true)
     */
    private $reporter;

    /**
     * @var string
     *
     * @ORM\Column(name="downloadUrl", type="string", length=255, nullable=true)
     */
    private $downloadurl;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=16, nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="stage", type="string", length=16, nullable=true)
     */
    private $stage;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started", type="datetime", nullable=true)
     */
    private $started;

    /**
     * @var string
     *
     * @ORM\Column(name="startReport", type="text", length=65535, nullable=true)
     */
    private $startreport;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="downloaded", type="datetime", nullable=true)
     */
    private $downloaded;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="extracted", type="datetime", nullable=true)
     */
    private $extracted;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upgraded", type="datetime", nullable=true)
     */
    private $upgraded;

    /**
     * @var string
     *
     * @ORM\Column(name="upgradeReport", type="text", length=65535, nullable=true)
     */
    private $upgradereport;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finished", type="datetime", nullable=true)
     */
    private $finished;

    /**
     * @var string
     *
     * @ORM\Column(name="finishReport", type="text", length=65535, nullable=true)
     */
    private $finishreport;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="failed", type="datetime", nullable=true)
     */
    private $failed;

    /**
     * @var string
     *
     * @ORM\Column(name="problem", type="text", length=65535, nullable=true)
     */
    private $problem;

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getSiteId() {
    return $this->siteId;
  }

  /**
   * @param string $siteId
   */
  public function setSiteId($siteId) {
    $this->siteId = $siteId;
  }

  /**
   * @return string
   */
  public function getRevision() {
    return $this->revision;
  }

  /**
   * @param string $revision
   */
  public function setRevision($revision) {
    $this->revision = $revision;
  }

  /**
   * @return string
   */
  public function getReporter() {
    return $this->reporter;
  }

  /**
   * @param string $reporter
   */
  public function setReporter($reporter) {
    $this->reporter = $reporter;
  }

  /**
   * @return string
   */
  public function getDownloadurl() {
    return $this->downloadurl;
  }

  /**
   * @param string $downloadurl
   */
  public function setDownloadurl($downloadurl) {
    $this->downloadurl = $downloadurl;
  }

  /**
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @param string $status
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @return string
   */
  public function getStage() {
    return $this->stage;
  }

  /**
   * @param string $stage
   */
  public function setStage($stage) {
    $this->stage = $stage;
  }

  /**
   * @return \DateTime
   */
  public function getStarted() {
    return $this->started;
  }

  /**
   * @param \DateTime $started
   */
  public function setStarted($started) {
    $this->started = $started;
  }

  /**
   * @return string
   */
  public function getStartreport() {
    return $this->startreport;
  }

  /**
   * @param string $startreport
   */
  public function setStartreport($startreport) {
    $this->startreport = $startreport;
  }

  /**
   * @return \DateTime
   */
  public function getDownloaded() {
    return $this->downloaded;
  }

  /**
   * @param \DateTime $downloaded
   */
  public function setDownloaded($downloaded) {
    $this->downloaded = $downloaded;
  }

  /**
   * @return \DateTime
   */
  public function getExtracted() {
    return $this->extracted;
  }

  /**
   * @param \DateTime $extracted
   */
  public function setExtracted($extracted) {
    $this->extracted = $extracted;
  }

  /**
   * @return \DateTime
   */
  public function getUpgraded() {
    return $this->upgraded;
  }

  /**
   * @param \DateTime $upgraded
   */
  public function setUpgraded($upgraded) {
    $this->upgraded = $upgraded;
  }

  /**
   * @return string
   */
  public function getUpgradereport() {
    return $this->upgradereport;
  }

  /**
   * @param string $upgradereport
   */
  public function setUpgradereport($upgradereport) {
    $this->upgradereport = $upgradereport;
  }

  /**
   * @return \DateTime
   */
  public function getFinished() {
    return $this->finished;
  }

  /**
   * @param \DateTime $finished
   */
  public function setFinished($finished) {
    $this->finished = $finished;
  }

  /**
   * @return string
   */
  public function getFinishreport() {
    return $this->finishreport;
  }

  /**
   * @param string $finishreport
   */
  public function setFinishreport($finishreport) {
    $this->finishreport = $finishreport;
  }

  /**
   * @return \DateTime
   */
  public function getFailed() {
    return $this->failed;
  }

  /**
   * @param \DateTime $failed
   */
  public function setFailed($failed) {
    $this->failed = $failed;
  }

  /**
   * @return string
   */
  public function getProblem() {
    return $this->problem;
  }

  /**
   * @param string $problem
   */
  public function setProblem($problem) {
    $this->problem = $problem;
  }

}

