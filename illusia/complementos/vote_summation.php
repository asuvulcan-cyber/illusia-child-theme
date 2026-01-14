<?php
/**
 * Soma os votos (meta '_total_votes') de todas as histórias e capítulos.
 * Oferece funções para exibir essa soma em HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Soma os votos de todas as histórias e capítulos.
 *
 * @return array Associativo com 'stories', 'chapters' e 'combined'.
 */
function sum_all_votes() {
	$total_votes_stories  = 0;
	$total_votes_chapters = 0;
	$stories_query         = new WP_Query(
		array(
			'post_type'      => 'fcn_story',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	if ( $stories_query->have_posts() ) {
		foreach ( $stories_query->posts as $story_id ) {
			$votes = (int) get_post_meta( $story_id, '_total_votes', true );
			$total_votes_stories += $votes;
		}
	}
	$chapters_query = new WP_Query(
		array(
			'post_type'      => 'fcn_chapter',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	if ( $chapters_query->have_posts() ) {
		foreach ( $chapters_query->posts as $chapter_id ) {
			$votes = (int) get_post_meta( $chapter_id, '_total_votes', true );
			$total_votes_chapters += $votes;
		}
	}
	return array(
		'stories'  => $total_votes_stories,
		'chapters' => $total_votes_chapters,
		'combined' => $total_votes_stories + $total_votes_chapters,
	);
}

/**
 * Exibe os totais de votos em formato HTML.
 */
function display_vote_summation() {
	$sums = sum_all_votes();
	echo '<div class="vote-summation">';
	echo '<p>Total de votos em Histórias: ' . number_format( $sums['stories'], 0, '', '.' ) . '</p>';
	echo '<p>Total de votos em Capítulos: ' . number_format( $sums['chapters'], 0, '', '.' ) . '</p>';
	echo '<p>Total Geral de votos: ' . number_format( $sums['combined'], 0, '', '.' ) . '</p>';
	echo '</div>';
}
