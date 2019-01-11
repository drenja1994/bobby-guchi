<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class Menu {
    
    public function getAll(){
        $rezultat = DB::table("menu")
                ->select('*')
                ->orderBy('z_order', 'ASC')
                ->get();
        return $rezultat;
    }
    
    public function getAllid(){
        $rezultat = DB::table("menu")
                ->select('*')
                ->orderBy('id', 'ASC')
                ->get();
        return $rezultat;
    }
}
