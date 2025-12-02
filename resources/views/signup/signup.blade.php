<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login::EspSensor</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="{{asset('/vendors/mdi/css/materialdesignicons.min.css')}}">
    <link rel="stylesheet" href="{{asset('/vendors/css/vendor.bundle.base.css')}}">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="{{asset('/css/style.css')}}">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="{{asset('/images/favicon.png')}}" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="row w-100 m-0">
          <div class="content-wrapper full-page-wrapper d-flex align-items-center auth login-bg">
            <div class="card col-lg-4 mx-auto">
              <div class="card-body px-5 py-5">
                <h3 class="card-title text-left mb-3">Sign Up</h3>
              
                  <div class="form-group">
                    <label>Full Name*</label>
                    <input id="name" type="text" class="form-control p_input" name="name" placeholder="Full Name">
                  </div>
                   <div class="form-group">
                    <label>User Name*</label>
                    <input id="username" type="text" class="form-control p_input" name="username" placeholder="User Name">
                  </div>
                  <div class="form-group">
                    <label>Email*</label>
                    <input id="email" type="email" class="form-control p_input" name="email" placeholder="Email">
                </div>  
                  <div class="form-group">
                      <label>Password*</label>
                      <input id="password" type="password"  class="form-control p_input" name="password" placeholder="Password">
                  </div>
                  <div class="form-group">
                    <label>Contact No.*</label>
                    <input id="contact_no" type="text" class="form-control form-control-user" name="contact_no" placeholder="Contact No">
                 </div>
                  <div class="text-center">
                    <button onclick="signup()" type="button" class="btn btn-success btn-user btn-block">Submit</button>
                     <a href="{{ route('login') }}"   type="button" class="btn btn-primary btn-user btn-block">Back to Login</a>
                  </div>
                  
                </form>
              </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
        </div>
        <!-- row ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->

    <script src="{{asset('/js/axios.min.js')}}"></script>
    <script src="{{asset('/js/jquery.min.js')}}"></script>
    <script src="{{asset('/js/toastr.min.js')}}"></script>
    <!-- plugins:js -->
    <script src="{{asset('/vendors/js/vendor.bundle.base.js')}}"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{asset('/js/off-canvas.js')}}"></script>
    <script src="{{asset('/js/hoverable-collapse.js')}}"></script>
    <script src="{{asset('/js/misc.js')}}"></script>
    <script src="{{asset('/js/settings.js')}}"></script>
    <script src="{{asset('/js/todolist.js')}}"></script>
    <!-- endinject -->
     
<script>
function signup() {
    let data = {
        name: document.getElementById("name").value,
        username: document.getElementById("username").value,
        email: document.getElementById("email").value,
        password: document.getElementById("password").value,
        contact_no: document.getElementById("contact_no").value
    };

    console.log(data); // Debugging: check values before sending

    axios.post("{{ route('signup.submit') }}", data)
        .then(res => {
            toastr.success(res.data.message || "Signup successful!");
        })
        .catch(err => {
            if (err.response && err.response.status === 422) {
                let errors = err.response.data.errors;
                Object.values(errors).forEach(msgArr => {
                    toastr.error(msgArr[0]);
                });
            } else {
                toastr.error("Something went wrong!");
            }
        });
}
</script>
  </body>
</html>