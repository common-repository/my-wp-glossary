( function (blocks, editor, components, i18n, element ) {

	const { __ } = wp.i18n;
	var el                = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var BlockControls     = wp.blocks.BlockControls;
	var InspectorControls = wp.blocks.InspectorControls;
	var TextControl       = components.TextControl;
	var SelectControl     = components.SelectControl;
	var PanelBody         = components.PanelBody;

	registerBlockType( 'mywpglossary/glossary', {
		title      : __( 'Glossary', 'my-wp-glossary' ),
		icon       : 'editor-textcolor',
		category   : 'widgets',
		keywords   : [ __( 'form', 'my-wp-glossary' ), __( 'data', 'my-wp-glossary' ), __( 'request', 'my-wp-glossary' ) ],
		attributes : {
			request_type : {
				type : 'string'
			},
		},
		edit: function( props ) {
			var attributes   = props.attributes;
			var request_type = props.attributes.request_type;

			return [
				el(
					'div', {
						className: 'data-request-form-wrapper',
						style: {
							fontStyle: 'italic',
							color: '#333333',
							backgroundColor: '#eaeaea',
							paddingTop: '1em',
							paddingBottom: '1.5em',
							marginBottom: '0'
						}
					},
					el(
						'p', { 
							className: 'data-request-form-label',
							style: {
								textAlign: 'center',
								fontSize: '2em'
							}
						},
						__( 'My WP Glossary', 'my-wp-glossary'  )
					),
					el(
						'p', { 
							className: 'data-request-form-label',
							style: {
								paddingLeft: '2em',
								paddingRight: '2em'
							}
						},
						__( 'This block displays your glossary template.', 'my-wp-glossary' ),
					),
				),
			]
		},
		save: function() {
			return null;
		}
	} );
}(
	window.wp.blocks,
	window.wp.editor,
	window.wp.components,
	window.wp.i18n,
	window.wp.element
) );