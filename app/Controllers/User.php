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

	public function postsignup(){
		$name = $this->request->getVar('name');
		$email = $this->request->getVar('email');
		$password = $this->request->getVar('password');
		$type = $this->request->getVar('password');


	}


	public function getSignin()
	{

		$this->twig->display( 'signin' );
	}


}
