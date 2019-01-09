@extends('layouts.front')
@section('title')
   PREDSTAVE | {{ $singlePost->naslov }}
@endsection
@section('content')

@isset($singlePost)
<!-- Sadrzaj -->
        <div class="col-lg-8 col-md-10 mx-auto">

          
          <!-- Title -->
          <h1 class="mt-4">{{ $singlePost->naslov }}</h1>

          <!-- Author -->
          <p class="lead">
            autor
            <a href="#">{{ $singlePost->postKorisnik }}</a>
          </p>

          <hr>

          <!-- Date/Time -->
          <p>Kreirano {{ date("d.m.Y. H:i:s", $singlePost->created_at) }}</p>

          <hr>

          <!-- Preview Image -->
          <img class="img-fluid rounded" src="{{ asset('/'.$singlePost->putanja)}}" alt="{{ $singlePost->alt}}">

          <hr>

          <!-- Post Content -->
          <p> {{ $singlePost->sadrzaj }}</p>
          
		  <hr>

          <!-- Comments Form -->
          <div class="card my-4">
              
                 @empty(!session('message'))
                 <div class="alert alert-success">
              {{ session('message') }}
              </div>
            @endempty
              @if(session()->has('user'))
              <h5 class="card-header">Leave a Comment:</h5>
            <div class="card-body">
               <form action="{{ asset('/komentar/store/'.$singlePost->postId) }}" method="POST" enctype="multipart/form-data">
              {{ csrf_field() }}
                <div class="form-group">
                  <textarea name="komentarisanje" class="form-control" rows="3"></textarea>
                </div>
                <input type="submit" name="addKorisnik" value="KOMENTARIŠI " class="btn btn-default" />
              </form>
            </div>
            @endif
            @if(!session()->has('user'))
            <h5 class="card-info">Ako želite da ostavite komentar, morate se ulogovati!</h5>
            @endif
          </div>
		  <!--// Comments Form -->
		  KOMENTARI:
          @isset($komentari)
          @foreach($komentari as $komentar)
          <div class="media mb-4">
            <div class="card-header">
              <h5 class="card-info">{{ $komentar->username }}</h5>
              {{ $komentar->tekst }}
            </div>
          </div>
          @endforeach
          @endisset
		<!--// Single Comment -->

        </div>
		<!--// Sadrzaj -->
@endisset
@endsection
