<?php

namespace App\Controllers;

use App\Models\OfferModel;
use App\Models\ProjectModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\I18n\TimeTrait;
use CodeIgniter\Model;
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
		$shareValue = $share['value'] / $share['amount'];

		if ( $this->buyShare($share['value'], $share['project'], $shareValue, $share['owner'], $user)){
			$this->shareModel->update($shareID, [
				'owner' => $user['user_id'],
				'listed' => "no"
			]);
			return redirect()->to('share/listedshares');
		}
		else{
			return $this->getListedShares();
		}

	}

	public function postBuyShares(){
		$user = $this->session->get('user');
		$shareID = $this->request->getVar('share');
		$amount = $this->request->getVar('amount');

		$share = $this->shareModel->find($shareID);
		$newShareValue = $share['value'] / $share['amount'];
		$value = $newShareValue * $amount;

		if ( $this->buyShare($value, $share['project'], $newShareValue, $share['owner'], $user)){
			$newAmount = $share['amount'] - $amount;
			if( $newAmount == 0 ){
				$this->shareModel->delete($shareID);
			}
			else{
				$this->shareModel->update($shareID, [
					"amount" => $newAmount,
					"value" => $newAmount * $newShareValue
				]);
			}

			$this->shareModel->insert([
				'value' => $value,
				'amount' => $amount,
				'owner' => $user['user_id'],
				'project' => $share['project'],
				'listed' => "no"
			]);
			return redirect()->to('share/listedshares');
		}
		else{
			return $this->getListedShares();
		}
	}

	//private function buyShare($purchaseValue, $share, $user){
	private function buyShare($purchaseValue, $projectID, $ShareValue ,$ownerID, $purchasingUser){

		if( $purchaseValue > $purchasingUser['credit'] ){
			$msg[] = ["type" => 'danger', 'text' => 'you do not have enough credit to complete this transaction'];
			$this->twig->addGlobal('msgs', $msg);
			return false;
		}

		//update user credit
		$newCredit = $purchasingUser['credit'] - $purchaseValue;
		$userModel = new UserModel();
		$userModel->update($purchasingUser['user_id'], ['credit' => $newCredit]);
		$this->session->set('user', $purchasingUser);

		//update project value
		$projectModel  = new ProjectModel();
		$project = $projectModel->find($projectID);

		if($ShareValue != $project['share_price']){
			$projectUpdate = [
				'share_price'=>$ShareValue,
				'total_value' => ($ShareValue * $project['total_shares'])
			];

			$projectModel->update($project['project_id'],$projectUpdate );
			$this->shareModel->builder()->where('project', $projectID)->where('listed', 'no')->set('value', "amount * {$ShareValue}", false)->update();
		}

		//update owner credit
		$userModel->builder()->where('user_id', $ownerID)->set('credit', "credit + {$purchaseValue}", false)->update();
		return true;
	}


	public function postSetOffer(){
		$shareID = $this->request->getVar("share");
		$user = $this->session->get("user");
		$share = $this->shareModel->find($shareID);

		$offerModel = new OfferModel();

		$offerModel->insert([
			"project" => $share['project'],
			"share" => $shareID,
			"original_owner" => $share['owner'],
			"offer_user" => $user['user_id'],
			"amount" => $this->request->getVar("amount"),
			"value" => $this->request->getVar("price"),
			"offer_date" => new Time('now')
		]);

		return redirect()->to('share/listedshares');
	}

	public function getMyOffers(){
		$user = $this->session->get("user");
		$offerModel = new OfferModel();

		$offers = $offerModel->where("offer_user", $user['user_id'])->withCompany()->findAll();

		$this->twig->display( 'my_offers', ["offers" => $offers] );
	}

	public function getRemoveOffer($offerID){
		$offerModel = new OfferModel();
		$offerModel->delete($offerID);
		$this->getMyOffers();
	}

	public function getOffers(){
		$user = $this->session->get("user");
		$offerModel = new OfferModel();

		$offers = $offerModel->where("original_owner", $user['user_id'])->withCompany()->findAll();

		$this->twig->display( 'offers', ["offers" => $offers] );
	}

	public function getAcceptOffer($offerID){
		$user = $this->session->get("user");

		$offerModel = new OfferModel();
		$userModel = new UserModel();
		$offer = $offerModel->find($offerID);
		$share = $this->shareModel->find($offer['share']);
		$purchaseUSer = $userModel->find($offer['offer_user']);
		$value = $offer['value'] / $offer['amount'];

		if ( $this->buyShare($offer['value'], $offer['project'], $value, $offer['original_owner'], $purchaseUSer ) ){
			$newAmount = $share['amount'] - $offer['amount'];
			if($newAmount == 0){
				$this->shareModel->delete($offer['share']);
			}
			else{
				$this->shareModel->update($offer['share'], [
					"amount" => $newAmount,
					"value" => $newAmount * $value
				]);
			}

			$this->shareModel->insert([
				'value' => $offer['value'],
				'amount' => $offer['amount'],
				'owner' => $offer['offer_user'],
				'project' => $offer['project'],
				'listed' => "no"
			]);
			$offerModel->delete($offerID);
			return redirect()->to('share/Offers');
		}
		else{
			return $this->getOffers();
		}


	}
}