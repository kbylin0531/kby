/**
 * Created by kbylin on 21/05/16.
 */

scrollTo = function (el, offeset) {
    var pos = (el && el.size() > 0) ? el.offset().top : 0;
    el && (pos = pos - $("header").height() + (offeset ? offeset : -1 * el.height()));
    $('html,body').animate({scrollTop: pos}, 'slow');
};