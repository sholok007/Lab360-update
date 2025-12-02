<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
        <a class="sidebar-brand brand-logo" href="index.html"><img src="assets/images/logo.svg" alt="logo" /></a>
        <a class="sidebar-brand brand-logo-mini" href="index.html"><img src="assets/images/logo-mini.svg" alt="logo" /></a>
    </div>
    <ul class="nav">

       <li class="nav-item profile">
        <div class="profile-desc">
            <div class="profile-pic">
                <div class="count-indicator">
                    <img class="img-xs rounded-circle" 
                        src="{{ Auth::user()->profile_picture ? asset('storage/' . Auth::user()->profile_picture) : 'assets/images/faces/face15.jpg' }}" 
                        >
                    <span class="count bg-success"></span>
                </div>
                <div class="profile-name">
                    <h5 class="mb-0 font-weight-normal">{{ Auth::user()->name }}</h5>
                    <span>{{ Auth::user()->role }}</span>
                </div>
            </div>
        </div>
    </li>

        <li class="nav-item nav-category">
            <span class="nav-link">Navigation</span>
        </li>

       {{-- ✅ Admin সব পাবে --}}
          @if(Auth::user()->role === 'Admin')
              <li class="nav-item menu-items">
                  <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                      <span class="menu-icon">
                          <i class="mdi mdi-laptop"></i>
                      </span>
                      <span class="menu-title">Settings</span>
                      <i class="menu-arrow"></i>
                  </a>
                  <div class="collapse" id="ui-basic">
                      <ul class="nav flex-column sub-menu">
                          <li class="nav-item"> <a class="nav-link" href="{{ route('tests.index') }}">Test</a></li>
                          <li class="nav-item"> <a class="nav-link" href="{{ route('brands.index') }}">Brands</a></li>
                          <li class="nav-item"> <a class="nav-link" href="{{ route('reagents.index') }}">Reagents</a></li>
                      </ul>
                  </div>
              </li>
              <li class="nav-item menu-items">
                  <a class="nav-link" href="{{ route('testing-view.index') }}">
                      <span class="menu-icon"><i class="mdi mdi-playlist-play"></i></span>
                      <span class="menu-title">Terminal View</span>
                  </a>
              </li>
          @endif

           {{-- ESP Sensor Data --}}
          @if(in_array(Auth::user()->role, ['Admin','Proprietor']))
               <li class="nav-item menu-items">
                  <a class="nav-link" href="{{ route('machines.index') }}">
                      <span class="menu-icon"><i class="mdi mdi-robot"></i></span>
                      <span class="menu-title">Machine List</span>
                  </a>
              </li>  
          @endif

   

          {{-- User Links --}}
          @if(Auth::user()->role === 'Admin')
              {{-- Admin  --}}
              <li class="nav-item menu-items">
                  <a class="nav-link" href="{{ route('users.index') }}">
                      <span class="menu-icon"><i class="mdi mdi-playlist-play"></i></span>
                      <span class="menu-title">View/Edit Users</span>
                  </a>
              </li>
              <li class="nav-item menu-items">
                  <a class="nav-link" href="{{ route('users.create') }}">
                      <span class="menu-icon"><i class="mdi mdi-playlist-play"></i></span>
                      <span class="menu-title">Create User</span>
                  </a>
              </li>
                <li class="nav-item menu-items">
                  <a class="nav-link" href="{{ route('espsensor.index') }}">
                      <span class="menu-icon"><i class="mdi mdi-playlist-play"></i></span>
                      <span class="menu-title">Esp Sensor Data</span>
                  </a>
              </li>
                {{-- Dashboard & Profile --}}
                <li class="nav-item menu-items">
                    <a class="nav-link" href="{{ route('home.home') }}">
                        <span class="menu-icon"><i class="mdi mdi-speedometer"></i></span>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </li>
          @else
              {{-- Non-admin শুধুমাত্র নিজের প্রোফাইল --}}
              <li class="nav-item menu-items">
                  <a class="nav-link" href="{{ route('users.index') }}">
                      <span class="menu-icon"><i class="mdi mdi-account"></i></span>
                      <span class="menu-title">My Profile</span>
                  </a>
              </li>
          @endif

         

          {{-- Machine Setup --}}
          @if(in_array(Auth::user()->role, ['Admin','Proprietor','Moderator']))
              
          @endif
    </ul>
</nav>
