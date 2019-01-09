@extends('layouts.front')

@section('title')
   POČETNA
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
    
@endsection

@section('content')

        <div class="col-lg-8 col-md-10 mx-auto">

          <h1 class="my-4">ŽAOKA Тхе Стинг! ДО јаја филм!
            <small>Predstave trupe i njenih članova</small>
          </h1>
            
          @isset($posts)
          {{-- var_dump($posts) --}}
            
          @foreach($posts as $post)
            {{-- var_dump($post) --}}
          
          <div class="card mb-4">
            <img class="card-img-top" src="{{ asset('/').$post->putanja }}" alt="{{ $post->alt }}">
            <div class="card-body">
              <h2 class="card-title">{{ $post->naslov }}</h2>
              <p class="card-footer text-muted">
                  {{ $post->sadrzaj }}
              </p>
              <a href="{{ asset('/posts/'.$post->postId )}}" class="btn btn-primary">VIŠE &rarr;</a>
            </div>
            
          </div>
           
		
                @endforeach
   
                <div class="btn">
                    {{$posts->render('components.paginacija')}}
                </div>
                @endisset
	
         
          

        </div>
@if(!session()->has('user'))
<br/><br/>
Morate se ulogovati da biste videli anketu!
@endif
 @if(session()->has('user') && session()->get('user')->glasao == '0')
<div id="card-body"><br/><br/>
    @isset($ankete)
        {{ $ankete->anketa_pitanje }}<br/>
    @endisset
    <form action="{{ asset('/odgovori/update') }}" method="POST" enctype="multipart/form-data">
    {{ csrf_field() }}
     @isset($odgovori)
    @foreach($odgovori as $odgovor)
       <input type="radio" name="glasanje" value="{{ $odgovor->odgovor_id }}" >  {{ $odgovor->odgovor }}<br/>
        @endforeach
    <div class="form-group">
                <input type="submit" name="glasaj" value="Glasaj" class="btn btn-default" />
              </div> 
    @endisset
    <br/><br/>
    @empty(!session('message'))
              {{ session('message') }}
            @endempty
</div>
	@endif	
        @if(session()->has('user') && session()->get('user')->glasao == '1')
        @isset($ankete)
        <br/><br/>
        {{ $ankete->anketa_pitanje }}<br/>
        @endisset
        <br/><br/>
        @foreach($odgovori as $odgovor)
        {{$odgovor->odgovor}}&nbsp;&nbsp;{{$odgovor->glasovi}}<br/><br/>
        @endforeach
        Hvala što ste glasali!
        @endif
@endsection