<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Log;

class AdminAuthController extends Controller
{
    public function index(){
        if (Auth::guard('admin')->check()) {
            return redirect('/admin/dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request){
        $credentials = $request->only('email', 'password');
    
        if (Auth::guard('admin')->attempt($credentials)) {
            $user = Auth::guard('admin')->user();
            
            if ($user->role_id === 1 || $user->role_id === 4 || $user->role_id = 2) {
                
                $log = new Log;
                $log->message = $user->first_name . " " . $user->last_name . " successfully logged into the admin panel.";
                $log->role_name = 'Admin';
                $log->save();
                
                return redirect()->intended('/admin/dashboard');
            }
    
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')->with('error', 'Invalid credentials');
        }
        return redirect()->route('admin.login')->with('error', 'Invalid credentials');
    }

    public function logout(){
        $user = Auth::guard('admin')->user();
        
        $log = new Log;
        $log->message = $user->first_name . " " . $user->last_name . " has logged out of the admin panel successfully.";
        $log->role_name = 'Admin';
        $log->save();
        
        Auth::guard('admin')->logout();
        return redirect('/admin/login');
    }
}
