<?php
/** paCardGrid columns: <div class="pa-grid pa-grid--3"> */
$n = in_array( (int) ( $attributes['columns'] ?? 3 ), array( 2, 3, 4 ), true ) ? (int) $attributes['columns'] : 3;
printf( '<div %s>%s</div>', get_block_wrapper_attributes( array( 'class' => 'pa-grid pa-grid--' . $n ) ), $content );
