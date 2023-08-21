<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Psr\Log\LoggerInterface;

class Project extends BaseController
{

	private $projectModel = null;


	public function initController(
		RequestInterface $request,
		ResponseInterface $response,
		LoggerInterface $logger
	) {
		parent::initController($request, $response, $logger);

		$this->projectModel = new \App\Models\ProjectModel();
	}


	public function getAddCompany(){
		$this->twig->display( 'add_company' );
	}

	public function postAddCompany(){
		$error = false;
		$msgs = [];
		$user = $this->session->get('user');

		$company = [
			"company" => $this->request->getVar('company'),
			"country" => $this->request->getVar('country'),
			"date" => $this->request->getVar('date'),
			"total_value" => $this->request->getVar('total_value'),
			"total_shares" => $this->request->getVar('total_shares'),
			"listed_shares" => $this->request->getVar('listed_shares'),
			"share_price" => $this->request->getVar('total_value') / $this->request->getVar('total_shares'),
			"lister" => $user['user_id'],
			"listdate" => new Time('now'),
			"status" => "validating",
			"progress" => "1",
			"rate" => "0",
		];

		$financial = $this->request->getFile('financial');
		if( $financial->isValid() ){
			$financialPath = $financial->store();
			$company["financial"] = $financialPath;
		}
		else{
			$error = true;
			$msgs[] = ["type" => 'danger', 'text' => 'error uploading financial file'];
		}


		$docs = $this->request->getFile('docs');
		if( $docs->isValid() ){
			$docsPath = $docs->store();
			$company["docs"] = $docsPath;
		}
		else{
			$error = true;
			$msgs[] = ["type" => 'danger', 'text' => 'error uploading document file'];
		}

		$certificate = $this->request->getFile('certificate');
		if( $certificate->isValid() ){
			$certificatePath = $certificate->store();
			$company["certificate"] = $certificatePath;
		}
		else{
			$error = true;
			$msgs[] = ["type" => 'danger', 'text' => 'error uploading certificate file'];
		}

		if($error){
			$this->twig->addGlobal('msgs', $msgs);
			$this->twig->display( 'add_company', ["company" => $company] );
		}
		else{
			$this->projectModel->insert( $company);
			return redirect()->to('project/myCompanies');
		}


	}

	public function getMyCompanies(){
		$filter = $this->request->getVar('filter');
		$user = $this->session->get('user');

		$companies = $this->projectModel->where('lister', $user["user_id"]);
		if($filter){
			$companies->where('status', $filter);
		}
		$companies = $companies->findAll();

		$this->twig->display( 'my_companies', ["companies" => $companies] );
	}

	public function getAdminCompanies(){
		$filter = $this->request->getVar('filter');

		$companies = $this->projectModel->withLister();
		if($filter){
			$companies->where('status', $filter);
		}
		$companies = $companies->findAll();

		$this->twig->display( 'admin_companies', ["companies" => $companies] );
	}

	public function postSetProgress(){
		$this->projectModel->update($this->request->getVar('company'), ["progress" =>  $this->request->getVar('progress')]);
		return redirect()->to('project/adminCompanies');
	}

	public function getValidate($companyID){
		$company = $this->projectModel->find($companyID);
		$this->projectModel->update($companyID, ["status" =>  "listed"]);

		$this->shareModel = new \App\Models\ShareModel();
		$this->shareModel->insert([
			'value' => $company['share_price'] * $company['listed_shares'],
			'amount' => $company['listed_shares'],
			'owner' => $company['lister'],
			'project' => $companyID,
			'listed' => "no"
		]);

		return redirect()->to('project/adminCompanies');
	}

}