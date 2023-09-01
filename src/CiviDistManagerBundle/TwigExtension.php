<?php

namespace CiviDistManagerBundle;

class TwigExtension extends \Twig\Extension\AbstractExtension {
  public function getFunctions() {
    return [
      new \Twig\TwigFunction('clientCacheCode', function () {
        // WISHLIST: Random ID everytime to reinstall
        return date('YmdH');
      })
    ];
  }
}
