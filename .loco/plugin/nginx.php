<?php

namespace Loco;

Loco::dispatcher()->addListener('loco.expr.functions', function (LocoEvent $e) {
  $e['functions']['nginx_event_detect'] = function () {
    $uname = php_uname('s');
    $map = [
      'Linux' => 'epoll',
      'Darwin' => 'kqueue',
    ];
    if (isset($map[$uname])) {
      return $map[$uname];
    }
    else {
      fprintf(STDERR, "WARNING: Failed to detect nginx event mode for %s (in %s)\n", $uname, __FILE__);
      return '';
    }

  };
});
