<?php

namespace CiviUpgradeManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class CheckController extends Controller {

  public function checkAction() {
    $data = array(
      'rev' => 'c6ef392f2d68e4bc940d30e10c0e26b6-0003',
      'tar' => array(
        'Backdrop' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-backdrop-unstable-20160925.tar.gz',
        'Drupal' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-drupal-20160925.tar.gz',
        'Drupal6' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-drupal6-20160925.tar.gz',
        'Joomla' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-joomla-20160925.zip',
        // 'Joomla-Alt' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-joomla-alt-20160925.zip',
        'L10n' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-l10n-20160925.tar.gz',
        'WordPress' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-wordpress-20160925.zip',
      ),
      'git' => array(
        'civicrm-core' => 'FIXME',
        'civicrm-joomla' => 'FIXME',
        'civicrm-backdrop' => 'FIXME',
        'civicrm-packages' => 'FIXME',
        'civicrm-drupal' => 'FIXME',
        'civicrm-wordpress' => 'FIXME',
      ),
    );

    return new Response(json_encode($data), 200, array(
      'Content-type' => 'application/json',
    ));
  }

}
