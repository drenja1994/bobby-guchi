<?php

namespace App\Http\Controllers;

use App\Models\Meni;
use App\Models\Post;
use App\Models\Komentar;
use App\Models\Anketa;

class FrontendController extends Controller {
    
    private $data = [];
    
    public function __construct() {
        
        
        
        $meni = new Meni();
        $this->data['menus'] = $meni->getAll();
        
        
    }
    
    public function index(){
        $post = new Post();
        $this->data['posts'] = $post->getAll();
        $anketa= new Anketa();
        $this->data['ankete']= $anketa->getAllAk();
        $odgovori= new Anketa();
        $this->data['odgovori']= $odgovori->odgovori();
        return view('pages.home', $this->data);
    }
    
    public function getPost($id){
        $post = new Post();
        $this->data['singlePost'] = $post->get($id);
        $komentar = new Komentar();
        $this->data['komentari']=$komentar->kompost($id);
        return view('pages.post', $this->data);
    }
    
    
      public function registracija(){
        $post = new Post();
        $this->data['posts'] = $post->getAll();
        return view('pages.registracija', $this->data);
    }
     public function logovanje(){
        $post = new Post();
        $this->data['posts'] = $post->getAll();
        return view('pages.login', $this->data);
    }
    public function autor(){
        $post = new Post();
        $this->data['posts'] = $post->getAll();
        return view('pages.autor', $this->data);
    }
  
}
