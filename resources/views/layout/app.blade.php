<!DOCTYPE html>
<html lang="en">
  @include('layout.header')
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_sidebar.html -->
      @include('layout.topleftnav')
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_navbar.html -->
        @include('layout.topnavbar')
        <!-- partial -->
        <div class="main-panel">
          @yield('content')
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
         @include('layout.footer') 
          <!-- partial -->
        </div>
        
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    @include('layout.bottomlink') 
    @yield('script')
  </body>
</html>