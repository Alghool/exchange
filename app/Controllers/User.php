<?php

namespace App\Controllers;

class User extends BaseController
{
	public function index()
	{

	}

	public function getSignup()
	{

		$this->twig->display( 'signup' );
	}

	public function getSignin()
	{

		$this->twig->display( 'signin' );
	}
}
