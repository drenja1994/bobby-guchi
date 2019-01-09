<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\Meni;
use App\Models\Phone;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Slika;
use App\Models\Komentar;
use App\Models\Anketa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Session;

class AnketaController extends Controller {
    //put your code here
    private $data = [];
    
    public function __construct() {
        
        // Zadatak 1 - prikaz stavki menija
        // Pre svega - .env podesiti parametre za pristup bazi
        
        $meni = new Meni();
        $this->data['menus'] = $meni->getAll();
        
        // var_dump($this->data['menus']);
    }
    
    public function index(){
        $anketa = new Anketa();
        $this->data['ankete'] = $anketa->getAll();
        return view('pages.radAnketa', $this->data);
    }
    
    	public function destroy($id){
                    $ident = $id;
                    $anketa=new Anketa();
                   
		$rez = DB::table('anketa')
					->select('*')
					->where('anketa_id',$ident)
					->delete();
                $rez1 = DB::table('odgovori')
					->select('*')
					->where('anketa_id',$ident)
					->delete();
		
		if($rez == 1){
			return redirect('/ankete')->with('message','Uspesno brisanje ankete');
		}
		else {
			return redirect('/ankete')->with('message','Greška pri brisanju!');
		}
	}
        
            public function restart($id) {
		
		
		

		$anketa = new Anketa();
		$anketa->anketa_id = $id;

		

		$rez = $anketa->restart();
		
		if($rez != 2){ 
                    $restart=0;
                     $data2=[
            'glasao'=>$restart
        ];
        
        $rez2 = DB::table('korisnik')
				->update($data2)
				;
        \session()->get('user')->glasao=0;
        if($rez2!=2){
			return redirect('/ankete')->with('message','Uspešno ste restartovali anketu i glasove korisnika!');
		}
                else{return redirect('/ankete')->with('message','Greška!');}
                }
		else {
			return redirect('/ankete')->with('message','Greška!');
		}
	}
    
        public function aktiviraj($id){
            $aktiviraj=1;
            $aktiviraj0=0;
            $data=['anketa_aktivna'=>$aktiviraj0];
            $data2=['anketa_aktivna'=>$aktiviraj];
            $rez= DB:: table('anketa')->update($data);
             $ident = $id;
              $campaignId=$id;      
                   $phone= new Phone();
        $first= $phone->result_first();
        $second=$phone->result_second($campaignId);
        $third=$phone->result_third($campaignId);
        $fourth=$phone->result_fourth($campaignId);
        $firstfinal=$first->number;
        $secondfinal=$second->number;
        $thirdfinal=$third->number;
        $fourthfinal=$fourth->number;
        $datetime=Carbon::now();
        $finaldatetime=$datetime->toDateTimeString();
        $update= DB::table('phonenumbers')
                ->where("campaign_id", $campaignId)
                 ->update([
                'status' => 'IDLE',
                'updated_at' => $finaldatetime,
                'sch_no' => $firstfinal,
                'idl_no' => $secondfinal,
                'suc_no' => $thirdfinal,
                'tot_no' => $fourthfinal
            ]);
		$rez2 = DB::table('anketa')
					
					->where('anketa_id',$ident)
                                        ->update($data2);
                if($rez2==1){
                return redirect('/ankete')->with('message','Uspesno ste aktivirali anketu!');
                }
                else{return redirect('/ankete')->with('message','Greška!');}
                    
                   
        }
        
        public function update(Request $request){
            $glas=$request->get('glasanje');
            $glasovi=DB::table('odgovori')
                    ->select('*')
                    ->where('odgovor_id',$glas)
                    ->first();
            $novglas=$glasovi->glasovi +1;
            $data=['glasovi'=>$novglas];
            $rezultat=DB::table('odgovori')
                    ->where('odgovor_id',$glas)
                    ->update($data);
            
            $kident= \session()->get('user')->id;
            $glask=1;
            $data2=['glasao'=>$glask];
            $rezultat2=DB::table('korisnik')
                    ->where('id',$kident)
                    ->update($data2);
            \session()->get('user')->glasao=1;
            if($rezultat==1){return redirect('/')->with('message','Uspesno ste glasali!');}
            else{return redirect('/')->with('message','Greška!');}
        }
        
        public function reskorisnik(){
            
            $restart=0;
                     $data2=[
            'glasao'=>$restart
        ];
        
        $rez2 = DB::table('korisnik')
				->update($data2)
				;
        \session()->get('user')->glasao=0;
        if($rez2!=2){
			return redirect('/ankete')->with('message','Uspešno ste restartovali glasove korisnika!');
		}
                else{return redirect('/ankete')->with('message','Greška!');}
                }
        
    
            
            
        
  
}
