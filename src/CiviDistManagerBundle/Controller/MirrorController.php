<?php

namespace CiviDistManagerBundle\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Quick and dirty implementation of a caching proxy (which stores data on gcloud).
 *
 * This is not likely to improve download times per-se. It's intended more to
 * improve resilience (e.g. preventing the problem where our worker-nodes get
 * banned from downloading from the canonical source).
 */
class MirrorController extends Controller {

  /**
   * @var \Doctrine\Common\Cache\Cache
   */
  protected $cache;

  /**
   * Quick and dirty workaround - GCloud public URLs are cached within CDN.
   * Add nonce to force one-time reload.
   * @var string
   */
  protected $suffix = '?r=1';

  /**
   * @var array
   *   Each item is keyed by a symbolic name and specifies the following properties:
   *   - upstreamUrl: string, e.g. 'https://example.com/download'
   *   - storageUrl: string, e.g. 'gs://my-cache/example-downloads
   *   - ttl: string, e.g. '+7 hours'
   *   - allow: string[], list of regex's matching cacheable paths
   */
  protected $mirrors;

  /**
   * MirrorController constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Doctrine\Common\Cache\Cache $cache
   * @param \CiviDistManagerBundle\GStorageUrlFacade $gsu
   * @param array $mirrors
   */
  public function __construct($container, $cache, $gsu, $mirrors) {
    $this->setContainer($container);
    $this->cache = $cache;
    $this->gsu = $gsu;
    $this->mirrors = $mirrors;
  }

  /**
   * Get a file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $path
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getAction(Request $request, $path) {
    $path = $this->filterPath($path);
    [$mirrorName, $relPath] = explode('/', $path, 2);
    $mirror = $this->mirrors[$mirrorName] ?? NULL;
    if (!$mirror) {
      throw new NotFoundHttpException();
    }

    if (!$this->isAllowed($relPath, $mirror['allow'])) {
      throw new AccessDeniedHttpException('Mirror policy does not allow requested path.');
    }

    $upstreamUrl = $mirror['upstreamUrl'] . '/' . $relPath;
    $checksum = hash('sha256', $upstreamUrl);
    $id = substr($checksum, 0, 4) . $checksum . '/' . basename($relPath);
    /** @var \Google\Cloud\Storage\Bucket $bucket */
    $storageUrl = $mirror['storageUrl'] . '/' . $id;
    $publicUrl = preg_replace(';^gs://;', 'https://storage.googleapis.com/', $storageUrl) . $this->suffix;

    if ($this->needsUpdate("mirror_$id", $storageUrl, $mirror['ttl'])) {
      // return new \Symfony\Component\HttpFoundation\Response("upload $upstreamUrl to $publicUrl");
      $this->transferFile($upstreamUrl, $storageUrl);
      $this->cache->save("mirror_$id", time());
    }

    return $this->redirect($publicUrl);
    // return new \Symfony\Component\HttpFoundation\Response("redirect to $publicUrl");
  }

  protected function needsUpdate(string $cacheId, string $storageUrl, string $ttl) {
    if (!$this->gsu->exists($storageUrl)) {
      return TRUE;
    }
    else {
      $lastCheck = $this->cache->fetch($cacheId);
      return empty($lastCheck) || (strtotime($ttl, $lastCheck) < time());
    }
  }

  protected function isAllowed(string $relPath, array $allows): bool {
    foreach ($allows as $allow) {
      if (preg_match($allow, $relPath)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param string $path
   * @return string
   */
  protected function filterPath($path) {
    if ($path === NULL || $path === FALSE || $path === '') {
      return '';
    }
    if ($path[0] === '/') {
      throw new \Exception("Invalid path - Should not begin with '/'");
    }
    if (preg_match(';[^a-zA-Z0-9\-\._\+\/];', $path)) {
      throw new \Exception("Invalid path - Unsupported characters");
    }
    if (preg_match(';^\.\.;', $path)) {
      throw new \Exception("Invalid path - No relative expressions allowed");
    }
    if (preg_match(';/\.+/;', $path)) {
      throw new \Exception("Invalid path - No relative expressions allowed");
    }
    return $path;
  }

  /**
   * @param $srcUrl
   * @param string $destUrl
   *
   * @return void
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function transferFile(string $srcUrl, string $destUrl): void {
    $client = new Client([
      'allow_redirects' => TRUE,
      'http_errors' => TRUE,
      'headers' => [
        'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
      ],
    ]);

    // In principle, it would be preferable to pass-through the stream instead
    // of buffering in a temp file, but I keep hitting problems in that path, and
    // this is actually good enough for our volume of data. (There may be an issue
    // where we need to receive full dataset before uploading file+checksum.)
    $tmpFile = tempnam(sys_get_temp_dir(), 'mirror_');

    try {
      chmod($tmpFile, 0700);
      $response = $client->request('GET', $srcUrl, [
        'sink' => $tmpFile,
      ]);
      $contentType = $response->getHeaderLine('Content-Type') ?: 'application/octet-stream';

      $tmpFileHandle = fopen($tmpFile, 'r');
      $this->gsu->upload($destUrl, $tmpFileHandle, [
        'metadata' => [
          'contentType' => $contentType,
        ],
      ]);
    }
    finally {
      unlink($tmpFile);
    }
  }

}
