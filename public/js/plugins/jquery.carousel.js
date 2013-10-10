(function($) {

    $.carousel = function(element, options) {

        var defaults = {
            target: element
        }

        var plugin = this;

        plugin.settings = {}

        var $element = $(element),
             element = element;

        plugin.init = function() {
            plugin.settings = $.extend({}, defaults, options);
            console.log(plugin.settings.target);
                    }

        plugin.foo_public_method = function() {
        }

        var foo_private_method = function() {
            // code goes here
        }

        plugin.init();

    }

    $.fn.carousel = function(options) {

        return this.each(function() {
            if (undefined == $(this).data('carousel')) {
                var plugin = new $.carousel(this, options);
                $(this).data('carousel', plugin);
            }
        });

    }

})(jQuery);