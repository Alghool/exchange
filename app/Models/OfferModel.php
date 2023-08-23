<?php

namespace App\Models;

use CodeIgniter\Model;

class OfferModel extends Model
{
	protected $table      = 'offers';
	protected $primaryKey = 'offer_id';

	protected $useAutoIncrement = true;

	protected $returnType     = 'array';
	protected $useSoftDeletes = false;

	protected $allowedFields = [ 'project', 'share', 'original_owner', 'offer_user', 'amount', 'value', 'offer_date'];

	public function withCompany(){
		$this->builder()->join('projects','offers.project = projects.project_id', 'left');
		return $this;
	}
}