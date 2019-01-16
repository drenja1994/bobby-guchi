@extends('layouts.front')
@section('title')
   GUCHI NEWS | {{ $singlePost->title }}
@endsection
@section('content')

@isset($singlePost)
<!-- Sadrzaj -->
        <div class="col-lg-8 col-md-10 mx-auto">

          
          <!-- Title -->
          <h1 class="mt-4">{{ $singlePost->title }}</h1>

          <!-- Author -->
          <p class="lead">
            created_by
            <a href="#">{{ $singlePost->postUsername }}</a>
          </p>

          <hr>

          <!-- Date/Time -->
          <p>Kreirano {{ date("d.m.Y. H:i:s", $singlePost->created_at) }}</p>

          <hr>

          

          <hr>

          <!-- Post Content -->
          <p> {{ $singlePost->content }}</p>
          
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
               <form action="{{ asset('/comment/store/'.$singlePost->postId) }}" method="POST" enctype="multipart/form-data">
              {{ csrf_field() }}
                <div class="form-group">
                  <textarea name="commentText" class="form-control" rows="3"></textarea>
                </div>
                <input type="submit" name="addKorisnik" value="POST A COMMENT " class="btn btn-default" />
              </form>
            </div>
            @endif
            @if(!session()->has('user'))
            <h5 class="card-info">If you want to posta comment to news, you need to <a class="links"  href="{{ asset('/userlogin') }}">login</a>
            </h5>
            @endif
          </div>
		  <!--// Comments Form -->
		  COMMENTS:
          @isset($comments)
          @foreach($comments as $comment)
          <div class="media mb-4">
            <div class="card-header">
              <h5 class="card-info">{{ $comment->username }}</h5>
              {{ $comment->text }}
            </div>
          </div>
          @endforeach
          @endisset
		<!--// Single Comment -->

        </div>
		<!--// Sadrzaj -->
@endisset
@endsection
