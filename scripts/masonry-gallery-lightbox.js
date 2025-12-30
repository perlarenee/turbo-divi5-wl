(function($) {
  'use strict';
  
  function initMasonryLightbox() {
    if (typeof $.fn.magnificPopup === 'undefined') {
      console.log('Magnific Popup not loaded');
      return;
    }
    
    $('.masonry_gallery__lightbox-link[data-lightbox]').magnificPopup({
      type: 'image',
      gallery: {
        enabled: true,
        navigateByImgClick: true,
        preload: [0, 1]
      },
      image: {
        titleSrc: function(item) {
          // Use caption if available, otherwise use alt text
          var caption = item.el.attr('data-caption');
          return caption || item.el.attr('data-alt') || '';
        },
        verticalFit: true
      },
      zoom: {
        enabled: true,
        duration: 300
      },
      callbacks: {
        elementParse: function(item) {
          item.src = item.el.attr('data-src');
        }
      }
    });
    
    console.log('Masonry lightbox initialized');
  }
  
  $(document).ready(initMasonryLightbox);
  
})(jQuery);