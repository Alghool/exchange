<?php

namespace App\Controllers;

use App\Models\ShareModel;
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
		$msg = [];
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
		$register = $this->userModel->insert($data, false);
		if(!$register){
			$error = $this->userModel->errors()["email"];
			$msg[] = ["type" => 'danger', 'text' => $error];
			$this->twig->addGlobal('msgs', $msg);
			return $this->twig->display( 'signup',["user" => $data] );
		}

		//todo: add a global msgs handler
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
				if($user['type'] == 'lister'){
					return redirect()->to('share/listedShares');
				}
				else if($user['type'] == 'admin'){
					return redirect()->to('project/adminCompanies');
				}
				else if($user['type'] == 'investor'){
					return redirect()->to('share/listedShares');
				}
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
			"citizenship" => $this->request->getVar('citizenship'),
			"profile_complete" => 1
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

	public function getAdminUsers(){
		$offset = $this->request->getVar('offset')|0;
		$limit = 15;
		$users = $this->userModel->findAll($limit, $offset*$limit);
		$usersCount = $this->userModel->countAll();
		$pages =$usersCount / $limit;
		$pages = (int)$pages == $pages? (int)$pages - 1 : (int)$pages ;
		$data = ["users" => $users,
				"pagination" => [
					'offset' => $offset,
					'total' =>$usersCount,
					'pages' => $pages
					]
				];
		$this->twig->display( 'admin_users', $data );
	}

	public function getCredit(){
		$user = $this->session->get("user");

		$shareModel = new ShareModel();

		$shares = $shareModel->where('owner', $user['user_id'])->withCompany()->findAll();

		$this->twig->display( 'user_credit' ,['shares' => $shares]);

	}
}

