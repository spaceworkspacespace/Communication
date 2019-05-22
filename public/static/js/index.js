(function ($) {
    $(".nav-secondary .nav-item a").on("click", function (event) {
        event.preventDefault();
        // event.stopPropagation();
    });
    $(".nav-secondary .nav-item").on("click", function (event) {
        var a = $(this).children("a");
        var href = a.attr("href");
        if (/#(\w+)/.test(href)) {
            a.tab("show");
        } else if ($(this).hasClass("x-expansion-items")) {  // 判断是否为拥有子栏目的栏目
            var sets = $.merge($(this).next(".x-sub-item"), $(this));
            sets.toggleClass("x-show");
            sets.addClass("x-light");
            setTimeout(function() {
                sets.removeClass("x-light");
            }, 300);
        }
    });
    $(".x-nav-expansion span").on("click", function(event) {
        $(this).parents(".nav-secondary").toggleClass("x-hide")
            .next(".content-secondary").toggleClass("x-full");
    });
})($);
