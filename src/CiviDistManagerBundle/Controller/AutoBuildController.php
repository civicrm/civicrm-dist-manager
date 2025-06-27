<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\BuildRepository;
use CiviDistManagerBundle\CacheTrait;
use CiviDistManagerBundle\GitBrowsers;
use Google\Cloud\Core\Timestamp;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browse and inspect the auto-builds.
 */
class AutoBuildController extends Controller {

  use CacheTrait;

  public function __construct() {
    $this->standardTtl = 120;
    $this->errorTtl = 30;
  }

  /**
   * Display a list of builds and files. (HTML)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $branch
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function browseAction(Request $request, $branch) {
    if (!$this->isWellFormedBranch($branch)) {
      throw $this->createNotFoundException("Invalid branch");
    }

    $timestamps = $this->getFolders($branch);
    usort($timestamps, function ($a, $b) {
      return -1 * strnatcmp($a, $b);
    });
    array_unshift($timestamps, 'LATEST');

    return $this->render('CiviDistManagerBundle:Browse:browse.html.twig', array(
      'breadcrumbs' => [
        ['title' => 'CiviCRM Home', 'url' => 'https://civicrm.org/'],
        ['title' => 'Download', 'url' => 'https://civicrm.org/download'],
        ['title' => 'Autobuild', 'url' => '/latest/'],
        ['title' => $request->get('branch')],
      ],
      'targetBranch' => $request->get('branch'),
      'folders' => $timestamps,
    ));
  }

  /**
   * Download a particular build (redirect).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function downloadAction(Request $request) {
    $file = $this->getFile($request->get('branch'), $request->get('ts'), $request->get('basename'));

    $bucket = \CiviDistManagerBundle\GCloudFactory::createBucket($this->container->get('gcloud_storage'), $file['bucket']);
    $signedUrl = $bucket->object($file['file'])->signedUrl(new Timestamp(new \DateTime($this->container->getParameter('gcloud_url_ttl'))));
    return $this->redirect($signedUrl);
  }

  /**
   * Get an overview about specific build
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $branch
   * @param $ts
   * @return \Symfony\Component\HttpFoundation\Response|null
   */
  public function viewAction(Request $request, $branch, $ts) {
    if (!$this->isWellFormedBranch($branch)) {
      throw $this->createNotFoundException("Invalid branch");
    }

    if ($ts === 'LATEST') {
      $title = $ts;
      $ts = $this->getLatest($branch);
      $releaseFiles = $this->getLatestReleaseFiles($branch);
      $jsonFile = preg_grep(';\.json$;', array_column($this->getReleaseFiles($branch, $this->getLatest($branch)), 'basename'))[0];
    }
    else {
      $title = $ts;
      if (!$this->isWellFormedTimestamp($ts)) {
        throw $this->createNotFoundException("Invalid timestamp");
      }
      $releaseFiles = $this->getReleaseFiles($branch, $ts);
      $jsonFile = preg_grep(';\.json$;', array_column($releaseFiles, 'basename'))[0];
    }

    if (!empty($releaseFiles)) {
      $json = $this->getReleaseJson($branch, $ts, $jsonFile);
      return $this->render('CiviDistManagerBundle:About:about.html.twig', [
        'breadcrumbs' => [
          ['title' => 'CiviCRM Home', 'url' => 'https://civicrm.org/'],
          ['title' => 'Download', 'url' => 'https://civicrm.org/download'],
          ['title' => 'Autobuild', 'url' => '/latest/'],
          ['title' => $branch, 'url' => '/latest/branch/' . $branch . '/'],
          ['title' => $title],
        ],
        'version' => $json['version'],
        'files' => $releaseFiles,
        // 'notes' => $this->getReleaseNotes($version),
        'jsonDef' => $json,
        'gitBrowsers' => GitBrowsers::getAll(),
      ]);
    }
    else {
      $response = $this->render('CiviDistManagerBundle:About:unknown.html.twig', [
        'version' => "$branch/$ts",
      ]);
      $response->setStatusCode(404);
      return $response;
    }
  }

  /**
   * Locate the file record from the buildrepo.
   *
   * @param string $branch
   *   Ex: '5.0', 'master'.
   * @param string $ts
   *   Ex: 'LATEST', '201704210350'.
   * @param string $basename
   *   Ex: 'civicrm-X.Y.Z-drupal-LATEST.tar.gz'.
   * @return array
   */
  protected function getFile($branch, $ts, $basename) {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');

    if ($ts === 'LATEST') {
      $wildBasename = strtr($basename, [
        'X.Y.Z' => '*',
        'LATEST' => '*',
      ]);
      $files = $buildRepo->getFilesByWildcard($branch . '/' . $wildBasename);
      usort($files, function($a, $b) {
        return -1 * strnatcmp($a['ts'], $b['ts']);
      });
      $file = isset($files[0]) ? $files[0] : NULL;
    }
    else {
      $file = $buildRepo->findFile(fn($f) => $f['branch'] == $branch && $f['basename'] == $basename);
    }

    if (!$file) {
      throw $this->createNotFoundException();
    }
    return $file;
  }

  protected function getFolders(string $branch): array {
    $files = static::cache("autobuild-list-$branch", function() use ($branch) {
      /** @var \CiviDistManagerBundle\GStorageUrlFacade $gsu */
      $gsu = $this->container->get('gsu');
      $gsFiles = $gsu->getFiles('gs://civicrm-build/' . $branch . '/');
      $jsonFiles = preg_grep(';\.json$;', $gsFiles);
      asort($jsonFiles);
      return $jsonFiles;
    }, []);

    $result = [];
    foreach ($files as $file => $uri) {
      if (preg_match(';-(\d+).json$;', $file, $m)) {
        $result[] = $m[1];
      }
    }
    sort($result);

    return $result;
  }

  protected function getLatest(string $branch): string {
    $all = $this->getFolders($branch);
    sort($all);
    return $all[0];
  }

  /**
   * @param string $branch
   * @param string $ts
   *
   * @return string[]
   *   List of file names and their URLs.
   *   Ex: [['basename' => 'civicrm-1.2.3-drupal.tar.gz', 'url' => 'https://....']]
   */
  protected function getReleaseFiles($branch, $ts) {
    // $files = static::cache(NULL, function() use ($branch, $ts) {
    $files = static::cache("autobuild-files-$branch-$ts", function() use ($branch, $ts) {
      /** @var \CiviDistManagerBundle\GStorageUrlFacade $gsu */
      $gsu = $this->container->get('gsu');
      $gsFiles = $gsu->getFiles('gs://civicrm-build/' . $branch . '/');
      $fmtFiles = [];
      foreach ($gsFiles as $gsFile) {
        if (strpos(basename($gsFile), '-' . $ts)) {
          $fmtFiles[] = $gsFile;
        }
      }
      asort($fmtFiles);
      return $fmtFiles;
    }, []);

    $result = [];
    foreach ($files as $file) {
      $result[] = [
        'basename' => basename($file),
        'url' => basename($file),
      ];
    }
    return $result;
  }

  protected function getLatestReleaseFiles($branch): array {
    $latest = $this->getLatest($branch);
    $files = $this->getReleaseFiles($branch, $latest);
    foreach ($files as &$file) {
      foreach (['basename', 'url'] as $prop) {
        // This makes it easier to re-use the link in the future.
        $file[$prop] = str_replace($latest, 'LATEST', $file[$prop]);
        $file[$prop] = preg_replace('/-(\d|alpha|beta|\.)+-/', '-X.Y.Z-', $file[$prop]);
      }
    }
    return $files;
  }

  /**
   * @param string $branch
   * @param string $ts
   * @param string $jsonFile
   * @return array
   */
  protected function getReleaseJson($branch, $ts, $jsonFile) {
    // return static::cache(NULL, function() use ($branch, $ts, $jsonFile) {
    return static::cache("autobuild-json-$branch-$ts-$jsonFile", function() use ($branch, $ts, $jsonFile) {
      /** @var \Google\Cloud\Storage\Bucket $bucket */
      $bucket = $this->container->get('gcloud_build_bucket');
      $name = "$branch/$jsonFile";
      $rawJson = $bucket->object($name)->downloadAsString();
      $parsedJson = json_decode($rawJson, TRUE);
      if (empty($rawJson) || empty($parsedJson)) {
        throw new \Exception("JSON file ($name) had no valid JSON data");
      }
      return $parsedJson;
    }, []);
  }

  /**
   * @param $branch
   *
   * @return false|int
   */
  protected function isWellFormedBranch($branch) {
    return preg_match(';^[-a-z0-9]+(\.[-a-z0-9]+)?$;', $branch) && !preg_match(';-(security|esr);', $branch);
  }

  /**
   * @param $ts
   *
   * @return false|int
   */
  protected function isWellFormedTimestamp($ts) {
    return preg_match(';^\d+$;', $ts) && mb_strlen($ts) > 10;
  }

}
