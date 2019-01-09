<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class Slika {

	public $id;
	public $alt;
	public $putanja;

	public function save(){
		$id = DB::table('slika')
				->insertGetId([
					'alt' => $this->alt,
					'putanja' => $this->putanja
				]);
		return $id;
	}
}