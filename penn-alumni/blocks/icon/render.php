<?php
/** Lucide icon in a span; the wrapper class (pa-tile-icon, pa-c-icon…) comes from "Additional CSS class". */
printf( '<span %s>%s</span>', get_block_wrapper_attributes(), pa_icon( $attributes['icon'] ?? '' ) );
