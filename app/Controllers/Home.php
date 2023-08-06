<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {

	    $this->twig->display( 'home' );
    }
}
