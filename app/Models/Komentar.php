<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
 
class Komentar {
     public $id;
	public $tekst;
	public $post_id;
	public $korisnik_id;
	
    private $table = 'komentar';
      public function getAll(){
        // $rezultat = DB::select("SELECT * FROM post p INNER JOIN slika s ON p.slika_id = s.id INNER JOIN korisnik k ON p.korisnik_id = k.id");
        $rezultat = 
                DB::table($this->table)
                ->select('*','komentar.id AS ID')
                ->join('korisnik','korisnik.id','=','komentar.korisnik_id')
                ->join('post','post.id','=','komentar.post_id')
                ->get();
                
        return $rezultat;
    }
    public function save() {
		$rez = DB::table('komentar')->insert([
			'tekst' => $this->tekst,
                        'post_id' => $this->post_id,
			'korisnik_id' => $this->korisnik_id
			
		]);
		return $rez;
	}
          public function kompost($id){
       $ident=$id;
        $rezultat = 
                DB::table($this->table)
                ->select('*','komentar.id AS ID')
                ->join('korisnik','korisnik.id','=','komentar.korisnik_id')
                ->join('post','post.id','=','komentar.post_id')
                ->where('post_id','=',$ident)
                ->where('prihvatio','=',1)
                ->get();
                
        return $rezultat;
    }
    
    public function prihvatanje(){
        $da=1;
        $data=[
            'prihvatio'=>$da
        ];
        $rez = DB::table('komentar')
		->where('id',$this->id)
				->update($data)
				;
		return $rez;
        
    }
}
