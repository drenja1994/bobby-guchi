<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class Korisnik
{
	public $id;
	public $korisnicko_ime;
        public $prezime;
	public $password;
	public $username;
        public $email;
	public $uloga_id;

	public function getAll(){
		$rezultat = DB::table('korisnik')
					->select('*', 'korisnik.id AS ID')
					->join('uloga','uloga.id','=','korisnik.uloga_id')
					->get();
		return $rezultat;
	}

	public function get() {
		$rezultat = DB::table('korisnik')
					->select('*')
					->where('id',$this->id)
					->first();
		return $rezultat;
	}

	public function save() {
		$rez = DB::table('korisnik')->insert([
			'korisnicko_ime' => $this->korisnicko_ime,
                        'prezime' => $this->prezime,
			'password' => md5($this->password),
			'username' => $this->username,
                        'email' => $this->email,
			'uloga_id' => $this->uloga_id
		]);
		return $rez;
	}

	public function update(){
		$data = [
			'korisnicko_ime' => $this->korisnicko_ime,
			'prezime' => $this->prezime,
			'password' => md5($this->password),
			'username' => $this->username,
                        'email' => $this->email,
			'uloga_id' => $this->uloga_id
		];
		
		

		$rez = DB::table('korisnik')
		->where('id',$this->id)
				->update($data)
				;
		return $rez;
	}

	public function delete(){
		$rezultat = DB::table('korisnik')
					->where('id', $this->id)
					->delete();
		return $rezultat;
	}
          public function login()
    {
        return \DB::table('korisnik')
            ->where([
                ['username', '=', $this->username],
                ['password', '=',md5($this->password)]
            ])->select("korisnik.*")
            ->get()->first();
    }
}
