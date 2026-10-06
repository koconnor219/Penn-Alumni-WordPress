<?php
/** paSectionNav: links are added by pa-site.js from every Section with a Nav label. */
printf( '<nav %s aria-label="On this page"><div class="pa-secnav-inner"></div></nav>', get_block_wrapper_attributes( array( 'class' => 'pa-secnav' ) ) );
