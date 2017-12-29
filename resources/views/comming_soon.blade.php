<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Coming Soon</title>
        <link href="assets/tools/style.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="assets/tools/jquery.min.js"></script> 
        <script type="text/javascript" src="assets/tools/cufon-yui.js"></script>
        <script type="text/javascript" src="assets/tools/Comfortaa_250-Comfortaa_700.font.js"></script>
        <script type="text/javascript">
            Cufon.replace('h1', {fontFamily: 'Comfortaa'});
            Cufon.replace('p.c_soon strong', {fontFamily: 'Comfortaa'});
        </script>        
        <script type="text/javascript" src="http://pinpals.fincoapps.com/admin/assets/js/jquery-1.10.2.js"></script>
        <script src="http://pinpals.fincoapps.com/admin/assets/sweetAlert/sweetalert-dev.js"></script>
        <link rel="stylesheet" href="http://pinpals.fincoapps.com/admin/assets/sweetAlert/sweetalert.css">

        </link>
    </head>
    <body>
        <div class="content_wrapper">
            <div class="top_content">
                <div class="header">
                    <div class="iphone">
                        <img src="assets/images/image.jpg" alt="" />
                    </div>
                    <div class="logo_content">
                        <h1><strong>Holla</strong>Port</h1>
                        <h3>The <strong>EASIEST</strong> way to chat and make payment <strong></strong></h3>
                        <p>With HollaPort App, you can make payments to anyone using their mobile number.   Payment is secure, fast and reliable.</p>
                        <a href="#" class="get_notified"><strong>Get Notified</strong> on the day of launch!</a>
                    </div>
                </div>
            </div>

            <div class="shadow"></div>
            <div class="white_content">
                <div class="top_content">
                    <div class="header">
                        <div class="coming_soon">
                            <p class="c_soon">we're <br/> <strong>Coming Soon</strong> <br/> initializing...</p>
                        </div>

                        <ul class="social">
                            <li><a href="#" class="googleplus">google plus</a></li>
                            <li><a href="#" class="twitter">twitter</a></li>
                            <li><a href="#" class="facebook">facebook</a></li>
                            <li><a href="#" class="email">email</a></li>
                            <li><a href="#" class="youtube">youtube</a></li>
                            <li><a href="#" class="pinterest">pinterest</a></li>
                        </ul>

                        <div class="copyright"><p>Copyright 2017. <br/> All Rights Reserved. <br/> <span><strong>Holla</strong>Port</span></p></div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    <script>
            $(".get_notified").click(function () {
                swal({
                    title: "Subscribe!",
                    text: "Enter your Email Address:",
                    type: "input",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    animation: "slide-from-top",
                    inputPlaceholder: "email@domain.com"
                },
                function (inputValue) {
                    if (inputValue === false)
                        return false;

                    if (inputValue === "") {
                        swal.showInputError("Please input your Email Address!");
                        return false
                    }
                    $.ajax({
                        url: "/subscribe",
                        type: "POST",
                        data: {email: inputValue},
                        success: function (data) {
                            if (data.hasOwnProperty("error")) {
                                swal("Error!", data.error, "error");
                            }
                            else {
                                swal("Subscribed!", data.success, "success");
                            }
                        },
                        error: function (xhr, status, error) {
                            swal("Error!", xhr.responseText, "error");
                        }
                    });
                    return true;
                });
            });
    </script>
</html>
