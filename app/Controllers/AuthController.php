<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Show registration form
     */
    public function register()
    {
        return view('auth/register');
    }

    /**
     * Process registration
     */
    public function processRegister()
    {
        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'name'     => 'required|min_length[3]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'name'     => $this->request->getPost('name'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        $userId = $this->userModel->insert($data);

        if ($userId) {
            // Set session
            session()->set([
                'user_id' => $userId,
                'user_email' => $data['email'],
                'user_name' => $data['name'],
                'logged_in' => true
            ]);

            return redirect()->to('/subscription/plans')->with('success', 'Registration successful! Please choose a subscription plan.');
        }

        return redirect()->back()->with('error', 'Registration failed. Please try again.');
    }

    /**
     * Show login form
     */
    public function login()
    {
        return view('auth/login');
    }

    /**
     * Process login
     */
    public function processLogin()
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $this->userModel->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            session()->set([
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'user_name' => $user['name'],
                'logged_in' => true
            ]);

            return redirect()->to('/dashboard')->with('success', 'Login successful!');
        }

        return redirect()->back()->with('error', 'Invalid email or password');
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'You have been logged out');
    }
}
