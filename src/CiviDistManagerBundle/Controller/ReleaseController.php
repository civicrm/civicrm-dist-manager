<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\CacheTrait;
use CiviDistManagerBundle\GitBrowsers;
use CiviDistManagerBundle\VersionUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Landing page for information about a particular version of CiviCRM.
 */
class ReleaseController extends Controller {

  use CacheTrait;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct($container) {
    $this->setContainer($container);
  }

  public function listAction(Request $request) {
    return $this->render('CiviDistManagerBundle:About:list.html.twig', [
      'breadcrumbs' => [
        ['title' => 'CiviCRM Home', 'url' => 'https://civicrm.org/'],
        ['title' => 'Download', 'url' => 'https://civicrm.org/download'],
        ['title' => 'All Releases'],
      ],
      'versions' => array_reverse($this->getGroupedVersions()),
      'prototype' => $request->get('prototype'),
    ]);
  }

  /**
   * Landing page for information about a particular version of CiviCRM.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $version
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function viewAction(Request $request, $version) {
    if (!VersionUtil::isWellFormed($version)) {
      throw $this->createNotFoundException("Invalid version");
    }

    // If requested with "?prototype=1", then show the draft implementation of UI.
    // if ($request->get('prototype')) {
    $releaseFiles = $this->getReleaseFiles($version);
    if (!empty($releaseFiles)) {
      return $this->render('CiviDistManagerBundle:About:about.html.twig', [
        'breadcrumbs' => [
          ['title' => 'CiviCRM Home', 'url' => 'https://civicrm.org/'],
          ['title' => 'Download', 'url' => 'https://civicrm.org/download'],
          ['title' => 'All Releases', 'url' => '/release'],
          ['title' => $version],
        ],
        'version' => $version,
        'files' => $releaseFiles,
        'notes' => $this->getReleaseNotes($version),
        'jsonDef' => $this->getReleaseJson($version),
        'gitBrowsers' => GitBrowsers::getAll(),
        'prototype' => $request->get('prototype'),
      ]);
    }
    else {
      $response = $this->render('CiviDistManagerBundle:About:unknown.html.twig', array(
        'version' => $version,
      ));
      $response->setStatusCode(404);
      return $response;
    }
  }

  /**
   * @param string $version
   *   Ex: '5.0.1'.
   * @return string|NULL
   */
  protected function pickRedirectUrl($version) {
    $cache = $this->getCache();
    $cacheId = md5('exists' . $version);

    if (!$cache->contains($cacheId)) {
      $url = NULL;

      // Preferred: `5.0.1` ==> `5.0/release-notes/5.0.1.md`
      // Fallbacks: `master/release-notes/5.0.1.md`, `5.0.1/release-notes/5.0.1.md`
      // Fallbacks: `master/release-notes/5.0.0.md`, `5.0.1/release-notes/5.0.0.md`

      $patchVers = [VersionUtil::getPatch($version), VersionUtil::getMinor($version) . '.0'];
      $branchNames = [VersionUtil::getMinor($version), 'master', VersionUtil::getMinor($version)];
      foreach ($patchVers as $patchVer) {
        foreach ($branchNames as $branch) {
          $candidate = sprintf('https://github.com/civicrm/civicrm-core/blob/%s/release-notes/%s.md', $branch, $patchVer);
          if ($this->fileExistsInHttp($candidate)) {
            $url = $candidate;
            break 2;
          }
        }
      }

      $cache->save($cacheId, $url, $this->standardTtl);
    }
    return $cache->fetch($cacheId);
  }

  protected function fileExistsInHttp($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $exists = $code >= 200 && $code < 300;
    return $exists;
  }

  protected function getReleaseNotes($version) {
    if (!VersionUtil::isWellFormed($version)) {
      throw $this->createNotFoundException("Invalid version");
    }

    return static::cache("releaseNotes-$version", function () use ($version) {
      $opts = [
        'http' => [
          'method' => 'GET',
          'follow_location' => 1,
          'max_redirects' => 10,
        ],
      ];
      $mdUrl = sprintf('https://github.com/civicrm/civicrm-core/raw/%s/release-notes/%s.md',
        VersionUtil::getMinor($version), VersionUtil::getPatch($version));
      $md = file_get_contents($mdUrl, FALSE, stream_context_create($opts));
      if (empty($md)) {
        throw new \Exception("Failed to read $mdUrl");
      }
      return $md;
    }, "Failed to load release notes for <code>{$version}</code>.");
  }

  /**
   * @param string $version
   *
   * @return string[]
   *   List of file names and their URLs.
   *   Ex: [['basename' => 'civicrm-1.2.3-drupal.tar.gz', 'url' => 'https://....']]
   */
  protected function getReleaseFiles($version) {
    if (!VersionUtil::isWellFormed($version)) {
      throw $this->createNotFoundException("Invalid version");
    }

    // $files = static::cache(NULL, function() use ($version) {
    $files = static::cache("files-$version", function() use ($version) {
      /** @var \CiviDistManagerBundle\GStorageUrlFacade $gsu */
      $gsu = $this->container->get('gsu');
      $gsFiles = $gsu->getFiles('gs://civicrm/civicrm-stable/' . $version . '/');
      $fmtFiles = [];
      foreach ($gsFiles as $gsFile) {
        $fmtFiles[] = basename($gsFile);
      }
      sort($fmtFiles);
      return $fmtFiles;
    }, []);

    $result = [];
    foreach ($files as $file) {
      $result[] = [
        'basename' => basename($file),
        'url' => '/' . basename($file),
      ];
    }
    return $result;
  }

  /**
   * @param string $version
   * @return array
   */
  public function getReleaseJson($version) {
    if (!VersionUtil::isWellFormed($version)) {
      throw $this->createNotFoundException("Invalid version");
    }

    return static::cache("json-$version", function() use ($version) {
      /** @var \Google\Cloud\Storage\Bucket $bucket */
      $bucket = $this->container->get('gcloud_release_bucket');
      $name = "civicrm-stable/$version/civicrm-$version.json";
      $rawJson = $bucket->object($name)->downloadAsString();
      $parsedJson = json_decode($rawJson, TRUE);
      if (empty($rawJson) || empty($parsedJson)) {
        throw new \Exception("JSON file ($name) had no valid JSON data");
      }
      return $parsedJson;
    }, []);
  }

  /**
   * @return array
   *   Ex: [..., '5.64.0', '5.63.2', '5.63.1', ..., '5.24.2', ...]
   */
  protected function getAllVersions() {
    return static::cache("allVersions", function() {
      /** @var \CiviDistManagerBundle\GStorageUrlFacade $gsu */
      $gsu = $this->container->get('gsu');
      $gsPaths = $gsu->getDirectories('gs://civicrm/civicrm-stable/');
      $versions = [];
      foreach ($gsPaths as $gsPath) {
        $version = basename(rtrim($gsPath, '/'));
        if (VersionUtil::isWellFormed($version)) {
          $versions[] = $version;
        }
      }
      usort($versions, 'version_compare');
      return $versions;
    }, []);
  }

  /**
   * @return array
   *   Ex: ['5.64' => ['5.64.1' => '/release/5.64.1']]
   */
  protected function getGroupedVersions() {
    $all = $this->getAllVersions();
    $result = [];
    foreach ($all as $version) {
      $result[VersionUtil::getMinor($version)][] = $version;
    }
    return $result;
  }

}
