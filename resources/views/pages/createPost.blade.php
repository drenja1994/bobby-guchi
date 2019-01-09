@extends('layouts.front')

@section('title')
    Rad sa predstavama
@endsection

@section('appendCss')
    @parent
    
    <link href="{{ asset('/') }}css/blog-home.css" rel="stylesheet"/>
@endsection

@section('content')

        <div class="col-md-8">
            <h3>Add post</h3>
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

            <form action="{{ (isset($post))? asset('/post/update/'.$post->post.id)  : asset('/posts/store') }}" method="POST" enctype="multipart/form-data">
              {{ csrf_field() }}
              <div class="form-group">
                <label>Title:</label>
                <input type="text" name="title" class="form-control" value="{{ (isset($post))? $post->naslov : old('title') }}"/>
              </div>
              <div class="form-group">
                <label>Body:</label>
                <textarea name="body" class="form-control" rows="7">{{ old('body') }}</textarea>
              </div> 
              <div class="form-group">
                <label>Photo:</label>
                <input type="file" name="photo" class="form-control"  />
              </div>
              <div class="form-group">
                <label>Alt:</label>
                <input type="text" name="alt" class="form-control" value="{{ old('alt') }}"/>
              </div>
              <div class="form-group">
                <input type="submit" name="addPost" value="Add post" class="btn btn-default" />
              </div> 
            </form>
            <table class="table">
                <tr>
                  <td>Slika</td>
                  <td>Naslov</td>
                  
                  
                  <td>Obrisi</td>
                </tr>
               
                @isset($posts)
                @foreach($posts as $post)
                  <tr>
                     <td> @isset($post)
                  <img src="{{ asset($post->putanja) }}" width="100" />
                @endisset</td>  
                    <td width="150"> {{ $post->naslov }} </td>
                   
                    <td> <a href="{{ asset('/posts/destroy/'.$post->postId) }}">Obrisi</a> </td>
                  </tr>
                @endforeach
                @endisset
            </table>
             <div class="btn">
                    {{$posts->render('components.paginacija')}}
                </div>

        </div>
		<!--// Sadrzaj -->
@endsection