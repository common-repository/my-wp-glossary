(function (wp) {
    const {__}                  = wp.i18n;
    const {createSlotFill}      = wp.components;
    const {registerPlugin}      = wp.plugins;
    const {dispatch, select}    = wp.data;
    const {PanelBody, PanelRow} = wp.components;


    const { Fill, Slot } = createSlotFill( 'PluginPrePublishPanel' );
    const PluginPrePublishPanelTest = () => {

        const postType = select( "core/editor" ).getCurrentPostType();
        const authorized = ["mywpglossary"];
        if ( ! authorized.includes( postType ) ){
            return null;
        }

        let letter_choose = jQuery( '#meta_box_mywpglossary_letter' ).val();

        dispatch('core/editor').lockPostSaving();

        if (letter_choose != '') {
            dispatch('core/editor').unlockPostSaving();
        }

        jQuery(document).on('change', '#meta_box_mywpglossary_letter', function () {
            if (jQuery(this).val() != '') {
                dispatch('core/editor').unlockPostSaving();
            } else {
                dispatch('core/editor').lockPostSaving();

            }
        });

        return (
            <Fill>
                <PanelBody title={__('Definition’s letter', 'my-wp-glossary')} icon="editor-textcolor" initialOpen={true}>
                    <PanelRow className="my-wp-glossary-checklist">
                        { __('Please choose a letter before saving your definition!', 'my-wp-glossary') }
                    </PanelRow>
                </PanelBody>
            </Fill>
        )
    }

    registerPlugin('pre-publish-panel-test', {render: PluginPrePublishPanelTest});

})(window.wp);