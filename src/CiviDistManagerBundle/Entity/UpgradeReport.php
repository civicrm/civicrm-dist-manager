<?php

namespace CiviDistManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UpgradeReport
 *
 * @ORM\Table(name="UpgradeReport", indexes={@ORM\Index(name="revision_idx", columns={"revision", "status"})})
 * @ORM\Entity
 */
class UpgradeReport {

  /**
   * @var string
   *
   * @ORM\Column(name="name", type="string", length=64, nullable=false)
   * @ORM\Id
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
  private $downloadUrl;

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
  private $startReport;

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
  private $upgradeReport;

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
  private $finishReport;

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
  public function getDownloadUrl() {
    return $this->downloadUrl;
  }

  /**
   * @param string $downloadUrl
   */
  public function setDownloadUrl($downloadUrl) {
    $this->downloadUrl = $downloadUrl;
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
  public function getStartReport() {
    return $this->startReport;
  }

  /**
   * @param string $startReport
   */
  public function setStartReport($startReport) {
    $this->startReport = $startReport;
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
  public function getUpgradeReport() {
    return $this->upgradeReport;
  }

  /**
   * @param string $upgradeReport
   */
  public function setUpgradeReport($upgradeReport) {
    $this->upgradeReport = $upgradeReport;
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
  public function getFinishReport() {
    return $this->finishReport;
  }

  /**
   * @param string $finishReport
   */
  public function setFinishReport($finishReport) {
    $this->finishReport = $finishReport;
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

  public function get($field) {
    $getter = 'get' . strtoupper($field{0}) . substr($field, 1);
    return call_user_func([$this, $getter]);
  }

  public function set($field, $value) {
    $setter = 'set' . strtoupper($field{0}) . substr($field, 1);
    return call_user_func([$this, $setter], $value);
  }

  /**
   * Update computed fields, stage and status.
   */
  public function updateComputedFields() {
    $this->setStage($this->pickStage());
    $this->setStatus($this->pickStatus());
  }

  public function pickStage() {
    // started, downloading, extracting, upgrading, finished
    if ($this->getProblem()) {
      return 'failed';
    }
    if ($this->getFinished()) {
      return 'finished';
    }
    if ($this->getUpgraded()) {
      return 'finishing';
    }
    if ($this->getExtracted()) {
      return 'upgrading';
    }
    if ($this->getDownloaded()) {
      return 'extracting';
    }
    if ($this->getStarted()) {
      return 'downloading';
    }
    return 'starting';
  }

  public function pickStatus() {
    $stage = $this->pickStage();
    switch ($stage) {
      case 'failed':
      case 'finished':
        return $stage;

      default:
        return 'running';
    }
  }

}
