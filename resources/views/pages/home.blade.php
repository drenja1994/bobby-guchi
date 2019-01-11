@extends('layouts.front')

@section('title')
   SHOP WITH BOBBY
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
    
@endsection

@section('content')

        <div class="col-lg-8 col-md-10 mx-auto">

          <h1 class="my-4">Best shopping platform in the universe
            <small>BE LIKE BOBBY, BUY SOME GUCHI</small>
          </h1>
            
          @isset($posts)
          {{-- var_dump($posts) --}}
            
          @foreach($posts as $post)
            {{-- var_dump($post) --}}
          
          <div class="card mb-4">
            <div class="card-body">
              <h2 class="card-title">{{ $post->title }}</h2>
              <p class="card-footer text-muted">
                  {{ $post->content }}
              </p>
              <a href="{{ asset('/posts/'.$post->postId )}}" class="btn btn-primary">MORE &rarr;</a>
            </div>
            
          </div>
           
		
                @endforeach
   
                <div class="btn">
                    {{$posts->render('components.paginacija')}}
                </div>
                @endisset
	
         
          

        </div>

@endsection