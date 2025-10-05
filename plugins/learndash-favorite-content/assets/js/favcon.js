jQuery(document).ready(function($) {

     $('body').on( 'click', '.js-ldfc-unfavorite', function(e) {

          e.preventDefault();

          if( $(this).hasClass('is-loeading') ) {
               return;
          }

          var post_id  = $(this).data('post_id');
          var endpoint = 'ldfc_remove_favorite';

          $(this).addClass('is-loading');

          var elm = $('.ldfc-favorite-' + post_id );

          jQuery.ajax({
               url: favcon_ajax_call.adminAjax + '?action=' + endpoint,
               type: 'post',
               data: {
                    post_id : post_id
               },
               success: function( data ) {

                    if( data.success == true ) {
                         $(elm).slideUp('slow');
                    }

                    $(elm).removeClass('is-loading');

               }
          });

     });

     $('body').on( 'click', '.js-favcon-favorite', function(e) {

          e.preventDefault();

          if( $(this).hasClass('is-loeading') ) {
               return;
          }

          var post_id = $(this).data('post_id');

          if( $(this).hasClass('favcon-saved') ) {
               endpoint = 'ldfc_remove_favorite';
          } else {
               endpoint = 'ldfc_save_favorite'
          }

          $('.js-favcon-favorite').addClass('is-loading');

          jQuery.ajax({
               url: favcon_ajax_call.adminAjax + '?action=' + endpoint,
               type: 'post',
               data: {
                    post_id : post_id
               },
               success: function( data ) {

                    if( data.success == true ) {
                         $('.js-favcon-favorite').toggleClass('favcon-saved');
                    }

                    $('.js-favcon-favorite').removeClass('is-loading');

               }
          });

     });

     console.log('runs');

     $('.ldfc-favorite-search-input').keyup( function(e) {

          ldfc_live_search( $(this).parents( $(this) ) );

     });

     function ldfc_live_search( elm ) {

          var parent = $(elm).parent('.ldfc-search-parent');

          filter = $(parent).find('.ldfc-favorite-search-input').val().toLowerCase();
          elms = $(parent).find('.ldfc-favorite');

          $(elms).each(function(e) {

               if( $(this).text().toLowerCase().search(filter) > -1 ) {
                    $(this).show();
               } else {
                    $(this).hide();
               }

          });

     }

});
