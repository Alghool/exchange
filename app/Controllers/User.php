<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class User extends BaseController
{
    private $userModel = null;


	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		$this->userModel = new \App\Models\UserModel();
	}


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
		$type = $this->request->getVar('type');
		$data = [
			'name' => $name,
			'email' => $email,
			'password' => $password,
			'type' => $type,
			'credit' => 100
		];

		$this->userModel->insert($data, false);
		//todo: add a global msgs handler
		$msg = [];
		$msg[] = ["type" => 'success', 'text' => 'successfully registered'];
		$msg[] = ["type" => 'info', 'text' => 'please complete your profile'];
		$this->twig->addGlobal('msgs', $msg);
		$userID = $this->userModel->getInsertID();
		$this->getProfile($userID);
	}

	public function  getProfile($userID){
		$user = $this->userModel->find($userID);

		$this->twig->display( 'profile', $user );

	}

	public function getSignin()
	{

		$this->twig->display( 'signin' );
	}

	public function postSignin()
	{
		$email = $this->request->getVar('email');
		$password = $this->request->getVar('password');

		$user = $this->userModel->where('email', $email)->where('password', $password)->first();
		$msg = [];
		if ($user){
			$msg[] = ["type" => 'success', 'text' => 'successfully logged in'];
		}
		else{
			$msg[] = ["type" => 'danger', 'text' => 'username/password not valid'];
		}

		$this->twig->addGlobal('msgs', $msg);
		$this->twig->display( 'signin' );
	}

}
