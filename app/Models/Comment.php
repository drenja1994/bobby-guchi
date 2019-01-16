<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
 
class Comment {
     public $id;
	public $text;
	public $post_id;
	public $user_id;
	
    private $table = 'comment';
      public function getAll(){
       
        $rezultat = 
                DB::table($this->table)
                ->select('*','komentar.id AS ID')
                ->join('korisnik','korisnik.id','=','komentar.korisnik_id')
                ->join('post','post.id','=','komentar.post_id')
                ->get();
                
        return $rezultat;
    }
    public function save() {
		$rez = DB::table('comment')->insert([
			'text' => $this->text,
                        'post_id' => $this->post_id,
			'user_id' => $this->user_id
			
		]);
		return $rez;
	}
          public function compost($id){
       $ident=$id;
        $result = 
                DB::table($this->table)
                ->select('*','comment.id AS ID')
                ->join('user','user.id','=','comment.user_id')
                ->join('post','post.id','=','comment.post_id')
                ->where('post_id','=',$ident)
                ->where('isAccepted','=',1)
                ->get();
                
        return $result;
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
