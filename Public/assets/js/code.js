
//****************************************
            // init scroll to top handler begin
            //****************************************
            var offset = 300;
            var duration = 500;
            var scrollToTop = $('.scroll-to-top');
            if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {  // ios supported
                $(window).bind("touchend touchcancel touchleave", function () {
                    if ($(this).scrollTop() > offset) {
                        scrollToTop.fadeIn(duration);
                    } else {
                        scrollToTop.fadeOut(duration);
                    }
                });
            } else {  // general
                $(window).scroll(function () {
                    if ($(this).scrollTop() > offset) {
                        scrollToTop.fadeIn(duration);
                    } else {
                        scrollToTop.fadeOut(duration);
                    }
                });
            }
            scrollToTop.click(function (e) {
                e.preventDefault();
                $('html, body').animate({scrollTop: 0}, duration);
                return false;
            });