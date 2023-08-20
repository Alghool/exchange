<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
	protected $table      = 'users';
	protected $primaryKey = 'user_id';

	protected $useAutoIncrement = true;

	protected $returnType     = 'array';
	protected $useSoftDeletes = false;

	protected $allowedFields = ['name', 'email', 'password', 'type', 'id', 'address', 'phone', 'birthday', 'country', 'country', 'citizenship', 'proof_id', 'proof_address', 'credit', 'profile_complete'];

//	// Dates
//	protected $useTimestamps = false;
//	protected $dateFormat    = 'datetime';
//	protected $createdField  = 'created_at';
//	protected $updatedField  = 'updated_at';
//	protected $deletedField  = 'deleted_at';
//
//	// Validation
//	protected $validationRules      = [];
//	protected $validationMessages   = [];
//	protected $skipValidation       = false;
//	protected $cleanValidationRules = true;
//
//	// Callbacks
//	protected $allowCallbacks = true;
//	protected $beforeInsert   = [];
//	protected $afterInsert    = [];
//	protected $beforeUpdate   = [];
//	protected $afterUpdate    = [];
//	protected $beforeFind     = [];
//	protected $afterFind      = [];
//	protected $beforeDelete   = [];
//	protected $afterDelete    = [];

}