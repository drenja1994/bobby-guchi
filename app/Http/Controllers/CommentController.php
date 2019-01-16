<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\Menu;
use App\Models\Post;
use App\Models\Slika;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller {
    //put your code here
    private $data = [];
    
    public function __construct() {
        
        // Zadatak 1 - prikaz stavki menija
        // Pre svega - .env podesiti parametre za pristup bazi
        
        $menu = new Menu();
        $this->data['menus'] = $menu->getAll();
        
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
			

			

			$komentar = new Comment();
			$komentar->user_id = $kident;
			$komentar->post_id = $ident;
			$komentar->text = $request->get('commentText');
                        

			$rez = $komentar->save();
			
			if($rez == 1){
				return redirect( asset('/posts/'.$ident) )->with('message','You have succesfuly added a comment, it will show on page if admins verify it!');
			}
			else {
				return redirect('/')->with('message','Error upon entering comment!');
			}
		}
		catch (\Exception $ex){
			\Log::error('Error: '.$ex->getMessage());
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
