@extends('layouts.front')

@section('title')
    Rad sa komentarima
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
@endsection

@section('content')

        <div class="col-md-8">
            <h3>Rad sa komentarima</h3>
            
            @empty(!session('message'))
              {{ session('message') }}
            @endempty

            @isset($errors)
              @if($errors->any())
                @foreach($errors->all() as $error)
                  <div class="alert alert-danger"> {{ $error }} </div>
                @endforeach
              @endif
            @endisset

        

            <table class="table">
                <tr>
                  <td>ID</td>
                  <td>Tekst</td>
                  <td>Post</td>
                  <td>Korisnik</td>
                  <td></td>
                  <td></td>
                 
                </tr>
                
                @isset($komentari)
                @foreach($komentari as $komentar)
                  <tr>
                    <td> {{ $komentar->id }} </td>
                    <td> {{ $komentar->tekst }} </td>
                    <td> {{ $komentar->naslov }} </td>
                    <td> {{ $komentar->username }} </td>
                   @if ($komentar->prihvatio !=1)
                    <td> <a href="{{ asset('/komentari/update/'.$komentar->ID) }}">Prihvati</a> </td>
                    @endif
                    <td> <a href="{{ asset('/komentari/destroy/'.$komentar->ID) }}">Obrisi</a> </td>
                  </tr>
                @endforeach
                @endisset
            </table>
        </div>
		
@endsection