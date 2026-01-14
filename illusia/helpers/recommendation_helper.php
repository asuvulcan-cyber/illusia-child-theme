<?php
/**
 * Helpers de recomendação (cálculo ponderado de votos)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calcula a porcentagem de recomendação ponderada para um único post.
 *
 * @param int $post_id ID do post (história ou capítulo).
 * @return float Porcentagem calculada (0 a 100).
 */
if ( ! function_exists( 'calculate_recommendation_percentage' ) ) {
	function calculate_recommendation_percentage( $post_id ) {
		$vote_counts = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$vote_counts[ $i ] = (int) get_post_meta( $post_id, '_vote_count_' . $i, true );
		}
		$total_votes = array_sum( $vote_counts );
		if ( 0 === $total_votes ) {
			return 0;
		}
		$weighted_votes = 0;
		foreach ( $vote_counts as $rating => $count ) {
			$weighted_votes += $count * ( 6 - $rating );
		}
		$max_possible = $total_votes * 5;
		$percentage   = round( ( $weighted_votes / $max_possible ) * 100, 2 );
		return min( $percentage, 100 );
	}
}

/**
 * Calcula a porcentagem combinada de recomendação para a história e seus capítulos.
 *
 * Soma os votos individuais (1 a 5) de história e capítulos
 * e aplica a mesma metodologia ponderada de calculate_recommendation_percentage().
 *
 * @param int $story_id ID da história.
 * @return float Porcentagem combinada (0 a 100).
 */
if ( ! function_exists( 'calculate_combined_recommendation_percentage' ) ) {
	function calculate_combined_recommendation_percentage( $story_id ) {
		$combined_votes = array_fill( 1, 5, 0 );
		for ( $i = 1; $i <= 5; $i++ ) {
			$combined_votes[ $i ] += (int) get_post_meta( $story_id, '_vote_count_' . $i, true );
		}
		$chapters = get_posts(
			array(
				'post_type'      => 'fcn_chapter',
				'posts_per_page' => -1,
				'meta_key'       => 'fictioneer_chapter_story',
				'meta_value'     => $story_id,
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		);
		if ( ! empty( $chapters ) ) {
			foreach ( $chapters as $chapter ) {
				for ( $i = 1; $i <= 5; $i++ ) {
					$combined_votes[ $i ] += (int) get_post_meta( $chapter->ID, '_vote_count_' . $i, true );
				}
			}
		}
		$total_votes = array_sum( $combined_votes );
		if ( 0 === $total_votes ) {
			return 0;
		}
		$weighted_votes = 0;
		foreach ( $combined_votes as $rating => $count ) {
			$weighted_votes += $count * ( 6 - $rating );
		}
		$max_possible = $total_votes * 5;
		$percentage   = round( ( $weighted_votes / $max_possible ) * 100, 2 );
		return min( $percentage, 100 );
	}
}
