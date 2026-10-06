/* Penn Alumni blocks — editor UI.
   Plain JavaScript (no build step) so anyone can edit it in VS Code.
   Titles, descriptions and attributes come from each blocks/<name>/block.json;
   the live HTML comes from each block's render.php. */
( function ( wp ) {
	'use strict';
	const { registerBlockType } = wp.blocks;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const { InspectorControls, InnerBlocks, useBlockProps, useInnerBlocksProps, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl, Button, Notice } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const icons = window.pennIcons || {};
	const themeUrl = window.pennThemeUrl || '';
	const url = ( u ) => ( u || '' ).replace( '{{theme}}', themeUrl );

	const svg = ( name ) =>
		icons[ name ]
			? el( 'svg', {
					className: 'pa-icon lucide-' + name, xmlns: 'http://www.w3.org/2000/svg', width: '1em', height: '1em', viewBox: '0 0 24 24',
					fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round',
					dangerouslySetInnerHTML: { __html: icons[ name ] },
			  } )
			: null;
	const iconOptions = [ { label: '— none —', value: '' } ].concat( Object.keys( icons ).sort().map( ( k ) => ( { label: k, value: k } ) ) );
	const saveInner = () => el( InnerBlocks.Content );
	const ssr = ( name ) => ( props ) =>
		el( 'div', useBlockProps(), el( ServerSideRender, { block: name, attributes: props.attributes, urlQueryArgs: { post_id: wp.data.select( 'core/editor' ) ? wp.data.select( 'core/editor' ).getCurrentPostId() : 0 } } ) );

	const imagePicker = ( label, value, onSelect, onClear ) =>
		el( MediaUploadCheck, null,
			el( MediaUpload, {
				onSelect: ( m ) => onSelect( m ),
				allowedTypes: [ 'image' ],
				render: ( { open } ) =>
					el( 'div', { style: { marginBottom: '12px' } },
						el( 'p', null, el( 'strong', null, label ) ),
						value ? el( 'img', { src: url( value ), style: { maxWidth: '100%', marginBottom: '8px' } } ) : null,
						el( Button, { variant: 'secondary', onClick: open }, value ? 'Replace image' : 'Choose image' ),
						value ? el( Button, { variant: 'link', isDestructive: true, onClick: onClear, style: { marginLeft: '8px' } }, 'Remove' ) : null
					),
			} )
		);

	/* ── Section ─────────────────────────────────────────────── */
	registerBlockType( 'penn/section', {
		edit: ( { attributes: a, setAttributes } ) => {
			const props = useBlockProps( { className: [ 'pa-block', 'pa-bg-' + a.bg, a.compact ? 'pa-block--compact' : '' ].join( ' ' ) } );
			const innerProps = useInnerBlocksProps( a.inner ? { className: 'pa-block-inner' } : {}, {
				template: [ [ 'core/group', { className: 'pa-head' }, [ [ 'core/paragraph', { className: 'pa-head-eyebrow', placeholder: 'Eyebrow' } ], [ 'core/heading', { className: 'pa-head-title', placeholder: 'Section title — italicize the accent words' } ] ] ] ],
			} );
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Section settings' },
						el( SelectControl, { label: 'Background', value: a.bg, options: [ 'white', 'cream', 'gray', 'blue', 'red' ].map( ( v ) => ( { label: v[ 0 ].toUpperCase() + v.slice( 1 ), value: v } ) ), onChange: ( bg ) => setAttributes( { bg } ) } ),
						el( ToggleControl, { label: 'Compact spacing', checked: a.compact, onChange: ( compact ) => setAttributes( { compact } ) } ),
						el( TextControl, { label: 'On-this-page nav label', help: 'Shown in the sticky section bar. Also set an HTML anchor under Advanced.', value: a.navLabel, onChange: ( navLabel ) => setAttributes( { navLabel } ) } )
					)
				),
				a.inner ? el( 'section', props, el( 'div', innerProps ) ) : el( 'section', { ...props, ...innerProps, className: props.className + ' ' + ( innerProps.className || '' ) } )
			);
		},
		save: saveInner,
	} );

	/* ── Hero ────────────────────────────────────────────────── */
	registerBlockType( 'penn/hero', {
		edit: ( { attributes: a, setAttributes } ) => {
			const mod = { page: '', home: 'pa-hero--home', solid: 'pa-hero--solid', compact: 'pa-hero--solid pa-hero--compact' }[ a.variant ] || '';
			const props = useBlockProps( { className: 'pa-hero ' + mod } );
			const inner = useInnerBlocksProps( { className: 'pa-hero-text' }, {
				template: [
					[ 'penn/breadcrumbs' ],
					[ 'core/paragraph', { className: 'pa-hero-eyebrow', placeholder: 'Eyebrow' } ],
					[ 'core/heading', { level: 1, className: 'pa-hero-title', placeholder: 'Page title' } ],
					[ 'core/paragraph', { className: 'pa-hero-sub', placeholder: 'One-sentence subtitle' } ],
				],
			} );
			const solid = a.variant === 'solid' || a.variant === 'compact';
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Hero settings' },
						el( SelectControl, { label: 'Variant', value: a.variant, options: [ { label: 'Page (photo band)', value: 'page' }, { label: 'Home (full height)', value: 'home' }, { label: 'Solid blue (no photo)', value: 'solid' }, { label: 'Solid, compact', value: 'compact' } ], onChange: ( variant ) => setAttributes( { variant } ) } ),
						solid ? null : imagePicker( 'Background photo', a.mediaUrl, ( m ) => setAttributes( { mediaUrl: m.url, mediaId: m.id } ), () => setAttributes( { mediaUrl: '', mediaId: undefined } ) ),
						solid ? null : el( TextControl, { label: 'Background video URL (Vimeo background link, optional)', value: a.videoUrl, onChange: ( videoUrl ) => setAttributes( { videoUrl } ) } )
					)
				),
				el( 'section', props,
					! solid && a.mediaUrl ? el( 'div', { className: 'pa-hero-media', style: { backgroundImage: 'url(' + url( a.mediaUrl ) + ')' } } ) : null,
					solid ? null : el( 'div', { className: 'pa-hero-overlay' } ),
					el( 'div', { className: 'pa-hero-content' }, el( 'div', inner ) )
				)
			);
		},
		save: saveInner,
	} );

	/* ── Card grid ───────────────────────────────────────────── */
	registerBlockType( 'penn/grid', {
		edit: ( { attributes: a, setAttributes } ) => {
			const props = useBlockProps( { className: 'pa-grid pa-grid--' + a.columns } );
			const inner = useInnerBlocksProps( props, { template: [ [ 'penn/card' ], [ 'penn/card' ], [ 'penn/card' ] ], orientation: 'horizontal' } );
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Grid' },
						el( SelectControl, { label: 'Columns', value: String( a.columns ), options: [ 2, 3, 4 ].map( ( n ) => ( { label: n + ' columns', value: String( n ) } ) ), onChange: ( v ) => setAttributes( { columns: parseInt( v, 10 ) } ) } )
					)
				),
				el( 'div', inner )
			);
		},
		save: saveInner,
	} );

	/* ── Card ────────────────────────────────────────────────── */
	const CARD_STYLES = [ [ 'icon', 'Icon (blue top rule)' ], [ 'image', 'Image on top' ], [ 'number', 'Number' ], [ 'tag', 'Tag' ], [ 'stat', 'Stat' ], [ 'row', 'Row (icon · text · arrow)' ], [ 'text', 'Text only' ] ];
	registerBlockType( 'penn/card', {
		edit: ( { attributes: a, setAttributes } ) => {
			const props = useBlockProps( { className: [ 'pa-c', 'pa-c--' + a.cardStyle, a.center ? 'pa-c--center' : '' ].join( ' ' ) } );
			const inner = useInnerBlocksProps( { className: 'pa-c-body' }, {
				template: [
					[ 'core/heading', { level: 3, className: 'pa-c-title', placeholder: 'Card title' } ],
					[ 'core/paragraph', { className: 'pa-c-text', placeholder: 'Short description' } ],
					[ 'core/paragraph', { className: 'pa-c-link', placeholder: 'Learn More →' } ],
				],
			} );
			const icon = a.icon ? el( 'span', { className: 'pa-c-icon' }, svg( a.icon ) ) : null;
			const media = a.cardStyle === 'image'
				? el( 'div', { className: 'pa-c-media pa-tone-' + a.tone }, a.imageUrl ? el( 'img', { src: url( a.imageUrl ), alt: '' } ) : 'P', a.badge ? el( 'span', { className: 'pa-badge pa-badge--attn' }, a.badge ) : null )
				: null;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Card' },
						el( SelectControl, { label: 'Card style', value: a.cardStyle, options: CARD_STYLES.map( ( [ value, label ] ) => ( { value, label } ) ), onChange: ( cardStyle ) => setAttributes( { cardStyle } ) } ),
						el( TextControl, { label: 'Link (whole card is clickable)', value: a.href, onChange: ( href ) => setAttributes( { href } ) } ),
						el( ToggleControl, { label: 'Open in new tab', checked: a.newTab, onChange: ( newTab ) => setAttributes( { newTab } ) } ),
						el( SelectControl, { label: 'Icon', value: a.icon, options: iconOptions, onChange: ( icon ) => setAttributes( { icon } ) } ),
						el( ToggleControl, { label: 'Center content', checked: a.center, onChange: ( center ) => setAttributes( { center } ) } )
					),
					a.cardStyle === 'image'
						? el( PanelBody, { title: 'Image' },
								imagePicker( 'Card image (3:2)', a.imageUrl, ( m ) => setAttributes( { imageUrl: m.url, imageId: m.id } ), () => setAttributes( { imageUrl: '', imageId: undefined } ) ),
								el( SelectControl, { label: 'Placeholder color (no image)', value: a.tone, options: 'abcdef'.split( '' ).map( ( t ) => ( { label: 'Tone ' + t.toUpperCase(), value: t } ) ), onChange: ( tone ) => setAttributes( { tone } ) } ),
								el( TextControl, { label: 'Badge (optional)', value: a.badge, onChange: ( badge ) => setAttributes( { badge } ) } )
						  )
						: null
				),
				a.cardStyle === 'row'
					? el( 'div', props, icon, el( 'div', inner ), el( 'span', { className: 'pa-c-arrow' }, '→' ) )
					: el( 'div', props, media, el( 'div', inner, icon, inner.children ) )
			);
		},
		save: saveInner,
	} );

	/* ── Link box ────────────────────────────────────────────── */
	registerBlockType( 'penn/link-box', {
		edit: ( { attributes: a, setAttributes } ) => {
			const inner = useInnerBlocksProps( useBlockProps(), { template: [ [ 'core/paragraph' ] ] } );
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Link' },
						el( TextControl, { label: 'Link', value: a.href, onChange: ( href ) => setAttributes( { href } ) } ),
						el( ToggleControl, { label: 'Open in new tab', checked: a.newTab, onChange: ( newTab ) => setAttributes( { newTab } ) } )
					)
				),
				el( 'div', inner )
			);
		},
		save: saveInner,
	} );

	/* ── Icon ────────────────────────────────────────────────── */
	registerBlockType( 'penn/icon', {
		edit: ( { attributes: a, setAttributes } ) =>
			el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Icon' }, el( SelectControl, { label: 'Icon', value: a.icon, options: iconOptions, onChange: ( icon ) => setAttributes( { icon } ) } ) )
				),
				el( 'span', useBlockProps(), svg( a.icon ) )
			),
		save: () => null,
	} );

	/* ── Server-rendered blocks with simple settings ─────────── */
	registerBlockType( 'penn/breadcrumbs', { edit: ssr( 'penn/breadcrumbs' ), save: () => null } );
	registerBlockType( 'penn/section-nav', { edit: () => el( 'nav', useBlockProps( { className: 'pa-secnav' } ), el( 'div', { className: 'pa-secnav-inner' }, el( 'a', { className: 'active' }, 'On-this-page links appear here (from each Section’s nav label)' ) ) ), save: () => null } );
	registerBlockType( 'penn/club-directory', { edit: ssr( 'penn/club-directory' ), save: () => null } );
	registerBlockType( 'penn/site-header', { edit: ssr( 'penn/site-header' ), save: () => null } );
	registerBlockType( 'penn/site-footer', { edit: ssr( 'penn/site-footer' ), save: () => null } );

	registerBlockType( 'penn/event-feed', {
		edit: ( props ) => {
			const { attributes: a, setAttributes } = props;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Event feed' },
						el( SelectControl, { label: 'Layout', value: a.layout, options: [ { label: 'List (date rows)', value: 'list' }, { label: 'Cards', value: 'cards' } ], onChange: ( layout ) => setAttributes( { layout } ) } ),
						el( RangeControl, { label: 'How many events', min: 1, max: 12, value: a.count, onChange: ( count ) => setAttributes( { count } ) } ),
						el( TextControl, { label: 'Filter (event group or category)', help: 'e.g. Homecoming, Young Alumni, Virtual', value: a.filter, onChange: ( filter ) => setAttributes( { filter } ) } ),
						a.layout === 'cards' ? el( SelectControl, { label: 'Columns', value: String( a.columns ), options: [ 2, 3, 4 ].map( ( n ) => ( { label: String( n ), value: String( n ) } ) ), onChange: ( v ) => setAttributes( { columns: parseInt( v, 10 ) } ) } ) : null
					)
				),
				ssr( 'penn/event-feed' )( props )
			);
		},
		save: () => null,
	} );

	registerBlockType( 'penn/blackthorn', {
		edit: ( props ) => {
			const { attributes: a, setAttributes } = props;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Blackthorn embed' },
						el( TextControl, { label: 'Blackthorn path', help: 'Everything after the Org ID in the Blackthorn URL — e.g. g/ZZ8AC6qtdC for an event group. Leave blank to use the default calendar from Settings → Penn Alumni.', value: a.path, onChange: ( path ) => setAttributes( { path } ) } ),
						el( SelectControl, { label: 'Embed method', value: a.mode, options: [ { label: 'Advanced (auto-resize, recommended)', value: 'advanced' }, { label: 'Simple (one-line loader)', value: 'simple' } ], onChange: ( mode ) => setAttributes( { mode } ) } ),
						el( RangeControl, { label: 'Starting height (px)', min: 400, max: 2000, step: 50, value: a.height, onChange: ( height ) => setAttributes( { height } ) } ),
						el( TextControl, { label: 'Label (shown until connected)', value: a.label, onChange: ( label ) => setAttributes( { label } ) } )
					)
				),
				ssr( 'penn/blackthorn' )( props )
			);
		},
		save: () => null,
	} );

	registerBlockType( 'penn/form-assembly', {
		edit: ( props ) => {
			const { attributes: a, setAttributes } = props;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Form Assembly' },
						el( TextControl, { label: 'Form number', help: 'The number at the end of the form’s Form Assembly link.', value: a.formId, onChange: ( formId ) => setAttributes( { formId } ) } ),
						el( TextControl, { label: 'Form title (for screen readers)', value: a.title, onChange: ( title ) => setAttributes( { title } ) } ),
						el( RangeControl, { label: 'Height (px)', min: 300, max: 2000, step: 50, value: a.height, onChange: ( height ) => setAttributes( { height } ) } )
					)
				),
				ssr( 'penn/form-assembly' )( props )
			);
		},
		save: () => null,
	} );
} )( window.wp );
