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

    $url = $this->pickUrl($version);
    if ($url) {
      return $this->redirect($url);
    }
    else {
      throw $this->createNotFoundException("Failed to locate release notes for requested version");
    }

    //    $response = $this->render('CiviDistManagerBundle:About:view.html.twig', array(
    //      'version' => $version,
    //      'relNotes' => $this->getReleaseNotesHtml($version),
    //    ));
    //    $response->setSharedMaxAge(self::CACHE_TTL);
    //    return $response;
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
  protected function pickUrl($version) {
    /**
     * @var Cache
     */
    $cache = $this->getCache();
    $cacheId = md5('exists' . $version);

    if (!$cache->contains($cacheId)) {
      $candidates = [
        sprintf('https://github.com/civicrm/civicrm-core/blob/%s/release-notes/%s.md', VersionUtil::getMinor($version), VersionUtil::getPatch($version)),
        sprintf('https://github.com/civicrm/civicrm-core/blob/%s/release-notes/%s.md', 'master', VersionUtil::getPatch($version)),
        sprintf('https://github.com/civicrm/civicrm-core/blob/%s/release-notes/%s.md', VersionUtil::getPatch($version), VersionUtil::getPatch($version))
      ];
      $url = NULL;
      foreach ($candidates as $candidate) {
        if ($this->fileExistsInHttp($candidate)) {
          $url = $candidate;
          break;
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

  //  protected function getReleaseNotesHtml($version) {
  //    $cacheId = 'releaseNotes-' . $version . '.html';
  //    if ($this->getCache()->contains($cacheId)) {
  //      return $this->getCache()->fetch($cacheId);
  //    }
  //
  //    $mdUrl = sprintf('https://github.com/civicrm/civicrm-core/raw/%s/release-notes/%s.md',
  //      VersionUtil::getMinor($version), VersionUtil::getPatch($version));
  //    $md = file_get_contents($mdUrl);
  //    if (empty($md)) {
  //      $parser /*sic*/ = new \cebe\markdown\GithubMarkdown();
  //      $html = $parser->parse($md);
  //      $this->getCache()->save($cacheId, $html, self::STANDARD_TTL);
  //    }
  //    else {
  //      $html = sprintf("<p>Failed to load release notes for <code>%s</code>.</p>", $version);
  //      $this->getCache()->save($cacheId, $html, self::ERROR_TTL);
  //    }
  //
  //    return $this->getCache()->fetch($cacheId);
  //  }

}
