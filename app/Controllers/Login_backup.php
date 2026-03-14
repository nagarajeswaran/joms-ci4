<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Login Controller
 * 
 * Converted from CI3 Login.php
 * Date: 2026-02-10
 * 
 * INSTRUCTIONS:
 * Copy this file to: C:\programing\htdocs\joms-ci4\app\Controllers\Login.php
 */
class Login extends BaseController
{
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    /**
     * Login page
     */
    public function index()
    {
        // If already logged in, redirect to products
        if (session()->has('user')) {
            return redirect()->to('/products');
        }
        
        // Handle POST request (login form submission)
        if ($this->request->getMethod() === 'POST') {
            return $this->authenticate();
        }
        
        // Show login form
        return view('login/index');
    }
    
    /**
     * Authenticate user
     */
    public function authenticate()
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[3]',
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        
        // Attempt login
        $user = $this->userModel->login($username, $password);
        
        if ($user) {
            // Set session
            session()->set('user', $user);
            
            // Redirect to products
            return redirect()->to('/products');
        } else {
            // Login failed
            session()->setFlashdata('error', 'Invalid username or password');
            return redirect()->back()->withInput();
        }
    }
    
    /**
     * Logout
     */
    public function logout()
    {
        session()->remove('user');
        session()->destroy();
        
        return redirect()->to('/login');
    }
}
