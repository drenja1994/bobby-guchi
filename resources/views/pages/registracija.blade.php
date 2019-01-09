@extends('layouts.front')

@section('title')
   Registracija
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
     <script type="text/javascript" src="{{ asset('/') }}js/register.js"></script>
@endsection

@section('content')

<div id="registracija">
     <div class="col-lg-10 col-md-10 mx-auto">

        <form action="{{ route("register") }}" method="post">
            {{ csrf_field() }}
            <h1 class="lead">Register here:</h1>

            <div class="form-group">

                <input type="text" class="form-control" id="tbIme1" name="korisnicko_ime"  placeholder="First Name">

            </div>

            <div class="form-group">

                <input type="text" class="form-control" id="tbPrezime1" name="prezime"  placeholder="Last Name">

            </div>

            <div class="form-group">

                <input type="text" class="form-control" id="tbEmail1" name="email"  placeholder="Email">

            </div>

            <div class="form-group">

                <input type="text" class="form-control" id="tbUsername" name="username"  placeholder="Username">

            </div>

            <div class="form-group">

                <input type="password" class="form-control" id="tbPass" name="password"  placeholder="Password">

            </div>

            <div class="form-group">

                <input type="password" class="form-control" name="password_confirmation"  placeholder="Confirm password">

            </div>

            <div class="form-group">

                <button type="submit" class="btn btn-warning" onClick="register();">Register</button>

            </div>

        </form>


        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(Session::has('success'))
            <div class="alert alert-success">
                {{ Session::get("success") }}
            </div>
        @endif

        @if(Session::has('error'))
            <div class="alert alert-danger">
                {{ Session::get("error") }}
            </div>
        @endif

    </div>

</div>


@endsection