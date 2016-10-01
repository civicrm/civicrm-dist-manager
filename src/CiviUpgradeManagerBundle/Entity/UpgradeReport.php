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


}

