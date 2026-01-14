<?php
/**
 * Código para exibição do ranking de histórias com filtros avançados.
 * Shortcode: [ranking_stories_filtros]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode principal.
 */
function get_ranking_stories_filtros_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'count'         => 10,
            'nacionalidade' => '',
            'ordenar_por'   => 'views',
        ),
        $atts,
        'ranking_stories_filtros'
    );

    // 1. Captura parâmetros
    $selected_count       = absint( $atts['count'] );
    $selected_order       = isset( $_GET['ordenar_por'] ) ? sanitize_key( $_GET['ordenar_por'] ) : sanitize_key( $atts['ordenar_por'] );
    $selected_nationality = isset( $_GET['nacionalidade'] ) ? sanitize_title( $_GET['nacionalidade'] ) : sanitize_title( $atts['nacionalidade'] );
    $specific_month       = isset( $_GET['specific_month'] ) ? absint( $_GET['specific_month'] ) : (int) date( 'm' );
    $specific_year        = isset( $_GET['specific_year'] ) ? absint( $_GET['specific_year'] ) : (int) date( 'Y' );
    $paged                = max( 1, get_query_var( 'paged' ) );

    // 2. Gera o formulário (Sempre dinâmico, nunca vai para o cache)
    $form_html = ranking_stories_generate_filter_form( $selected_order, $selected_nationality, $specific_month, $specific_year );

    // 3. Define a chave do cache APENAS para os resultados
    $transient_key = 'ranking_results_' . md5( wp_json_encode( array( $selected_count, $selected_order, $selected_nationality, $specific_month, $specific_year, $paged ) ) );
    
    // Tenta pegar apenas os resultados do cache
    $results_html = get_transient( $transient_key );

    // 4. Se não existir cache dos resultados, gera a consulta
    if ( false === $results_html ) {
        
        $args = array(
            'posts_per_page'      => $selected_count,
            'paged'               => $paged,
            'post_type'           => 'fcn_story',
            'ignore_sticky_posts' => true,
        );

        if ( $selected_nationality ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'fcn_fandom',
                    'field'    => 'slug',
                    'terms'    => $selected_nationality,
                ),
            );
        }
        
        ranking_stories_handle_ordering( $args, $selected_order, $specific_month, $specific_year );

        $query = new WP_Query( $args );
        
        // Otimização de cache de meta dados
        if ( ! empty( $query->posts ) ) {
            $post_ids = wp_list_pluck( $query->posts, 'ID' );
            update_meta_cache( 'post', $post_ids );
        }

        // Gera o cabeçalho "Ordenado por..."
        $metric_labels = array(
            'views'                => __( 'Visualizações - Total', 'illusia' ),
            'daily_views'          => __( 'Visualizações - Hoje', 'illusia' ),
            'monthly_views'        => __( 'Visualizações - Mês Atual', 'illusia' ),
            'yearly_views'         => __( 'Visualizações - Ano Atual', 'illusia' ),
            'specific_month_views' => sprintf(
                __( 'Visualizações - %1$s/%2$d', 'illusia' ),
                wp_date( 'F', mktime( 0, 0, 0, $specific_month, 1 ) ),
                $specific_year
            ),
        );
        
        $current_label = isset( $metric_labels[ $selected_order ] ) ? $metric_labels[ $selected_order ] : '';
        $metric_highlight = '<div class="ranking-header highlight">Ordenado por: <strong>' . $current_label . '</strong></div>';
        
        $correct_views_key = ranking_stories_get_meta_key( $selected_order, $specific_month, $specific_year );

        // Monta o HTML dos resultados
        $results_html  = $metric_highlight;
        $results_html .= ranking_stories_generate_results_output( $query, $correct_views_key );
        
        wp_reset_postdata();

        // Salva SOMENTE o HTML dos resultados no cache
        set_transient( $transient_key, $results_html, 5 * MINUTE_IN_SECONDS );
    }

    // 5. Retorna Formulário (Novo) + Resultados (Cacheados ou Novos)
    return $form_html . $results_html;
}
add_shortcode( 'ranking_stories_filtros', 'get_ranking_stories_filtros_shortcode' );

/**
 * Gera o formulário de filtros (HTML) OTIMIZADO (Sem campo QTD).
 */
function ranking_stories_generate_filter_form( $selected_order, $selected_nationality, $specific_month, $specific_year ) {
    ob_start(); ?>
    
    <div class="ranking-filters-container">
        <form method="GET" class="ranking-filters">
            
            <div class="filter-group">
                <label for="ordenar_por">ORDENAR POR:</label>
                <select name="ordenar_por" id="ordenar_por">
                    <?php
                    $options = array(
                        'views'                => __( 'Visualizações (Total)', 'illusia' ),
                        'daily_views'          => __( 'Hoje', 'illusia' ),
                        'monthly_views'        => __( 'Mês Atual', 'illusia' ),
                        'yearly_views'         => __( 'Ano Atual', 'illusia' ),
                        'specific_month_views' => __( 'Mês Específico', 'illusia' ),
                    );
                    foreach ( $options as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $selected_order ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="nacionalidade">NACIONALIDADE:</label>
                <select name="nacionalidade" id="nacionalidade">
                    <option value="">Todas</option>
                    <?php
                    $terms = get_terms( array( 'taxonomy' => 'fcn_fandom', 'hide_empty' => true ) );
                    if ( ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $term->slug, $selected_nationality ); ?>>
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                        <?php endforeach;
                    } ?>
                </select>
            </div>
            
            <div class="filter-group hidden" id="specific-month-filter">
                <label for="specific_month">SELECIONE O MÊS:</label>
                <select name="specific_month" id="specific_month">
                    <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                        <option value="<?php echo $m; ?>" <?php selected( $m, $specific_month ); ?>>
                            <?php echo wp_date( 'F', mktime( 0, 0, 0, $m, 1 ) ); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="filter-group group-btn">
                <button type="submit" class="ranking-filters__button">FILTRAR</button>
            </div>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderSelect = document.getElementById('ordenar_por');
        const specificMonthFilter = document.getElementById('specific-month-filter');

        function toggleSpecificMonthFilter() {
            const isSpecific = orderSelect.value === 'specific_month_views';
            if(isSpecific) {
                specificMonthFilter.classList.remove('hidden');
            } else {
                specificMonthFilter.classList.add('hidden');
            }
        }
        
        toggleSpecificMonthFilter();
        orderSelect.addEventListener('change', toggleSpecificMonthFilter);
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Retorna a meta_key correta, compensando o erro de salvamento de -1 mês.
 */
function ranking_stories_get_meta_key( $selected_order, $specific_month = null, $specific_year = null ) {
    $original_meta_keys = function_exists('get_view_meta_keys') ? get_view_meta_keys() : array('_views', '_views_year', '_views_month', '_views_day');

    switch ( $selected_order ) {
        case 'daily_views':
            return $original_meta_keys[3];
        case 'monthly_views':
            return $original_meta_keys[2];
        case 'yearly_views':
            return $original_meta_keys[1];
        
        case 'specific_month_views':
            if ( $specific_month && $specific_year ) {
                $user_selected_date = new DateTime( "{$specific_year}-{$specific_month}-01", wp_timezone() );
                $user_selected_date->modify( '-1 month' );
                $correct_key_format = $user_selected_date->format( 'Ym' );
                return '_views_month_' . $correct_key_format;
            }
            return $original_meta_keys[0]; 

        case 'views':
        default:
            return $original_meta_keys[0];
    }
}

/**
 * Ajusta os argumentos da WP_Query para ordenação.
 */
function ranking_stories_handle_ordering( &$args, $selected_order, $specific_month = null, $specific_year = null ) {
    $meta_key         = ranking_stories_get_meta_key( $selected_order, $specific_month, $specific_year );
    $args['meta_key'] = $meta_key;
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'DESC';
}

/**
 * Gera o HTML de resultados do ranking (loop).
 */
function ranking_stories_generate_results_output( $query, $views_key ) {
    ob_start();
    if ( $query->have_posts() ) :
        $paged          = max( 1, $query->get( 'paged', 1 ) );
        $posts_per_page = (int) $query->get( 'posts_per_page' );
        $rank_position  = ( ( $paged - 1 ) * $posts_per_page ) + 1;
        ?>
        <div class="ranking-stories">
            <?php
            while ( $query->have_posts() ) :
                $query->the_post();
                $post_id          = get_the_ID();
                $views_value      = (int) get_post_meta( $post_id, $views_key, true );
                
                $combined_percent = function_exists('calculate_combined_recommendation_percentage') ? calculate_combined_recommendation_percentage( $post_id ) : 0;
                $frequency        = function_exists('calculate_release_frequencies') ? calculate_release_frequencies( $post_id ) : array('average_90_days' => '-');
                $avg_90           = $frequency['average_90_days'];
                ?>
                <div class="story-item">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="story-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'medium' ); ?>
                                <div class="ranking-ribbon">#<?php echo esc_html( $rank_position ); ?></div>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="story-content">
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <div class="views-comments">
                            <span class="meta-item-ranking"><i class="fa fa-eye"></i><?php echo esc_html( function_exists('format_number') ? format_number( $views_value ) : $views_value ); ?></span>
                            <span class="meta-item-ranking"><i class="fa fa-chart-bar"></i><?php echo esc_html( $combined_percent . '%' ); ?></span>
                            <span class="meta-item-ranking"><i class="fa fa-comments"></i><?php echo esc_html( function_exists('format_number') ? format_number( (int) get_comments_number( $post_id ) ) : get_comments_number( $post_id ) ); ?></span>
                            <span class="meta-item-ranking"><i class="fa fa-calendar"></i><?php echo esc_html( $avg_90 ); ?></span>
                        </div>
                        <p class="story-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                    </div>
                </div>
                <?php
                $rank_position++;
            endwhile;
            ?>
            <div class="pagination">
                <?php
                echo paginate_links(
                    array(
                        'total'     => $query->max_num_pages,
                        'current'   => $paged,
                        'prev_next' => false,
                    )
                );
                ?>
            </div>
        </div>
    <?php else : ?>
        <p><?php esc_html_e( 'Nenhuma história encontrada.', 'illusia' ); ?></p>
    <?php endif;
    return ob_get_clean();
}