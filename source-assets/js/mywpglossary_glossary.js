(function ($) {
	'use strict';
	$( document ).ready(function() {
		if ( 0 < $('.mywpglossary-list-entry-title').length ) {
			$( '.mywpglossary-list-entry-title' ).click( function( e ){
				e.preventDefault();
				var $this = $( this );
				if ($this.next().hasClass('show')) {
					$this.next().removeClass('show');
					$this.next().slideUp(350);
					$this.hasClass('show_parent');
					$this.removeClass('show_parent');
				} else {
					$this.next().toggleClass('show');
					$this.next().slideToggle(350);
					$this.addClass('show_parent');
					window.location.hash = $(this).attr( 'id' ).replace( 'mywpglossary-term-' , '' );
				}
			});
			if ( window.location.hash ) {
				var node = $( '#mywpglossary-term-'+window.location.hash.substring(1) )
				if (0 < node.length) {
					$('html, body').animate({
						scrollTop: node.offset().top - 150
					}, 100 );
					node.click();
				}
			}
		}
	});
})( jQuery );
