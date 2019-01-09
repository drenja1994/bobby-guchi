<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class Meni {
    
    public function getAll(){
        $rezultat = DB::table("meni")
                ->select('*')
                ->orderBy('red', 'ASC')
                ->get();
        return $rezultat;
    }
    
    public function getAllid(){
        $rezultat = DB::table("meni")
                ->select('*')
                ->orderBy('id', 'ASC')
                ->get();
        return $rezultat;
    }
}
