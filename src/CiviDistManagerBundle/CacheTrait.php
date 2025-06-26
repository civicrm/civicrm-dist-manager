<?php

namespace CiviDistManagerBundle;

use Doctrine\Common\Cache\Cache;

trait CacheTrait {

  protected $standardTtl = 3600;

  protected $errorTtl = 120;

  /**
   * @return \Doctrine\Common\Cache\Cache
   */
  protected function getCache() {
    return $this->container->get('civi_upgrade_manager.dist_cache');
  }

  /**
   * Load some data from the cache - or else lookup the data (and store it
   * in the cache).
   *
   * @param string $cacheId
   * @param callable $callback
   * @param null $placeholder
   *
   * @return mixed|null
   */
  protected function cache($cacheId, $callback, $placeholder = NULL) {
    $cache = $this->getCache();
    if ($cacheId !== NULL && $cache->contains($cacheId)) {
      return $cache->fetch($cacheId);
    }

    try {
      $data = $callback();
      $ttl = $this->standardTtl;
    } catch (\Throwable $t) {
      $data = $placeholder;
      $ttl = $this->errorTtl;
      $this->container->get('logger')->warning("Failed to populate cache item ({cacheId})", [
        'cacheId' => $cacheId,
        'exception' => $t,
      ]);
    }

    if ($cacheId !== NULL) {
      $cache->save($cacheId, $data, $ttl);
    }
    return $data;
  }

}
