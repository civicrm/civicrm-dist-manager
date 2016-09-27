<?php

namespace CiviUpgradeManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CheckController extends Controller
{
    public function checkAction()
    {
        return $this->render('CiviUpgradeManagerBundle:Check:check.html.php', array(
            // ...
        ));
    }

}
