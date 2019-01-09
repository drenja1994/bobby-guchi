<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\Meni;
use App\Models\Post;
use App\Models\Slika;
use App\Models\Komentar;
use App\Models\Anketa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class MeniController extends Controller {
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
        $meni = new Meni();
        $this->data['menii'] = $meni->getAllid();
        return view('pages.radMeni', $this->data);
    }
    
    public function update(Request $request){

		

		$prvi = $request->get('1');
		$drugi = $request->get('2');
                $treci = $request->get('3');
                $cetvrti = $request->get('4');
                
                if($prvi <> $drugi && $prvi <> $treci && $prvi <> $cetvrti){
                    if($drugi <> $treci && $drugi<> $cetvrti){
                        if($treci <> $cetvrti){
                
                $id1=1;
                $id2=2;
                $id3=3;
                $id4=4;
               


			$data1=[
                          'red'=>$prvi 
                        ];
			

			$rez1 = DB::table('meni')
		->where('id',$id1)
				->update($data1)
				;
                        
                        $data2=[
                          'red'=>$drugi 
                        ];
			

			$rez2 = DB::table('meni')
		->where('id',$id2)
				->update($data2)
				;
                        
                        $data3=[
                          'red'=>$treci 
                        ];
			

			$rez3 = DB::table('meni')
		->where('id',$id3)
				->update($data3)
				;
                        
                        $data4=[
                          'red'=>$cetvrti 
                        ];
			

			$rez4 = DB::table('meni')
		->where('id',$id4)
				->update($data4)
				;
                        
                        
			
			if($rez1 == 1 || $rez1== 0 && $rez2 == 1 || $rez2== 0 && $rez3 == 1 || $rez3== 0 && $rez4 == 1 || $rez4== 0 ){
				return redirect('/meni')->with('message','Uspešan update redosleda!');
			}
			else {
				return redirect('/meni')->with('message','Greška pri updatu!');
			}
                    }
                }
                }
                else{return redirect('/meni')->with('message','Greška pri updatu, izabrane vrednosti moraju biti različite!');}
    }
    
		
		
	}
