<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class Uloga {

	public function getAll(){
		$rezultat = DB::table('uloga')->get();
		return $rezultat;
	}
}
