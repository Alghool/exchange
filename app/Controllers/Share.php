<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Psr\Log\LoggerInterface;

class Share extends BaseController
{

	private $shareModel = null;

	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		parent::initController($request, $response, $logger);

		$this->shareModel = new \App\Models\ShareModel();
	}

	public function getMyShares(){
		$filter = $this->request->getVar('filter');
		$user = $this->session->get('user');

		$shares = $this->shareModel->where("owner", $user['user_id'])-> withCompany();
		$filter = $this->request->getVar('filter');

		if($filter){
			$shares->where('listed', $filter);
		}

		$shares = $shares->findAll();

		$this->twig->display( 'my_shares', ["shares" => $shares] );
	}

	public function getSellAll($shareID){
		$this->shareModel->update($shareID, ['listed' => "yes"]);
		$msg[] = ["type" => 'success', 'text' => 'shares listed successfully'];
		$this->twig->addGlobal('msgs', $msg);
		return redirect()->to('share/myShares');
	}

	public function postSellShares(){
		$user = $this->session->get('user');
		$shareID = $this->request->getVar('share');
		$oldShare = $this->shareModel->withCompany()->find($shareID);
		$newShare = [
			'value' => $this->request->getVar('price'),
			'amount' => $this->request->getVar('amount'),
			'owner' => $user['user_id'],
			'project' => $oldShare['project'],
			'listed' => "yes"
		];

		$this->shareModel->insert($newShare);

		$newAmount = $oldShare['amount'] - $newShare['amount'];
		if($newAmount == 0){
			$this->shareModel->delete($shareID);
		}
		else{
			$this->shareModel->update($shareID, [
				'value' => $newAmount * $oldShare['share_price'],
				'amount' => $newAmount
			]);
		}


		return redirect()->to('share/myShares');
	}

	public function getUnlist($shareID){
		$user = $this->session->get('user');
		$share = $this->shareModel->withCompany()->find($shareID);
		//is there unlisted company shares
		$unlistedShare = $this->shareModel
						  ->where('owner', $user['user_id'])
			              ->where('project', $share['project'])
			              ->where('listed', 'no')
						  ->first();

		if($unlistedShare){
			$newAmount = $share['amount'] + $unlistedShare['amount'];

			$this->shareModel->update($unlistedShare['share_id'], [
				'value' => $newAmount * $share['share_price'],
				'amount' => $newAmount
			]);
			$this->shareModel->delete($shareID);
		}
		else{
			$this->shareModel->update($shareID, ['listed' => "no"]);
		}
		return redirect()->to('share/myShares');
	}

	public function getListedShares(){
		$user = $this->session->get('user');

		$shares = $this->shareModel->where('listed', 'yes')->where("owner !=", $user['user_id'])-> withCompany();

		$shares = $shares->findAll();

		$this->twig->display( 'listed_shares', ["shares" => $shares] );
	}

	public function getBuyAllShares($shareID){
		$user = $this->session->get('user');
		$share = $this->shareModel->find($shareID);
		if( $share['value'] > $user['credit'] ){
			$msg[] = ["type" => 'danger', 'text' => 'you do not have enough credit to complete this transaction'];
			$this->twig->addGlobal('msgs', $msg);
			return $this->getListedShares();
		}

		$newCredit = $user['credit'] - $share['value'];
		$userModel = new UserModel();
		$userModel->update($user['user_id'], ['credit' => $newCredit]);
		$this->session->set('user', $user);

		$projectModel  = new ProjectModel();
		$project = $projectModel->find($share['project']);

		$newShareValue = $share['value'] / $share['amount'];
		if($newShareValue != $project['share_price']){
			$projectUpdate = [
				'share_price'=>$newShareValue,
				'total_value' => ($newShareValue * $project['total_shares'])
			];

			$projectModel->update($project['project_id'],$projectUpdate );
			$this->shareModel->builder()->where('project', $share['project'])->where('listed', 'no')->set('value', "amount * {$newShareValue}", false)->update();
		}

		$userModel->builder()->where('user_id', $share['owner'])->set('credit', "credit + {$share['value']}", false)->update();
		$this->shareModel->update($shareID, [
			'owner' => $user['user_id'],
			'listed' => "no"
		]);

		return redirect()->to('share/listedshares');
	}

//	public function getTest(){
//		$newShareValue = "1111.11";
//		$koko = $this->shareModel->builder()->where('share_id', 5)->set('value', "amount * {$newShareValue}", false)->update();
//
//		dd($koko);
//	}
}