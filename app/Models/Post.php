<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Post {
    public $id;
	public $naslov;
	public $sadrzaj;
	public $korisnik_id;
	public $slika_id;
        public $putanja;
    private $table = 'post';
    
    public function getAll(){
        // $rezultat = DB::select("SELECT * FROM post p INNER JOIN slika s ON p.slika_id = s.id INNER JOIN korisnik k ON p.korisnik_id = k.id");
        $rezultat = 
                DB::table($this->table)
                ->select('*', 'post.id AS postId')
                //->join('slika','slika.id','=','post.slika_id')
                ->join('user','user.id','=','post.created_by')
                ->paginate(2);
        return $rezultat;
    }
    
    public function get($id){
        $rezultat = 
                DB::table($this->table)
                ->select('*', 
                        'post.id AS postId',
                        'korisnik.username as postKorisnik',
                        'k.username as komentarKorisnik')
                ->join('slika','slika.id','=','post.slika_id')
                // Komentari se mogu dohvatati i posebnim upitom
                ->join('korisnik','korisnik.id','=','post.korisnik_id')
                ->leftJoin('komentar','post.id','=','komentar.post_id')
                ->leftJoin('korisnik AS k','k.id','=','komentar.korisnik_id')
                ->where('post.id','=',$id)
                ->first();
        return $rezultat;
    }
    
    public function save() {
		$rezultat = DB::table('post')->insert([
			'naslov' => $this->naslov,
			'sadrzaj' => $this->sadrzaj,
			'korisnik_id' => $this->korisnik_id,
			'created_at' => time(),
			'slika_id' => $this->slika_id
		]);
		return $rezultat;
	}
        public function update(){
		$data = [
			'naslov' => $this->naslov,
			'sadrzaj' => $this->sadrzaj,
			'korisnik_id' => $this->korisnik_id
		];
		
		if(!empty($this->slika)){ // ako je upload-ovana slika
			$data['putanja'] = $this->putanja;
		}

		$rez = DB::table('post')
		->where('id',$this->id)
				->update($data)
				;
		return $rez;
	}
}
