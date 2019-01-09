@extends('layouts.front')

@section('title')
   O meni
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
    
@endsection

@section('content')

        <div class="col-lg-8 col-md-10 mx-auto">

            <img src='images/autor.jpg'><br/><br/>
            <p class="text-justify text-center caption"style='font-weight: bold; color:black;'>AAAMilutin je mnogo jak. Ja sam Ognjen Drenjanin. Student master studija glume na "Fakultetu savremenih umetnosti" kao i završne godine "Visoke ICT škole". Dramsku trupu "Žaoka" osnovao sam sa kolegama Tijanom Upčev i Markom Todorovićem sa ciljem da ona služi kao mesto gde se mladi dramski umetnici okupljaju oko projekata koji ih inspirišu.</p>
           <br/><br/>
		
          
          

        </div>
		
@endsection