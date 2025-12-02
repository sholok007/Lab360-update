<!-- container-scroller -->
    <!-- plugins:js -->
    
    <script src="{{asset('/vendors/js/vendor.bundle.base.js')}}"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="{{asset('/vendors/chart.js/Chart.min.js')}}"></script>
    <script src="{{asset('/vendors/progressbar.js/progressbar.min.js')}}"></script>
    <script src="{{asset('/vendors/jvectormap/jquery-jvectormap.min.js')}}"></script>
    <script src="{{asset('/vendors/jvectormap/jquery-jvectormap-world-mill-en.js')}}"></script>
    <script src="{{asset('/vendors/owl-carousel-2/owl.carousel.min.js')}}"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{asset('js/off-canvas.js')}}"></script>
    <script src="{{asset('/js/hoverable-collapse.js')}}"></script>
    <script src="{{asset('/js/misc.js')}}"></script>
    <script src="{{asset('/js/settings.js')}}"></script>
    <script src="{{asset('/js/todolist.js')}}"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="{{asset('/js/dashboard.js')}}"></script>
    <!-- End custom js for this page -->

    <!-- jQuery CDN (যদি bundle এ না থাকে) -->
    
    <script src="{{asset('/js/jquery-3.6.0.min.js')}}"></script>
    <script src="{{asset('/js/bootstrap.bundle.min.js')}}"></script>
   
    <script src="{{asset('/js/custom.js')}}"></script>
    
    <script>
        // CSRF setup
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });
    </script>