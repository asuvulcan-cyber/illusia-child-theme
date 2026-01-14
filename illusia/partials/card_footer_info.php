<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Se a função format_number() não existir, define-a.
 */
if ( ! function_exists( 'format_number' ) ) {
    function format_number( $number ) {
        if ( $number >= 1000000 ) {
            return number_format( $number / 1000000, 1, '.', '' ) . 'M';
        } elseif ( $number >= 1000 ) {
            return number_format( $number / 1000, 1, '.', '' ) . 'k';
        } else {
            return number_format( $number, 0, '', '.' );
        }
    }
}

/**
 * Adiciona views, comentários, recomendação e frequência ao footer dos cards.
 *
 * @param array  $footer_items
 * @param object $post
 * @param mixed  $story
 * @param mixed  $args
 * @return array Footer items modificados.
 */
function add_views_and_comments_to_footer( $footer_items, $post, $story = null, $args = null ) {
    $views           = is_object( $post ) && isset( $post->ID ) ? (int) get_post_meta( $post->ID, '_views', true ) : 0;
    $formatted_views = format_number( $views );

    $comments_count    = (int) $post->comment_count;
    $formatted_comments = format_number( $comments_count );

    // Cálculo da recomendação usando o helper centralizado
    $percentage = calculate_recommendation_percentage( $post->ID );
    $vote_counts = [];
    for ( $i = 1; $i <= 5; $i++ ) {
        $vote_counts[ $i ] = (int) get_post_meta( $post->ID, "_vote_count_$i", true );
    }
    $total_votes = array_sum( $vote_counts );
    $formatted_recommendation = $percentage . '%';

    $frequency = calculate_release_frequencies( $post->ID );

    // Adiciona os itens de views, comments e recommendation no footer
    $footer_items['views'] = '<span class="card__footer-views"><i class="card-footer-icon fa-solid fa-eye"></i> ' . $formatted_views . '</span>';
    $footer_items['comments'] = '<span class="card__footer-comments"><i class="card-footer-icon fa-solid fa-comment"></i> ' . $formatted_comments . '</span>';
    $footer_items['recommendation'] = '<span class="card__footer-recommendation"><i class="card-footer-icon fa-solid fa-thumbs-up"></i> ' . $formatted_recommendation . '</span>';

    // Adiciona a frequência como ícone de calendário e o nível de frequência
    $footer_items['frequency'] = '<span class="card__footer-frequency"><i class="card-footer-icon fa-solid fa-calendar-alt"></i> ' . $frequency['level'] . ' (' . $frequency['average'] . ')</span>';

    // Reorganiza os itens para que 'views', 'comments', 'recommendation' e 'frequency' apareçam em lugares adequados
    $new_footer_items = [];
    $i = 0;
    foreach ($footer_items as $key => $item) {
        if ($i == 0) {
            $new_footer_items[$key] = $item;  // Primeiro item
        }

        if ($i == 1) {
            $new_footer_items['views'] = $footer_items['views'];  // 'views' segundo item
        }

        if ($i == 2) {
            $new_footer_items['comments'] = $footer_items['comments'];  // 'comments' terceiro item
        }

        if ($i == 3) {
            $new_footer_items['recommendation'] = $footer_items['recommendation'];  // 'recommendation' quarto item
        }

        if ($i == 4) {
            $new_footer_items['frequency'] = $footer_items['frequency'];  // 'frequency' quinto item
        }

        if ($i > 4) {
            $new_footer_items[$key] = $item;  // Outros itens após
        }
        $i++;
    }

    // Se não houver itens, adiciona 'views', 'comments', 'recommendation' e 'frequency' no final
    if ($i == 1) {
        $new_footer_items['views'] = $footer_items['views'];
    }

    if ($i == 2) {
        $new_footer_items['comments'] = $footer_items['comments'];
    }

    if ($i == 3) {
        $new_footer_items['recommendation'] = $footer_items['recommendation'];
    }

    if ($i == 4) {
        $new_footer_items['frequency'] = $footer_items['frequency'];
    }

    return $new_footer_items;
}
add_filter( 'fictioneer_filter_chapter_card_footer', 'add_views_and_comments_to_footer', 10, 4 );
add_filter( 'fictioneer_filter_shortcode_latest_chapters_card_footer', 'add_views_and_comments_to_footer', 10, 4 );
add_filter( 'fictioneer_filter_shortcode_latest_stories_card_footer', 'add_views_and_comments_to_footer', 10, 4 );
add_filter( 'fictioneer_filter_story_card_footer', 'add_views_and_comments_to_footer', 10, 4 );

/**
 * Adiciona visualizações às estatísticas das histórias.
 *
 * @param array $statistics
 * @param mixed $args
 * @return array Estatísticas modificadas.
 */
function add_views_to_stories_statistics( $statistics, $args ) { 
    $total_views = 0;
    $all_stories = new WP_Query( [
        'post_type'      => 'fcn_story',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids'
    ] );

    if ( $all_stories->have_posts() ) {
        foreach ( $all_stories->posts as $story_id ) {
            $views = (int) get_post_meta( $story_id, '_views', true );
            $total_views += $views;
        }
    }
    $formatted_views = format_number( $total_views );

    $statistics = array_merge(
        array_slice( $statistics, 0, 1, true ),
        [
            'views' => [
                'label'   => 'Visualizações', 
                'content' => $formatted_views
            ]
        ],
        array_slice( $statistics, 1, null, true )
    );

    return $statistics;
}
add_filter( 'fictioneer_filter_stories_statistics', 'add_views_to_stories_statistics', 10, 2 );

/**
 * Modifica os argumentos da query para ordenar as histórias por visualizações.
 *
 * @param array $query_args
 * @param array $args
 * @return array Argumentos modificados.
 */
function order_stories_by_views( $query_args, $args ) {
    if ( isset( $args['orderby'] ) && $args['orderby'] === 'views' ) {
        $query_args['meta_key'] = '_views';
        $query_args['orderby']  = 'meta_value_num';
        $query_args['order']    = 'DESC';
    }
    return $query_args;
}
add_filter( 'fictioneer_filter_shortcode_latest_stories_query_args', 'order_stories_by_views', 10, 2 );
add_filter( 'fictioneer_filter_stories_query_args', 'order_stories_by_views', 10, 2 );