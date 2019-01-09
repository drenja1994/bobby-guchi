<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title> DRAMSKA TRUPA ŽAOKA | @yield('title') </title>

    @section('appendCss')
    
    <link href="{{ asset('/') }}vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('/') }}css/bootstrap.css" rel="stylesheet">
     <link href="{{ asset('/') }}css/ionicons.css" rel="stylesheet">
      <link href="{{ asset('/') }}css/responsive.css" rel="stylesheet">
       <link href="{{ asset('/') }}css/styles.css" rel="stylesheet">
    @show
    
    

  </head>

  <body>

    @include('components.nav')
	
    <div class="container">

      <div class="row">

        
        @yield('content')

        @include('components.sidebar')
		
      </div>

    </div>
	
	
    @include('components.footer')
	
	
    @section('appendJavascript')
    
    <script src="{{ asset('/') }}vendor/jquery/jquery.min.js"></script>
    <script src="{{ asset('/') }}vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    @show
    
  </body>

</html>


/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

