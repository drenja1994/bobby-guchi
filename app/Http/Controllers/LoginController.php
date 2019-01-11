<?php

namespace App\Http\Controllers;

use App\Models\Korisnik;
use App\Models\User;
use Dotenv\Exception\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $userModel = new UserModel();
        $userModel->username = $request->get("username");
        $userModel->password = $request->get("password");
        $user = $userModel->login();
        if ($user) {
            $request->session()->put('user', $user);
            return redirect(route("home"));
        } else {
             return redirect()->back()->with('alert', 'Infromation you entered is not valid!');
        }
    }

    public function register(Request $request)
    {
        
        $rules = [
            'korisnicko_ime' => 'required|alpha|min:2|max:20',
            'prezime' => 'required|alpha|min:2|max:20',
            'email' => 'required|email|unique:korisnik',
            'password' => [
                'required',
                'confirmed',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,}$/'
            ]
        ];

        
        $messages = [
            "password.regex" => 'Šifra mora sadržati jedno veliko slovo i jedan broj.'
        ];

        
        $validator = \Validator::make($request->all(), $rules, $messages);
        $validator->validate();

        

        $userModel = new UserModel();
        $userModel->korisnicko_ime = $request->get('korisnicko_ime');
        $userModel->prezime = $request->get("prezime");
        $userModel->email = $request->get('email');
        $userModel->password = $request->get("password");
        $userModel->username = $request->get("username");
        $userModel->uloga_id = "2";

        try {
            $userModel->insert();
            return redirect()->back()->with("success", "Uspešno ste se registrovali.");
        } catch(QueryException $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with("error", "Greska sa serverom!.");
        }
    }

    public function logout()
    {
        session()->forget('user');
        return redirect(route("home"));
    }
    
}
