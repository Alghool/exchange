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

		$user = $this->userModel->find($userID);
		$this->session->set('user', $user);

		$this->getCorrectNavbar($user);

		$this->twig->display( 'profile',["user" => $user] );

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
			$this->session->set('user', $user);
			$this->getCorrectNavbar($user);
			$msg[] = ["type" => 'success', 'text' => 'successfully logged in'];

			if($user['profile_complete']){
				//redirect to the correct page
			}
			else{
				$msg[] = ["type" => 'info', 'text' => 'please complete your profile'];
				$this->twig->addGlobal('msgs', $msg);
				$this->twig->display( 'profile',["user" => $user] );
			}
		}
		else{
			$msg[] = ["type" => 'danger', 'text' => 'username/password not valid'];
			$this->twig->addGlobal('msgs', $msg);
			$this->twig->display( 'signin', ["email" => $email] );
		}



	}

	public function getProfile(){
		$user = $this->session->get('user');
		$this->twig->display( 'profile',["user" => $user, "writePath" => WRITEPATH] );
	}

	public function postProfile(){
		$user = $this->session->get('user');

		$profile = [
			"name" => $this->request->getVar('name'),
			"id" => $this->request->getVar('id'),
			"address" => $this->request->getVar('address'),
			"phone" => $this->request->getVar('phone'),
			"birthday" => $this->request->getVar('birthday'),
			"country" => $this->request->getVar('country'),
			"citizenship" => $this->request->getVar('citizenship')
		];

		$proofOfID = $this->request->getFile('proof_id');
		if( $proofOfID->isValid() ){
			$proofOfIDPath = $proofOfID->store();
			$profile["proof_id"] = $proofOfIDPath;
			if($user['proof_id']) @unlink(WRITEPATH .'uploads/'.$user['proof_id']);

		}

		$proofOfAddress = $this->request->getFile('proof_address');
		if( $proofOfAddress->isValid() ){
			$proofOfAddressPath = $proofOfAddress->store();
			$profile["proof_address"] = $proofOfAddressPath;
			if($user['proof_address']) @unlink(WRITEPATH .'uploads/'.$user['proof_address']);
		}

		if($proofOfAddress->isValid() && $proofOfID->isValid() ){
			$profile["profile_complete"] = 1;
		}


		$this->userModel->update($user['user_id'], $profile);

		$user = $this->userModel->find($user['user_id']);
		$this->session->set('user', $user);

		$msg[] = ["type" => 'success', 'text' => 'profile updated successfully'];
		$this->twig->addGlobal('msgs', $msg);
		$this->twig->display( 'profile',["user" => $user] );
	}

	public function getLogout(){
		$this->session->remove('user');
		$this->getCorrectNavbar([]);
		$this->twig->display( 'home' );
	}
}

