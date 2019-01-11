<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class Role {

	public function getAll(){
		$rezultat = DB::table('role')->get();
		return $rezultat;
	}
        
        public function getRolesByUser($user_id){
		$rezultat = DB::table('user_role')->get();
		return $rezultat;
	}
        
        
}
