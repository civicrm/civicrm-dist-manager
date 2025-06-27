<?php
namespace CiviDistManagerBundle;

class VersionUtil {

  /**
   * @param string $ver
   *   Ex: '5.1.2'
   * @return string
   *   Ex: '5'
   */
  public static function getMajor($ver) {
    $parts = preg_split('/[\.\+]/', $ver);
    return $parts[0];
  }

  /**
   * @param string $ver
   *   Ex: '5.1.2'
   * @return string
   *   Ex: '5.1'
   */
  public static function getMinor($ver) {
    $parts = preg_split('/[\.\+]/', $ver);
    return $parts[0] . '.' . $parts[1];
  }

  /**
   * @param string $ver
   *   Ex: '5.1.2'
   * @return string
   *   Ex: '5.1.2'
   */
  public static function getPatch($ver) {
    $parts = preg_split('/[\.\+]/', $ver);
    return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
  }

  /**
   * @param string $ver
   *   Ex: '4.7.10', '5.1.beta1', '<script>'.
   * @return bool
   *   TRUE if the string is a well-formed version number.
   */
  public static function isWellFormed($ver) {
    return (bool) preg_match(';^[0-9]+\.[0-9a-z_\-\.]+$;', $ver);
  }

  public static function max($versions) {
    $extreme = NULL;
    foreach ($versions as $v) {
      if (version_compare($extreme, $v, '<')) {
        $extreme = $v;
      }
    }
    return $extreme;
  }

  public static function isBetween($low, $lowOp, $target, $highOp, $high): bool {
    return version_compare($low, $target, $lowOp) && version_compare($target, $high, $highOp);
  }

}
