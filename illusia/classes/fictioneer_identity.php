<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Fictioneer_Identity' ) ) {

    class Fictioneer_Identity {

        /**
         * Modifica a identidade de uma história.
         *
         * @param array $output Array de output.
         * @param int   $story_id ID da história.
         * @param mixed $story Dados da história.
         * @return array Output atualizado.
         */
        public static function modify_story_identity( $output, $story_id, $story ) {
            $author_nodes = fictioneer_get_story_author_nodes( $story_id );
            $views        = (int) get_post_meta( $story_id, '_views', true );
            $meta_html    = '';

            // Processa dados dos autores.
            if ( ! empty( $author_nodes ) ) {
                if ( strpos( $author_nodes, ',' ) !== false ) {
                    $authors = array_map( 'trim', explode( ',', $author_nodes ) );
                    if ( count( $authors ) === 3 ) {
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-address-card"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[1] ) . '</span></div></div>';
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-language"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[0] ) . ' & ' . wp_kses_post( $authors[2] ) . '</span></div></div>';
                    } else {
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-address-card"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[1] ) . '</span></div></div>';
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-language"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[0] ) . '</span></div></div>';
                    }
                } else {
                    $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-address-card"></i></span> <span class="custom-story-info">' . wp_kses_post( trim( $author_nodes ) ) . '</span></div></div>';
                }
            }

            // Designers (taxonomia cover_designer).
            $designers = get_the_terms( $story_id, 'cover_designer' );
            if ( ! empty( $designers ) && ! is_wp_error( $designers ) ) {
                $designer_links = [];
                foreach ( $designers as $designer ) {
                    $designer_links[] = '<a href="' . esc_url( get_term_link( $designer ) ) . '">' . esc_html( $designer->name ) . '</a>';
                }
                $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-paint-brush"></i></span> <span class="custom-story-info">' . implode( ', ', $designer_links ) . '</span></div></div>';
            }

            // Visualizações.
            $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-eye"></i></span> <span class="custom-story-info">' . number_format( $views, 0, '', '.' ) . '</span></div></div>';

            // Votos – usa a função helper para calcular a porcentagem.
            $percentage = calculate_recommendation_percentage( $story_id );
            $vote_counts = [];
            for ( $i = 1; $i <= 5; $i++ ) {
                $vote_counts[ $i ] = (int) get_post_meta( $story_id, "_vote_count_$i", true );
            }
            $total_votes = array_sum( $vote_counts );
            $vote_text   = ( $total_votes === 1 ) ? 'voto' : 'votos';
            $meta_html  .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-thumbs-up"></i></span> <span class="custom-story-info">' . $percentage . '% (' . $total_votes . ' ' . $vote_text . ')</span></div></div>';

            // NOVA FUNCIONALIDADE: Acordeão para soma dos votos (história e capítulos) com porcentagem combinada.
            // Votos da história.
            $story_votes = (int) get_post_meta( $story_id, '_total_votes', true );
            // Votos dos capítulos.
            $chapters = get_posts( [
                'post_type'      => 'fcn_chapter',
                'posts_per_page' => -1,
                'meta_key'       => 'fictioneer_chapter_story',
                'meta_value'     => $story_id,
                'orderby'        => 'date',
                'order'          => 'ASC',
            ] );
            $chapters_votes = 0;
            if ( ! empty( $chapters ) ) {
                foreach ( $chapters as $chapter ) {
                    $chapters_votes += (int) get_post_meta( $chapter->ID, '_total_votes', true );
                }
            }
            $combined_votes = $story_votes + $chapters_votes;
            // Calcula a porcentagem combinada usando a função helper.
            $combined_percentage = calculate_combined_recommendation_percentage( $story_id );
            $vote_summation_details = '<div class="_custom-story-meta">
                <div class="vote-summation-header" style="cursor: pointer;" onclick="this.nextElementSibling.style.display = (this.nextElementSibling.style.display === \'block\' ? \'none\' : \'block\');">
                    <div class="_id-header">
                        <span class="custom-story-icon"><i class="fa-solid fa-chart-bar"></i></span>
                        <span class="custom-story-info">Votos Totais: ' . number_format( $combined_votes, 0, '', '.' ) . ' (' . $combined_percentage . '%)</span>
                    </div>
                </div>
                <div class="vote-summation-details" style="display:none;">
                    <p><strong>Detalhamento dos Votos:</strong></p>
                    <ul>
                        <li><strong>Votos na História:</strong> ' . number_format( $story_votes, 0, '', '.' ) . '</li>
                        <li><strong>Votos dos Capítulos:</strong> ' . number_format( $chapters_votes, 0, '', '.' ) . '</li>
                        <li><strong>Total Geral:</strong> ' . number_format( $combined_votes, 0, '', '.' ) . '</li>
                        <li><strong>Porcentagem Combinada:</strong> ' . $combined_percentage . '%</li>
                    </ul>
                </div>
            </div>';
            $meta_html .= $vote_summation_details;
            // Fim da nova funcionalidade.
            // Frequência de lançamento com acordeão.
            $frequency = calculate_release_frequencies( $story_id );
            $frequency_details = '<div class="_custom-story-meta">
                <div class="frequency-header" style="cursor: pointer;" onclick="this.nextElementSibling.style.display = (this.nextElementSibling.style.display === \'block\' ? \'none\' : \'block\');">
                   <div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-calendar-alt"></i></span> <span class="custom-story-info">' . esc_html( $frequency['level'] ) .
                   ( ( ! empty( $frequency['average'] ) && $frequency['average'] !== $frequency['level'] ) ? ', ' . esc_html( $frequency['average'] ) : '' ) .
                   '</span></div></div>
                <div class="frequency-details" style="display:none;">
                    <p><strong>Frequência Detalhada:</strong></p>
                    <ul>
                        <li><strong>Hoje:</strong> ' . esc_html( $frequency['daily'] ) . '</li>
                        <li><strong>Nesta Semana:</strong> ' . esc_html( $frequency['weekly'] ) . '</li>
                        <li><strong>Neste Mês:</strong> ' . esc_html( $frequency['monthly'] ) . '</li>
                        <li><strong>Neste Trimestre:</strong> ' . esc_html( $frequency['quarterly'] ) . '</li>
                        <li><strong>Desde o Ínicio:</strong> ' . esc_html( $frequency['total'] ) . '</li>
                        <li><strong>Frequência (90 Dias):</strong> ' . esc_html( $frequency['average_90_days'] ) . '</li>
                        <li><strong>Frequência (Total):</strong> ' . esc_html( $frequency['average'] ) . '</li>
                    </ul>
                </div>
            </div>';
            $meta_html .= $frequency_details;

            $output['meta'] = $meta_html;
            return $output;
        }

        /**
         * Modifica a identidade de um capítulo.
         *
         * @param array $output Array de output.
         * @param array $args Array contendo 'chapter_id'.
         * @return array Output atualizado.
         */
        public static function modify_chapter_identity( $output, $args ) {
            $chapter_id = $args['chapter_id'] ?? 0;
            if ( ! $chapter_id ) {
                return $output;
            }
            $author_nodes = fictioneer_get_chapter_author_nodes( $chapter_id );
            $views        = (int) get_post_meta( $chapter_id, '_views', true );
            $meta_html    = '';
            if ( ! empty( $author_nodes ) ) {
                if ( strpos( $author_nodes, ',' ) !== false ) {
                    $authors = array_map( 'trim', explode( ',', $author_nodes ) );
                    if ( count( $authors ) === 3 ) {
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-address-card"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[1] ) . '</span></div></div>';
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-language"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[0] ) . ' & ' . wp_kses_post( $authors[2] ) . '</span></div></div>';
                    } else {
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-address-card"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[1] ) . '</span></div></div>';
                        $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-language"></i></span> <span class="custom-story-info">' . wp_kses_post( $authors[0] ) . '</span></div></div>';
                    }
                } else {
                    $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-address-card"></i></span> <span class="custom-story-info">' . wp_kses_post( trim( $author_nodes ) ) . '</span></div></div>';
                }
            }
            if ( current_user_can( 'edit_posts' ) && $views > 0 ) {
                $meta_html .= '<div class="_custom-story-meta"><div class="_id-header"><span class="custom-story-icon"><i class="fa-solid fa-eye"></i></span> <span class="custom-story-info">' . number_format( $views, 0, '', '.' ) . '</span></div></div>';
            }
            // Votos para capítulo.
            $percentage = calculate_recommendation_percentage( $chapter_id );
            $vote_counts = [];
            for ( $i = 1; $i <= 5; $i++ ) {
                $vote_counts[ $i ] = (int) get_post_meta( $chapter_id, "_vote_count_$i", true );
            }
            $total_votes = array_sum( $vote_counts );
            $vote_text   = ( $total_votes === 1 ) ? 'voto' : 'votos';

            $meta_html .= '<div class="_custom-story-meta">
                <div class="_id-header">
                    <span class="custom-story-icon"><i class="fa-solid fa-thumbs-up"></i></span>
                    <span class="custom-story-info">' . $percentage . '% (' . $total_votes . ' ' . $vote_text . ')</span>
                </div>
            </div>';
            $output['meta'] = $meta_html;
            return $output;
        }
    }

    add_filter( 'fictioneer_filter_story_identity', [ 'Fictioneer_Identity', 'modify_story_identity' ], 10, 3 );
    add_filter( 'fictioneer_filter_chapter_identity', [ 'Fictioneer_Identity', 'modify_chapter_identity' ], 10, 2 );
}
