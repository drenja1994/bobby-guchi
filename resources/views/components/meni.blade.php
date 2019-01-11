	<header>
		<div class="container-fluid position-relative no-side-padding">

			<a href="#" class="logo"><img src="{{ asset('/') }}images/zaokat.png" alt="Logo Image" style="height:50px; position:absolute; top:5px; left:5px;"></a>

			<div class="menu-nav-icon" data-nav-menu="#main-menu"><i class="ion-navicon"></i></div>

			<ul class="main-menu visible-on-click" id="main-menu">
				 @isset($menus)
              @foreach($menus as $menu)
                {{-- var_dump($menu) --}}
                <li class="nav-item {{ ($loop->first)? 'active' : '' }}">
                  <a class="nav-link" href="{{ asset($menu->link) }}"> {{ $menu->name}}
                      @if($loop->first)
                      <span class="sr-only">(current)</span>
                      @endif
                  </a>
                </li>
              @endforeach
            @endisset
            @if(session()->has('user'))
              <li>
                <a class="nav-link" href="{{ route('logout') }}">
                  Logout
                </a>
              </li>
            @endif
            @if(!session()->has('user'))
           <li class="nav-item">
                <a class="nav-link" href="{{ asset('/userlogin') }}">
                  Login
                </a>
              </li>
            @endif
             @if(session()->has('user') && session()->get('user')->ranking_id == '1')
                ADMIN PANEL LINKS WILL GO HERE IF ADMIN IS LOGGED IN
            @endif
			</ul>

			

		</div><!-- conatiner -->
	</header>
