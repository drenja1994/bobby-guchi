<?php

namespace App\Http\Controllers;
use App\Models\Meni;
use App\Models\Uloga;
use App\Models\Korisnik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class KorisnikController extends Controller {

	private $data = [];

	public function __construct() {
		$uloga = new Uloga();
		$this->data['uloge'] = $uloga->getAll();
                $meni = new Meni();
                $this->data['menus'] = $meni->getAll();
	}

	public function show($id = null){
		$korisnik = new Korisnik();
		$this->data['korisnici'] = $korisnik->getAll();

		if(!empty($id)){
			$korisnik->id = $id;
			$this->data['korisnik'] = $korisnik->get();
		}

		return view('pages.createKorisnik', $this->data);
	}

	public function store(Request $request){

		$request->validate([
			'username' => 'unique:korisnik,username',
			'ddlUloga' => 'required|not_in:0',
			
		]);

		$korisnicko_ime = $request->get('korisnicko_ime');
		$password = $request->get('password');
                $username = $request->get('username');
                $email = $request->get('email');
                $prezime = $request->get('prezime');
		$uloga_id = $request->get('ddlUloga');
		
		

		try {
			


			$korisnik = new Korisnik();
			$korisnik->korisnicko_ime = $korisnicko_ime;
			$korisnik->password = $password;
			$korisnik->username = $username;
                        $korisnik->prezime = $prezime;
                        $korisnik->email = $email;
			$korisnik->uloga_id = $uloga_id;

			$rez = $korisnik->save();
			
			if($rez == 1){
				return redirect('/users')->with('message','Uspešan unos korisnika!');
			}
			else {
				return redirect('/users')->with('message','Greška pri unosu!');
			}
		}
		catch (\Exception $ex){
			\Log::error('MOJA GRESKA: '.$ex->getMessage());
		}
	}

	public function update($id, Request $request) {
		$korisnicko_ime = $request->get('korisnicko_ime');
                $prezime = $request->get('prezime');
                $username = $request->get('username');
		$password = $request->get('password');
                $email = $request->get('email');
		$uloga_id = $request->get('ddlUloga');
		
		

		$korisnik = new Korisnik();
		$korisnik->id = $id;
		$korisnik->korisnicko_ime = $korisnicko_ime;
                $korisnik->prezime = $prezime;
                $korisnik->username = $username;
		$korisnik->password = $password;
                $korisnik->email = $email;
		$korisnik->uloga_id = $uloga_id;

		

		$rez = $korisnik->update();
		
		if($rez == 1){ 
			return redirect('/users')->with('message','Uspešan update korisnika!');
		}
		else {
			return redirect('/users')->with('message','Greška pri update-u!');
		}
	}

	public function destroy($id){
		$korisnik = new Korisnik();
		$korisnik->id = $id;
                

		$rez = $korisnik->delete();
		if($rez == 1){
			return redirect('/users')->with('message','Uspesan delete!');
		}
		else {
			return redirect('/users')->with('message','Greska pri delete-u!');
                }
	}
}