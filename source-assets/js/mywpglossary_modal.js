(function ($) {
	'use strict';
	$(window).load(function () {
		if( 'undefined' !== typeof mywpglossary_terms ){
			var tagLimit = ( 'undefined' !== typeof mywpglossary.tag_limit ) ? mywpglossary.tag_limit : -1;
			var tagCount = {};
			for ( const [ key, value ] of Object.entries( mywpglossary_terms ) ){
				if( undefined === tagCount[ key ] ){ tagCount[ key ] = 0 };
				$( '.mywpglossary-term-def[data-title="'+key+'"]' ).each( function(){
					if( tagCount[ key ] >= tagLimit && '-1' !== tagLimit ){ return; }
					var dataURL        = $( this ).data( 'url' );
					var innerText      = $( this ).text();
					if( 'tippy-popin' === mywpglossary.display_mode ){
						$( this ).addClass( 'mywpglossary-popin' );
						$( this ).removeClass( 'mywpglossary-term-def' );
						tippy( this, {
							content     : mywpglossary_terms[ key ][ 'content' ],
							placement   : 'bottom',
							theme       : mywpglossary.tippy_theme,
							allowHTML   : true,
							interactive : true,
						});
						tagCount[ key ]++;
					}else if( 'popin' === mywpglossary.display_mode ){
						$( this ).replaceWith(
							'<span class="mywpglossary-popin">' +
							'<span class="mywpglossary-content">'+
							mywpglossary_terms[ key ][ 'content' ]+
							'</span>'+
							innerText+
							'</span>');
						tagCount[ key ]++;
					}else{
						$( this ).replaceWith('<a class="mywpglossary-definition" href="'+dataURL+'">'+innerText+'</a>');
						tagCount[ key ]++;
					}
				});
			}
		}
	});
})(jQuery);
