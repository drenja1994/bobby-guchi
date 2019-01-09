@extends('layouts.front')

@section('title')
    Rad sa korisnicima
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
@endsection

@section('content')

        <div class="col-md-8">
            
            <h3>Add korisnik</h3>
            
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

            <form action="{{ (isset($korisnik))? asset('/users/update/'.$korisnik->id)  : asset('/users/store') }}" method="POST" enctype="multipart/form-data">
              
              {{ csrf_field() }}
              
              <div class="form-group">
                <label>Ime:</label>
                <input type="text" name="korisnicko_ime" class="form-control" value="{{ (isset($korisnik))? $korisnik->korisnicko_ime : old('korisnicko_ime') }}"/>
              </div>
               <div class="form-group">
                <label>Prezime:</label>
                <input type="text" name="prezime" class="form-control" value="{{ (isset($korisnik))? $korisnik->prezime : old('prezime') }}"/>
              </div> 
                            <div class="form-group">
                <label>Korisničko ime:</label>
                <input type="text" name="username" class="form-control" value="{{ (isset($korisnik))? $korisnik->username : old('username') }}"/>
              </div>
              <div class="form-group">
                <label>Lozinka:</label>
                <input type="password" name="password" class="form-control" value="{{ (isset($korisnik))? $korisnik->password : old('password') }}"/>
              </div> 
              <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" value="{{ (isset($korisnik))? $korisnik->email : old('email') }}"/>
              </div> 
              <div class="form-group">
                <label>Uloga:</label>
                <select name="ddlUloga">
                  <option value="0">Izaberite</option>
                  
                  @foreach($uloge as $uloga)
                    <option value="{{ $uloga->id }}" {{ (isset($korisnik) && $korisnik->uloga_id == $uloga->id)? 'selected' : (old('ddlUloga')==$uloga->id)? 'selected' : '' }} > {{ $uloga->naziv }} </option>
                  @endforeach

                </select>
              </div>
              <div class="form-group">
                <input type="submit" name="addKorisnik" value="{{ (isset($korisnik))? 'Change korisnik' : 'Add korisnik' }} " class="btn btn-default" />
              </div> 
            </form>

            <table class="table">
                <tr>
                  <td>ID</td>
                  <td>Ime</td>
                  <td>Prezime</td>
                  <td>Korisničko ime</td>
                  <td>Email</td>
                  <td>Uloga</td>
                  <td>Izmeni</td>
                  <td>Obrisi</td>
                </tr>
                
                @isset($korisnici)
                @foreach($korisnici as $korisnik)
                  <tr>
                    <td> {{ $korisnik->ID }} </td>
                    <td> {{ $korisnik->korisnicko_ime }} </td>
                    <td> {{ $korisnik->prezime }} </td>
                    <td> {{ $korisnik->username }} </td>
                    <td> {{ $korisnik->email }} </td>
                    <td> {{ $korisnik->naziv }} </td>
                    <td> <a href="{{ asset('/users/'.$korisnik->ID) }}">Izmeni</a> </td>
                    @if ($korisnik->uloga_id !=1)
                    <td> <a href="{{ asset('/users/destroy/'.$korisnik->ID) }}">Obrisi</a> </td>
                    @endif
                  </tr>
                @endforeach
                @endisset
            </table>
        </div>
		
@endsection