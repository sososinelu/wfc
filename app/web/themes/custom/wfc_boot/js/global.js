/**
 * Premium page call to actions
 * stripe form
 */
(function($) {
  $(document).ready(function() {
    $(".1-cta").click(function(){
      $('.stripe-forms #quarterly .stripe-button-el').click();
    });

    $(".2-cta").click(function(){
      $('.stripe-forms #semiannual .stripe-button-el').click();
    });

    $(".3-cta").click(function(){
      $('.stripe-forms #annual .stripe-button-el').click();
    });
  });
})(jQuery);

/**
 * Form styling
 */
(function ($) {
  $(document).ready(function() {
    // Add placeholder to signup form
    // $('.form-email').attr('placeholder', 'I want cheap flights!');

    $('input.error, select.error').each(function() {
      $(this).parents('.form-item').first().addClass('error');
    });

    $('input[type="submit"]').on('click',function() {
      $(':input[required]:visible').each(function() {
        if(!$(this).val()){
          $(this).parents('.form-item').first().addClass('error');
        }
      });
    });
  });
}(jQuery));

/**
 * Scroll to the top
 */
(function($) {
  // When the user scrolls down 20px from the top of the document, show the button
  window.onscroll = function() {scrollFunction()};

  function scrollFunction() {
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
      $(".top").show();
    } else {
      $(".top").hide();
    }
  }


/**
 * Select all links with hashes
 */
$('a[href*="#"]')
  .click(function(event) {

    // On-page links
      // Figure out element to scroll to
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
      // Does a scroll target exist?
      if (target.length) {
        // Only prevent default if animation is actually gonna happen
        event.preventDefault();
        $('html, body').animate({
          scrollTop: target.offset().top
        }, 800, function() {

          // Callback after animation
          // Must change focus!
          var $target = $(target);
          $target.focus();
          if ($target.is(":focus")) { // Checking if the target was focused
            return false;
          } else {
            $target.attr('tabindex','-1'); // Adding tabindex for elements not focusable
            $target.focus(); // Set focus again
          };
        });
      }
  })
})(jQuery);

/**
 * Global utilities.
 */
(function($, Drupal) {
  'use strict';
  Drupal.behaviors.bootstrap_barrio_subtheme = {
    attach: function(context, settings) {
      var position = $(window).scrollTop();
      $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
          $('body').addClass("scrolled");
        }
        else {
          $('body').removeClass("scrolled");
        }
        var scroll = $(window).scrollTop();
        if (scroll > position) {
          $('body').addClass("scrolldown");
          $('body').removeClass("scrollup");
        } else {
          $('body').addClass("scrollup");
          $('body').removeClass("scrolldown");
        }
        position = scroll;
      });
    }
  };
})(jQuery, Drupal);
