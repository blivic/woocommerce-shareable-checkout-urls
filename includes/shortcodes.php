<?php
	add_shortcode( 'scu_link', function( $atts ) {
    $a = shortcode_atts( [
        'id'          => '',
        'text'        => '',
        'class'       => '',
        'style'       => '',
        'target'      => '',
        'rel'         => '',
        'button'      => '',
        'align'       => '',
        'aria-label'  => '',
        'title'       => '',
    ], $atts, 'scu_link' );

    $url  = get_post_meta( intval( $a['id'] ), 'mx_scu_url', true );
    if ( ! $url ) {
        return '';
    }

    $text = $a['text']
        ? esc_html( $a['text'] )
        : esc_html__( 'Go to Checkout', 'shareable-checkout-urls' );

    $attrs = [];
    if ( $a['class'] )      $attrs[] = 'class="'       . esc_attr( $a['class'] )      . '"';
    if ( $a['style'] )      $attrs[] = 'style="'       . esc_attr( $a['style'] )      . '"';
    if ( $a['target'] )     $attrs[] = 'target="'      . esc_attr( $a['target'] )     . '"';
    if ( $a['rel'] )        $attrs[] = 'rel="'         . esc_attr( $a['rel'] )        . '"';
    if ( $a['aria-label'] ) $attrs[] = 'aria-label="'  . esc_attr( $a['aria-label'] ) . '"';
    if ( $a['title'] )      $attrs[] = 'title="'       . esc_attr( $a['title'] )      . '"';
    if ( $a['align'] ) {
        $attrs[] = 'style="text-align:' . esc_attr( $a['align'] ) . ';' . ( $a['style'] ?? '' ) . '"';
    }

    $attr_str = implode( ' ', $attrs );

    if ( strtolower( $a['button'] ) === 'yes' ) {
        return sprintf(
            '<a href="%s" %s><button type="button">%s</button></a>',
            esc_url( $url ),
            $attr_str,
            $text
        );
    }

    return sprintf(
        '<a href="%s" %s>%s</a>',
        esc_url( $url ),
        $attr_str,
        $text
    );
} );
