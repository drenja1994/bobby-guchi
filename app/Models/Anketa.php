<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;


class Anketa {
    
    public function getAll(){
        $rezultat = DB::select("SELECT * FROM anketa");
        return $rezultat;
    }
      public function restart(){
        $restart=0;
        $data=[
            'glasovi'=>$restart
        ];
       
        $rez = DB::table('odgovori')
		->where('anketa_id',$this->anketa_id)
				->update($data)
				;
		return $rez;
        
    }
    public function getAllAk(){
        $rezultat = DB::table("anketa")
                ->select('*')
                ->where('anketa_aktivna', '1')
                ->first();
        return $rezultat;
    }
   public function odgovori(){
       
     $anketa = DB::table("anketa")
                ->select('*')
                ->where('anketa_aktivna', '1')
                ->first();
       
        $rezultat = 
                DB::table("odgovori")
                ->select('*')
                ->where('anketa_id', $anketa->anketa_id)
                
                
                
                ->get();
      
                
        return $rezultat;
    }
}
