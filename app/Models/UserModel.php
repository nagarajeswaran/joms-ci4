<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'username',
        'password',
        'email',
        'name',
        'role',
        'modules',
        'status',
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[50]|is_unique[user.username]',
        'password' => 'required|min_length[6]',
        'email'    => 'required|valid_email|is_unique[user.email]',
    ];
    
    protected $validationMessages = [
        'username' => [
            'required' => 'Username is required',
            'is_unique' => 'Username already exists',
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please provide a valid email',
            'is_unique' => 'Email already exists',
        ],
    ];
    
    public function login($username, $password)
    {
        $user = $this->where('username', $username)->first();

        if (!$user && $this->db->fieldExists('name', $this->table)) {
            $user = $this->where('name', $username)->first();
        }
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        
        if (md5($password) === $user['password']) {
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    public function getUserByUsername($username)
    {
        return $this->where('username', $username)->first();
    }
    
    public function getUserByEmail($email)
    {
        return $this->where('email', $email)->first();
    }
    
    public function createUser($data)
    {
        if (isset($data['password']) && strlen($data['password']) < 60) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->insert($data);
    }
    
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->update($userId, ['password' => $hashedPassword]);
    }
}
