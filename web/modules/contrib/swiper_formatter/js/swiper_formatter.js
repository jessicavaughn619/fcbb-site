/**
 * @file
 * Init any instances of Swiper on the page.
 */

(function ($, Drupal, drupalSettings, once) {

  'use strict';

  Drupal.swiper_formatter = Drupal.swiper_formatter || {};

   /**
    * Drupal.behaviors implementation.
    *
    * Register and initialise all Swiper instances on the page.
    *
    */
   Drupal.behaviors.nkToolsSwiper = {

    attach: function(context, settings) {

      var self = this;

      var swiper_formatter_settings = settings.swiper_formatter || null;

      if (swiper_formatter_settings && $.type(swiper_formatter_settings.swipers) !== 'undefined') { 

        var swipers = {};

        once('swiperFormaterInit', '.swiper-container', context).forEach(function(swiperContainer) {

	        if (swiperContainer.id) {
            var swiperSettings = swiper_formatter_settings.swipers[swiperContainer.id];
            if (typeof swiperSettings === 'object' && typeof Swiper !== 'undefined') {
	
              if (swiperSettings.pagination.type === 'progressbar') {
	              $(swiperContainer).addClass('progressbar');
              }

	            swipers[swiperContainer.id] = new Swiper('#' + swiperContainer.id, swiperSettings);

              if (swipers[swiperContainer.id]) {

                // Swiper's slideChangeTransitionEnd event.
                // @todo: Make this as option in entity configuration form.
                // swipers[swiperContainer.id].on('slideChangeTransitionEnd', function(e) {
                //   self.showHidden(this);
                // });

                // A custom links (anywhere on the page) that trigger swiper slides.
                self.registerTriggers(swipers[swiperContainer.id], $(context).find('.swiper-trigger'), context, settings); 
              }
	          }
	        }
	      });    
      }
    },

    /**
     * Show's any ".hidden" elements on a new slide.
     *
     * @param object swiper
     *  Current Swiper object.
     */
    showHidden: function(swiper) {
      if (swiper && swiper.slides.length > 0) {
        $.each(swiper.slides, function(index, slide) {
          if (index == swiper.activeIndex) {
            $(slide).find('.hidden').each(function(d, hidden) {
              $(hidden).removeClass('hidden');
            });
          }
        });
      }
    },

    /**
     * Run sliding from anywhere, with some markup attributes defined.
     *
     * @param object swiper
     *  Current Swiper object.
     * @param array triggers
     *  Array with trigger elements/objects.
     * @code
     *  <ul>
     *    <li><a class="swiper-trigger" data-index="2" href="#">Go to slide 2</a></li>
     *    <li><a class="swiper-trigger is-active" data-index="4" href="#">Go to slide 4</a></li>
     *  </ul>
     * @endcode
     */
    registerTriggers: function(swiper, triggers) {
      triggers.each(function(trigger) {
        $(this).on('click', function(e) {

          // Take care of siblings' active class.
          if ($(this).parent().siblings().length) {
            $(this).parent().siblings().each(function(i, sibling) {
              $(sibling).find('.swiper-trigger').removeClass('active');
            });
          }

          $(e.currentTarget).addClass('active');

          var index = $(this).attr('data-index') ? parseInt($(this).attr('data-index')) - 1 : 0;
          swiper.slideTo(index);
          return false;
        });
      });   
    }
  };

})(jQuery, Drupal, drupalSettings, once);