<?php

namespace App\Http\Controllers;
use Validator;
use App\Models\Meni;
use App\Models\Post;
use App\Models\Slika;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class PostController extends Controller {

	private $data = [];

    public function __construct(){
    	$meni = new Meni();
    	$this->data['menus'] = $meni->getAll();
    }

	public function create($id=null){
		// 1 - Prikaz formulara
            $post = new Post();
        $this->data['posts'] = $post->getAll();
        if(!empty($id)){
			$ident = $id;
			$this->data['post'] = DB::table('post')
					->select('*', 'post.id AS postId')
                                        ->join('slika','slika.id','=','post.slika_id')
                                        ->join('korisnik','korisnik.id','=','post.korisnik_id')
					->where('post.id',$ident)
                                        ->get();
					
		}
		return view('pages.createPost', $this->data);
	}

	public function store(Request $request) {

		// 2 - Validacija unosa

		$rules = [
			'title' => 'regex:/^.{6,}$/',
			'body' => 'required',
			'photo' => 'required|mimes:jpg,jpeg,png,gif|max:3000',
			'alt' => 'required'
		];
		$custom_messages = [
			'required' => 'Polje :attribute je obavezno!',
			'title.regex' => 'Polje title nije u ispravnom formatu!',
			'max' => 'Fajl ne sme biti veci od :max KB.',
			'mimes' => 'Dozvoljeni formati su: :values.'
		];
		$request->validate($rules, $custom_messages);
		
		/*
			 Kod ce se dalje izvrsavati jedino ako prodje validacija!
		*/


		// 3 - Dohvatanje informacija o slici

		$photo = $request->file('photo');
		$extension = $photo->getClientOriginalExtension();
		$tmp_path = $photo->getPathName();
		
		$folder = 'images/';
		$file_name = time().".".$extension;
		$new_path = public_path($folder).$file_name;
                    $kident= \session()->get('user')->id;
		try {
			// 4 - Upload slike na server

			File::move($tmp_path, $new_path);

			// 5 - Unos slike u bazu

			$slika = new Slika();
			$slika->alt = trim($request->get('alt'));
			$slika->putanja = 'images/'.$file_name;
			$slika_id = $slika->save();

			// 6 - Unos posta u bazu
			$post = new Post();
			$post->naslov = $request->get('title');
			$post->sadrzaj = $request->get('body');
			$post->korisnik_id = $kident;
			$post->slika_id = $slika_id;
			$post->save();

			// Ako se ne desi nijedan Exception - otici ce na pocetnu stranu!
			return redirect('/create')->with('message','Uspešno ste dodali predstavu i sliku!');
		}
		catch(\Illuminate\Database\QueryException $ex){ // greske u upitu
			\Log::error($ex->getMessage());
			return redirect()->back()->with('error','Greška pri dodavanju predstave u bazu!');
		}
		catch(\Symfony\Component\HttpFoundation\File\Exception\FileException $ex) { // greske sa fajlom
			\Log::error('Problem sa fajlom!! '.$ex->getMessage());
			return redirect()->back()->with('error','Greška pri dodavanju slike!');
		}
		catch(\ErrorException $ex) { 
			\Log::error('Problem sa fajlom!! '.$ex->getMessage());
			return redirect()->back()->with('error','Desila se greška..');
		} 
	}
        
  
        
        	public function destroy($id){
                    $ident = $id;
                    $post=new Post();
                    $post_to_update = $post->get($ident);
		File::delete($post_to_update->putanja);
		$rez = DB::table('post')
					->select('*')
					->where('id',$ident)
					->delete();
                
		
		if($rez == 1){
			return redirect('/create')->with('message','Uspešno ste obrisali predstavu!');
		}
		else {
			return redirect('/create')->with('message','Greška pri brisanju!');
		}
	}
}