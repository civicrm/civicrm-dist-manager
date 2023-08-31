<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\BuildRepository;
use CiviDistManagerBundle\VersionUtil;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Version;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Landing page for information about a particular version of CiviCRM.
 */
class AboutController extends Controller {

  const STANDARD_TTL = 3600, ERROR_TTL = 120;

  /**
   * Landing page for information about a particular version of CiviCRM.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function viewAction(Request $request, $version) {
    if (!VersionUtil::isWellFormed($version)) {
      throw $this->createNotFoundException("Invalid version");
    }

    // If requested with "?next=1", then show the draft implementation of UI.
    if ($request->get('prototype')) {
      return $this->render('CiviDistManagerBundle:About:about.html.twig', [
        'version' => $version,
        'files' => $this->getReleaseFiles($version),
        'notes' => $this->getReleaseNotes($version),
        'jsonDef' => $this->getReleaseJson($version),
      ]);
    }

    $url = $this->pickRedirectUrl($version);
    if ($url) {
      return $this->redirect($url);
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
   * @return Cache
   */
  protected function getCache() {
    return $this->container->get('civi_upgrade_manager.dist_cache');
  }

  /**
   * @param string $version
   *   Ex: '5.0.1'.
   * @return string|NULL
   */
  protected function pickRedirectUrl($version) {
    /**
     * @var Cache
     */
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

      $cache->save($cacheId, $url, self::STANDARD_TTL);
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
    $cacheId = 'releaseNotes-' . $version . '.html';
    if ($this->getCache()->contains($cacheId)) {
      return $this->getCache()->fetch($cacheId);
    }

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
    if (!empty($md)) {
      $this->getCache()->save($cacheId, $md, self::STANDARD_TTL);
      return $md;
    }
    else {
      $placeholder = sprintf("Failed to load release notes for <code>%s</code>.", $version);
      $this->getCache()->save($cacheId, $placeholder, self::ERROR_TTL);
      return $placeholder;
    }
  }

  /**
   * @param string $version
   * @return string[]
   *   List of file names.
   *   Ex: ['civicrm-1.2.3-drupal.tar.gz', 'civicrm-1.2.3-wordpress.zip']
   */
  protected function getReleaseFiles($version) {
    if (!VersionUtil::isWellFormed($version)) {
      throw $this->createNotFoundException("Invalid version");
    }

    $cacheId = 'files-' . $version;
    if ($this->getCache()->contains($cacheId)) {
      return $this->getCache()->fetch($cacheId);
    }

    /** @var \CiviDistManagerBundle\GStorageUrlFacade $gsu */
    $gsu = $this->container->get('gsu');
    $gsFiles = $gsu->getFiles('gs://civicrm/civicrm-stable/' . $version . '/');
    $fmtFiles = [];
    foreach ($gsFiles as $gsFile) {
      $fmtFiles[] = basename($gsFile);
    }
    sort($fmtFiles);

    $this->getCache()->save($cacheId, $fmtFiles, self::STANDARD_TTL);
    return $fmtFiles;
  }

  /**
   * @param string $version
   * @return array
   */
  protected function getReleaseJson($version) {
    if (!VersionUtil::isWellFormed($version)) {
      throw $this->createNotFoundException("Invalid version");
    }

    $cacheId = 'json-' . $version;
    if ($this->getCache()->contains($cacheId)) {
      return $this->getCache()->fetch($cacheId);
    }

    /** @var \Google\Cloud\Storage\Bucket $bucket */
    $bucket = $this->container->get('gcloud_release_bucket');
    $name = "civicrm-stable/$version/civicrm-$version.json";
    $rawJson = $bucket->object($name)->downloadAsString();
    $parsedJson = json_decode($rawJson, TRUE);
    if (!empty($rawJson) || empty($parsedJson)) {
      $this->getCache()->save($cacheId, $parsedJson, self::STANDARD_TTL);
      return $parsedJson;
    }
    else {
      $placeholder = [];
      $this->getCache()->save($cacheId, $placeholder, self::ERROR_TTL);
      return $placeholder;
    }
  }

}
