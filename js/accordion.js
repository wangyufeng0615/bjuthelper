/*
* @Author: 卓文理
* @Email : 531840344@qq.com
* @Desc  : 折叠菜单
*/

(function (factory) {
    if ( typeof define === 'function' && define.amd ) {
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        module.exports = factory;
    } else {
        factory(jQuery);
    }
}(function ($) {
    var ua = navigator.userAgent;
    var isMobile = /(iPhone|Android|Mobile)/.test(ua);
    var EVENT_TAP = isMobile && !jQuery ? 'tap' : 'click';

    var accordion = function(e, options) {
        var $accordion = this.$accordion = $(e);

        this.options = $.extend({
            animate: true,
            duration: 300,
            onChange: function() {}
        }, options);

        this.init();
    }

    accordion.prototype = {
        constructor: accordion,
        init: function() {
            var $accordion = this.$accordion;
            var that = this;

            $accordion.find('.weui_accordion_title').unbind(EVENT_TAP);
            $accordion.find('.weui_accordion_title').on(EVENT_TAP, function() {
                var $title = $(this);
                var $content = $title.parent().find('.weui_accordion_content');

                if ($title.hasClass('active')) {
                    $title.removeClass('active');
                    that.close($content);
                } else {
                    $title.addClass('active');
                    that.open($content);
                }
            });
        },
        open: function($content) {
            var options = this.options;

            if (options.animate) {
                $content.slideDown(options.duration);

                setTimeout(function() {
                    $content.addClass('active');
                }, options.duration);
            } else {
                $content.show();
                $content.addClass('active');
            }

            options.onChange('open');
        },
        close: function($content) {
            var options = this.options;

            if (options.animate) {
                $content.slideUp(options.duration);

                setTimeout(function() {
                    $content.removeClass('active');
                }, options.duration);
            } else {
                $content.hide();
                $content.removeClass('active');
            }

            options.onChange('close');
        },
    }

    $.fn.extend({
        accordion: function(options) {
            new accordion(this, options);
        },
    });
}));
$('.weui_accordion_box').accordion({
    animate: true, // 开启动画效果
    duration: 300, // 动画时长
    onChange: function(event) {
        console.log(event); // 'open' or 'close'
    }
});