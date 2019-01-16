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
       
        $result = 
                DB::table($this->table)
                ->select('*','comment.id AS ID')
                ->join('user','user.id','=','comment.user_id')
                ->join('post','post.id','=','comment.post_id')
                ->get();
                
        return $result;
    }
    public function save() {
		$res = DB::table('comment')->insert([
			'text' => $this->text,
                        'post_id' => $this->post_id,
			'user_id' => $this->user_id
			
		]);
		return $res;
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
        $yes=1;
        $data=[
            'isAccepted'=>$da
        ];
        $res = DB::table('comment')
		->where('id',$this->id)
				->update($data)
				;
		return $res;
        
    }
}
