<?php
/**
 * Funções para contagem de visualizações e exibição na metabox.
 *
 * Inclui formato compacto de números (1.5k, 2M etc.) e
 * incrementa atomicamente as views de forma distribuída, evitando duplicações
 * via transient (baseado em IP).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formata números para exibição (ex.: 1.5k ou 2M).
 *
 * @param int $number Número a formatar.
 * @return string Número formatado.
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
 * Adiciona metabox para exibir contagem de visualizações.
 */
function add_views_meta_box() {
	add_meta_box(
		'views_meta_box',
		'Contagem de Visualizações',
		'display_views_meta_box',
		array( 'fcn_story','fcn_chapter' ),
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'add_views_meta_box' );

/**
 * Exibe as contagens de visualizações na metabox.
 *
 * @param WP_Post $post O objeto post.
 */
function display_views_meta_box( $post ) {
	$now           = new DateTime( 'now', wp_timezone() );
	$current_year  = $now->format( 'Y' );
	$current_month = $now->format( 'Ym' );
	$current_day   = $now->format( 'Ymd' );
	$views_total   = (int) get_post_meta( $post->ID, '_views', true );
	$views_year    = (int) get_post_meta( $post->ID, '_views_year_' . $current_year, true );
	$views_month   = (int) get_post_meta( $post->ID, '_views_month_' . $current_month, true );
	$views_day     = (int) get_post_meta( $post->ID, '_views_day_' . $current_day, true );
	echo '<p>Total: ' . format_number( $views_total ) . '</p>';
	echo '<p>Anual (' . $current_year . '): ' . format_number( $views_year ) . '</p>';
	echo '<p>Mensal (' . $now->format( 'F Y' ) . '): ' . format_number( $views_month ) . '</p>';
	echo '<p>Diário (' . $now->format( 'd/m/Y' ) . '): ' . format_number( $views_day ) . '</p>';
}

/**
 * Incrementa de forma atômica um meta campo de um post.
 *
 * @param int    $post_id  ID do post.
 * @param string $meta_key Chave do meta.
 */
function increment_post_meta_atomic( $post_id, $meta_key ) {
	global $wpdb;
	$table  = $wpdb->postmeta;
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE $table SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = %d AND meta_key = %s",
			$post_id,
			$meta_key
		)
	);
	if ( 0 === $result ) {
		$added = add_post_meta( $post_id, $meta_key, 1, true );
		if ( ! $added ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $table SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = %d AND meta_key = %s",
					$post_id,
					$meta_key
				)
			);
		}
	}
}

/**
 * Incrementa as visualizações de histórias e capítulos com limitação por IP. */
function increment_story_views() {
	if ( ! is_singular( array( 'fcn_story','fcn_chapter' ) ) ) {
		return;
	}

	// Evita bots e crawlers
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
		return;
	}

	global $post;
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';

	// Determina ID da história
	if ( 'fcn_story' === $post->post_type ) {
		$story_id = $post->ID;
	} elseif ( 'fcn_chapter' === $post->post_type ) {
		$story_id = get_post_meta( $post->ID, 'fictioneer_chapter_story', true );
		if ( ! $story_id ) {
			$story_id = $post->ID;
		}
	} else {
		return;
	}

	$keys = get_view_meta_keys(); // ['_views', '_views_year_YYYY', ... ]

	// Função para checar e setar cache de IP
	$check_and_set = function( $cache_key, $expire ) {
		if ( ! wp_cache_get( $cache_key, 'views_limiter' ) ) {
			wp_cache_set( $cache_key, true, 'views_limiter', $expire );
			return true;
		}
		return false;
	};

	// STORY
	if ( 'fcn_story' === $post->post_type ) {
		$cache_key = 'story_' . $story_id . '_' . md5( $ip );
		if ( $check_and_set( $cache_key, 300 ) ) { // 5 minutos
			foreach ( $keys as $meta_key ) {
				increment_post_meta_atomic( $story_id, $meta_key );
			}
		}
	}

	// CHAPTER
	if ( 'fcn_chapter' === $post->post_type ) {
		$chapter_key = 'chapter_' . $post->ID . '_' . md5( $ip );
		if ( $check_and_set( $chapter_key, 120 ) ) { // 2 minutos
			foreach ( $keys as $meta_key ) {
				increment_post_meta_atomic( $post->ID, $meta_key );
			}
		}

		$story_key = 'story_' . $story_id . '_' . md5( $ip );
		if ( $check_and_set( $story_key, 120 ) ) {
			foreach ( $keys as $meta_key ) {
				increment_post_meta_atomic( $story_id, $meta_key );
			}
		}
	}
}

add_action( 'wp', 'increment_story_views' );
