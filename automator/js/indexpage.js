const targetSignIn = document.getElementById("showsignin");
    const targetSignUp = document.getElementById("showsignup");
    const signinbtn = document.getElementById("signintoggle");
    const signupbtn = document.getElementById("signuptoggle");

    signupbtn.onclick = function () {
        if (targetSignUp.style.display === "none") {
            targetSignUp.style.display = "block";
            targetSignIn.style.display = "none";
        }
    }

    signinbtn.onclick = function () {
        if (targetSignIn.style.display === "none") {
            targetSignIn.style.display = "block";
            targetSignUp.style.display = "none";
        }
    }

    const goToSignUp = document.getElementById("goSignUp");
    const goToSignIn = document.getElementById("goSignIn");

    goToSignUp.onclick = function () {
        if (targetSignUp.style.display === "none") {
            targetSignUp.style.display = "block";
            targetSignIn.style.display = "none";
        }
    }

    goToSignIn.onclick = function () {
        if (targetSignIn.style.display === "none") {
            targetSignIn.style.display = "block";
            targetSignUp.style.display = "none";
        }
    }

    $(document).ready(function() {

        // Check for click events on the navbar burger icon
        $(".navbar-burger").click(function() {

            // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
            $(".navbar-burger").toggleClass("is-active");
            $(".navbar-menu").toggleClass("is-active");

        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
            const $notification = $delete.parentNode;

            $delete.addEventListener('click', () => {
                $notification.parentNode.removeChild($notification);
            });
        });
    });