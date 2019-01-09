@extends('layouts.front')

@section('title')
    Logovanje
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
@endsection

@section('content')
 
<div class="form-group">

    <div class="form-group">

        <form action="{{ route("login") }}" method="post">
            {{ csrf_field() }}
            <h1 class="lead">Login here:</h1>

            <div class="form-group">

                <input type="text" class="form-control" name="username"  placeholder="Username">

            </div>

            <div class="form-group">

                <input type="password" class="form-control" name="password"  placeholder="Password">

            </div>

            <div class="form-group">

                <button type="submit" class="btn btn-warning" name="button">Login</button>

            </div>

        </form>

        

    </div>

</div>
@if (session('alert'))
    <div class="alert alert-danger">
        {{ session('alert') }}
    </div>
@endif
@endsection