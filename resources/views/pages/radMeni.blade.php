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

        
<form action="{{ asset('/meni/update') }}" method="POST" enctype="multipart/form-data">
    {{ csrf_field() }}
            <table class="table">
                <tr>
                  <td>ID</td>
                  <td>Naziv</td>
                  <td>Link</td>
                  <td>Redosled</td>
                  <td>Novi redosled</td>
                  
                 
                </tr>
                
                @isset($menii)
                @foreach($menii as $meni)
                  <tr>
                    <td> {{ $meni->id }} </td>
                    <td> {{ $meni->naziv }} </td>
                    <td> {{ $meni->link }} </td>
                    <td> {{ $meni->red }} </td>
                    <td>
                        <select name="{{$meni->id}}">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </td>
                   
                  </tr>
                @endforeach
                @endisset
                
            </table>
    <div class="form-group">
                <input type="submit" name="redosledMenija" value="Promeni redosled" class="btn btn-default" />
              </div> 
        </div>
		
@endsection