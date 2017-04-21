<?php
namespace CiviUpgradeManagerBundle;

class VersionUtil {

  public static function max($versions) {
    $extreme = NULL;
    foreach ($versions as $v) {
      if (version_compare($extreme, $v, '<')) {
        $extreme = $v;
      }
    }
    return $extreme;
  }

}