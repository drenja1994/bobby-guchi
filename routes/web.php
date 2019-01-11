<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/* Route::get('/', function () {
    return view('welcome');
}); */

Route::get('/','FrontendController@index')->name("home");

Route::get('/posts/{id}','FrontendController@getPost');
Route::get('/autor','FrontendController@autor');
Route::post("/do-register", "LoginController@register")->name("register");
Route::get('/register','FrontendController@registracija');




Route::get('/userlogin','FrontendController@logovanje');
Route::post("/do-login", "LoginController@login")->name("login");
Route::get("/logout", "LoginController@logout")->name("logout");
Route::group(['middleware' => 'admin'], function() {
    Route::get('/users/{id?}', 'KorisnikController@show')->name("korisnici");
Route::post('/users/store', 'KorisnikController@store');
Route::post('/users/update/{id}','KorisnikController@update');
Route::get('/users/destroy/{id}','KorisnikController@destroy');

Route::get('/create/{id?}', 'PostController@create');
Route::post('/posts/store', 'PostController@store');
Route::get('/posts/destroy/{id}','PostController@destroy');

Route::get('/komentari/','KomentarController@index');
Route::get('/komentari/destroy/{id}','KomentarController@destroy');
Route::get('/komentari/update/{id}','KomentarController@update');

Route::get('/ankete/','AnketaController@index');
Route::get('/ankete/destroy/{id}','AnketaController@destroy');
Route::get('/ankete/restart/{id}','AnketaController@restart');
Route::get('/ankete/aktiviraj/{id}','AnketaController@aktiviraj');
Route::get('/ankete/reskor/','AnketaController@reskorisnik');

Route::get('/meni/','MeniController@index');
Route::post('/meni/update','MeniController@update');
    });
    Route::post('/komentar/store/{id}', 'KomentarController@store');
    Route::post('/odgovori/update','AnketaController@update');
