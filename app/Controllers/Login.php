<?php

namespace App\Controllers;

class Login extends BaseController
{
    public function index()
    {
        // If already logged in, redirect
        if (session()->get('logged_in')) {
            return redirect()->to('/joms-ci4/public/products');
        }
        
        // Handle POST (form submission)
        if ($this->request->getMethod() === 'post') {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');
            
            // Simple hardcoded check for testing
            if ($username === 'admin' && $password === 'admin') {
                session()->set([
                    'logged_in' => true,
                    'username' => $username,
                    'user_id' => 1
                ]);
                
                return redirect()->to('/joms-ci4/public/products');
            }
            
            // Try database check (with timeout protection)
            try {
                $db = \Config\Database::connect();
                $builder = $db->table('user');
                $user = $builder->where('username', $username)->get()->getRowArray();
                
                if ($user && password_verify($password, $user['password'])) {
                    session()->set([
                        'logged_in' => true,
                        'username' => $username,
                        'user_id' => $user['id']
                    ]);
                    return redirect()->to('/joms-ci4/public/products');
                }
            } catch (\Exception $e) {
                // Database error, skip
            }
            
            // Login failed
            return redirect()->back()->with('error', 'Invalid username or password');
        }
        
        // Show login form
        return view('login/index');
    }
    
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/joms-ci4/public/login');
    }
}
