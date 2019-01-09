@extends('layouts.front')

@section('title')
    Rad sa anketama
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
@endsection

@section('content')

        <div class="col-md-8">
            <h3>Rad sa anketama</h3>
            
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
            <br/><br/>

            <a href="{{ asset('/ankete/reskor/') }}" style="color:red;">Restartujte glasove korisnika</a>

            <table class="table">
                <tr>
                  <td>ID</td>
                  <td>Pitanje</td>
                  <td></td>
                 
                  <td></td>
                  <td></td>
                  
                 
                </tr>
                
                @isset($ankete)
                @foreach($ankete as $anketa)
                  <tr>
                    <td> {{ $anketa->anketa_id }} </td>
                    <td> {{ $anketa->anketa_pitanje }} </td>
                   
                   @if ($anketa->anketa_aktivna !=1)
                    <td> <a style="color:green;" href="{{ asset('/ankete/aktiviraj/'.$anketa->anketa_id) }}">Aktiviraj</a> </td>
                    @endif
                    
                     @if ($anketa->anketa_aktivna !=0)
                    <td> <a href="{{ asset('/ankete/restart/'.$anketa->anketa_id) }}" style="color:red;">Restartuj</a> </td>
                     @endif
                     @if ($anketa->anketa_aktivna !=1)
                    <td> <a href="{{ asset('/ankete/destroy/'.$anketa->anketa_id) }}">Obrisi</a> </td>
                    @endif
                  </tr>
                @endforeach
                @endisset
            </table>
        </div>
		
@endsection