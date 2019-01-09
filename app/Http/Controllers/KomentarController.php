<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\Meni;
use App\Models\Post;
use App\Models\Slika;
use App\Models\Komentar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class KomentarController extends Controller {
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
        $komentar = new Komentar();
        $this->data['komentari'] = $komentar->getAll();
        return view('pages.radKomentari', $this->data);
    }
    
 public function store($id, Request $request){

		

		$ident=$id;
		$kident= \session()->get('user')->id;
		

		try {
			

			

			$komentar = new Komentar();
			$komentar->korisnik_id = $kident;
			$komentar->post_id = $ident;
			$komentar->tekst = $request->get('komentarisanje');
                        

			$rez = $komentar->save();
			
			if($rez == 1){
				return redirect( asset('/posts/'.$ident) )->with('message','Uspešan unos komentara, biće prikazan ako ga administrator odobri!');
			}
			else {
				return redirect('/')->with('message','Greška pri unosu koemntara!');
			}
		}
		catch (\Exception $ex){
			\Log::error('MOJA GRESKA: '.$ex->getMessage());
		}
	}
        	public function destroy($id){
                    $ident = $id;
                    $komentar=new Komentar();
                   
		$rez = DB::table('komentar')
					->select('*')
					->where('id',$ident)
					->delete();
                
		
		if($rez == 1){
			return redirect('/komentari')->with('message','Uspešno ste obrisali komentar!');
		}
		else {
			return redirect('/komentari')->with('message','Greška pri brisanju!');
		}
	}
        public function update($id) {
		
		
		

		$komentar = new Komentar();
		$komentar->id = $id;

		

		$rez = $komentar->prihvatanje();
		
		if($rez == 1){ 
			return redirect('/komentari')->with('message','Prikaz komentara je odobren!');
		}
		else {
			return redirect('/komentari')->with('message','Greška!');
		}
	}
  
}
