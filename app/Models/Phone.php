<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class Phone {
    
     public function result_first($id){
        $sch=DB::table("schedulations")
                ->select("_id")
                ->where('campaign_id','=',$id)
                ->orderBy('schedulation_date','desc')
                ->take(1)
                ->first();
        $result = DB::table("phonenumbers")
                ->select(DB::raw('COUNT(*) as number'))
                ->where('status','=','IN_PROGRESS')
                ->where('schedulation_id','=',$sch->_id)
                ->first();
        return $result;
    }
    
    public function result_second($id){
        $result = DB::table("phonenumbers")
                ->select(DB::raw('COUNT(*) as number'))
                ->where('status','=','IDLE')
                ->where('campaign_id','=',$id)
                ->first();
        return $result;
    }
    
    public function result_third($id){
        $result = DB::table("phonenumbers")
                ->select(DB::raw('COUNT(*) as number'))
                ->where('status','=','SUCCEED')
                ->where('campaign_id','=',$id)
                ->first();
        return $result;
    }
    public function result_fourth($id){
        $result = DB::table("phonenumbers")
                ->select(DB::raw('COUNT(*) as number'))
                ->where('status','=','IDLE')
                ->where('campaign_id','=',$id)
                ->first();
        return $result;
    }
}


