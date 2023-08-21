<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectModel extends Model
{
	protected $table      = 'projects';
	protected $primaryKey = 'project_id';

	protected $useAutoIncrement = true;

	protected $returnType     = 'array';
	protected $useSoftDeletes = false;

	protected $allowedFields = ['company', 'country', 'date', 'total_value', 'total_shares', 'listed_shares', 'status', 'listdate', 'lister', 'financial', 'docs', 'certificate', 'progress', 'rate', 'share_price'];

	public function withLister(){
		$this->builder()->join('users', 'projects.lister = users.user_id', 'left');
		return $this;
	}
}