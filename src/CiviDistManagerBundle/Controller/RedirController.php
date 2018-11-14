<?php

namespace CiviDistManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RedirController
 * @package CiviDistManagerBundle\Controller
 *
 * General helpers for redirection.
 */
class RedirController extends Controller {

  /**
   * Redirect to the same path... but with a suffix!
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $suffix
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function appendAction(Request $request, $suffix) {
    return $this->redirect($request->getRequestUri() . $suffix);
  }

}
