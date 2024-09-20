<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\VersionUtil;
use Doctrine\Common\Cache\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class JoomlaController extends Controller {

  const RELEASE_TTL = 5 * 60 * 60;

  const VERSIONS_TTL = 120;

  const VERSIONS_URL = 'https://latest.civicrm.org/versions.json';

  // FIXME: When we actually have joomla5.zip files, use those instead.
  // For now, we list joomla.zip files.
  const TARGET_FILE = '-joomla.zip';
  // const TARGET_FILE = '-joomla5.zip';

  public function indexAction($suffix) {
    $xml = new \SimpleXMLElement('<updates/>');
    $releases = $this->pickReleases();
    foreach ($releases as $release) {
      $composerJson = $this->fetchComposerJson($release);
      $checksums = $this->fetchChecksums($release);
      if (isset($checksums[$suffix])) {
        $this->addRelease($xml, $release, $composerJson, $suffix, $checksums[$suffix]);
      }
    }
    $text = $xml->asXML();
    return new Response($text, 200, [
      'Content-type' => 'application/xml',
    ]);
  }

  /**
   * @param \SimpleXMLElement $xml
   * @param array{version: string, date: string, message: ?string} $release
   * @return void
   * @link https://docs.joomla.org/Deploying_an_Update_Server
   */
  protected function addRelease(\SimpleXMLElement $xml, array $release, array $composerJson, string $targetFile, ?string $sha256): void {
    $update = $xml->addChild('update');
    [$major, $minor] = explode('.', $release['version']);

    $update->addChild('name', "CiviCRM {$major}.{$minor}");
    $update->addChild('description',  $release['message'] ?? "CiviCRM {$major}.{$minor}");
    $update->addChild('element', 'civicrm'); // com_civicrm?
    $update->addChild('type', 'file');
    $update->addChild('client', 'administrator');
    $update->addChild('version', $release['version']);

    $infoUrl = $update->addChild('infourl', sprintf('https://download.civicrm.org/release/%s', urlencode($release['version'])));
    $infoUrl->addAttribute('title', 'CiviCRM');

    // Add 'downloads' element with nested 'downloadurl' and 'downloadsource'
    $downloads = $update->addChild('downloads');
    $downloadUrl = $downloads->addChild('downloadurl', sprintf('https://download.civicrm.org/civicrm-%s%s', $release['version'], $targetFile));
    $downloadUrl->addAttribute('type', 'full');
    $downloadUrl->addAttribute('format', 'zip');

    // Additional/fallback URLs
    // $downloadsource1 = $downloads->addChild('downloadsource', 'https://github.com/joomla/joomla-cms/releases/download/3.9.6/Joomla_3.9.6-Stable-Update_Package.zip');
    // $downloadsource1->addAttribute('type', 'full');
    // $downloadsource1->addAttribute('format', 'zip');
    //
    // $downloadsource2 = $downloads->addChild('downloadsource', 'https://update.joomla.org/releases/3.9.6/Joomla_3.9.6-Stable-Update_Package.zip');
    // $downloadsource2->addAttribute('type', 'full');
    // $downloadsource2->addAttribute('format', 'zip');

    // Add 'tags' element with a 'tag' child
    $tags = $update->addChild('tags');
    $tags->addChild('tag', 'stable');

    if ($sha256) {
      $update->addChild('sha256', $sha256);
    }

    // Add maintainer details
    $update->addChild('maintainer', 'CiviCRM');
    $update->addChild('maintainerurl', 'https://civicrm.org');

    // Specification says: "Optional (unknown use)"
    // $update->addChild('section', 'STS');

    $targetplatform = $update->addChild('targetplatform');
    $targetplatform->addAttribute('name', 'joomla');
    $targetplatform->addAttribute('version', '.*');

    // Add PHP minimum version
    $update->addChild('php_minimum', $composerJson['config']['platform']['php'] ?? '7.4');

    // FIXME: If we had better metadata about MySQL requirements, we could generate <supported_databases>
  }

  /**
   * @return Cache
   */
  protected function getCache() {
    return $this->container->get('civi_upgrade_manager.dist_cache');
  }

  /**
   * Which versions of CiviCRM are we interested in reporting about?
   *
   * @return array
   */
  protected function pickReleases(): array {
    $branches = json_decode($this->fetchFile(static::VERSIONS_URL, static::VERSIONS_TTL), 1);
    $result = [];
    foreach ($branches as $branchName => $branch) {
      if ($branch['status'] === 'eol') {
        continue;
      }
      $max = NULL;
      foreach ($branch['releases'] as $release) {
        if (str_contains($release['message'] ?? '', 'ESR')) {
          continue;
        }
        if ($max === NULL || version_compare($release['version'], $max['version'], '>')) {
          $max = $release;
        }
      }
      if ($max !== NULL) {
        $result[$branchName] = $max;
      }
    }

    ksort($result);
    return $result;
  }

  /**
   * Download a remote JSON document. If possible, use/store the
   * document in a cache (per CACHE_TTL).
   *
   * @return string
   */
  protected function fetchFile($url, int $ttl) {
    /** @var Cache $cache */
    $cacheKey = "1 $url";
    $cache = $this->getCache();
    if (!$cache->contains($cacheKey)) {
      $data = file_get_contents($url);
      if ($data) {
        $cache->save($cacheKey, $data, $ttl);
      }
      else {
        throw new \RuntimeException('Failed to fetch URL: ' . $url);
      }
    }
    // return json_decode($cache->fetch($cacheKey), 1);
    return $cache->fetch($cacheKey);
  }

  protected function fetchComposerJson(array $release): array {
    $url = sprintf('https://github.com/civicrm/civicrm-core/raw/%s/composer.json', urlencode($release['version']));
    return json_decode($this->fetchFile($url, static::RELEASE_TTL), 1);
  }

  protected function fetchChecksums(array $release): array {
    $url = sprintf('https://download.civicrm.org/civicrm-%s.SHA256SUMS', urlencode($release['version']));
    $lines = explode("\n", file_get_contents($url));
    $lines = preg_grep('/\S/', $lines);
    $keyValues = array_map(fn($l) => preg_split('/\s+/', trim($l)), $lines);
    $result = [];
    foreach ($keyValues as $keyValue) {
      [$value, $key] = $keyValue;
      if (preg_match('/civicrm-(.*)(.json|-.*)$/', $key, $m)) {
        $result[$m[2]] = $value;
      }
    }
    return $result;
  }

}