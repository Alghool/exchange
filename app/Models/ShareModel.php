<?php

namespace App\Models;

use CodeIgniter\Model;

class ShareModel extends Model
{
	protected $table      = 'shares';
	protected $primaryKey = 'share_id';

	protected $useAutoIncrement = true;

	protected $returnType     = 'array';
	protected $useSoftDeletes = false;

	protected $allowedFields = ['value', 'owner', 'project', 'amount', 'listed'];

	public function withCompany(){
		$this->builder()->join('projects','shares.project = projects.project_id', 'left');
		return $this;
	}
}