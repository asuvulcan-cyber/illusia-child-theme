<?php
/**
 * Exemplo de implementação para calcular frequências de publicação.
 * Ajuste caminhos, nomes de função e comentários conforme necessário.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calcula a frequência de publicação de uma história (fcn_story).
 *
 * @param int $story_id ID da história.
 * @return array Array com dados de frequência (daily, weekly, monthly, etc.).
 */
function calculate_release_frequencies( $story_id ) {
	$cache_key   = 'story_frequency_' . $story_id;
	$cached_data = get_transient( $cache_key );
	if ( false !== $cached_data ) {
		return $cached_data;
	}
	$status = get_post_meta( $story_id, 'fictioneer_story_status', true );
	if ( 'Completed' === $status ) {
		$data = array(
			'daily'           => 0,
			'weekly'          => 0,
			'monthly'         => 0,
			'quarterly'       => 0,
			'total'           => 0,
			'level'           => 'Concluída',
			'average'         => 'Concluída',
			'average_90_days' => 'Concluída',
		);
		update_post_meta( $story_id, 'release_frequencies', $data );
		set_transient( $cache_key, $data, 30 * DAY_IN_SECONDS );
		return $data;
	}
	if ( 'Oneshot' === $status ) {
		$data = array(
			'daily'           => 0,
			'weekly'          => 0,
			'monthly'         => 0,
			'quarterly'       => 0,
			'total'           => 0,
			'level'           => 'Conto',
			'average'         => 'Conto',
			'average_90_days' => 'Conto',
		);
		update_post_meta( $story_id, 'release_frequencies', $data );
		set_transient( $cache_key, $data, 30 * DAY_IN_SECONDS );
		return $data;
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
	if ( empty( $chapters ) ) {
		$data = array(
			'daily'           => 0,
			'weekly'          => 0,
			'monthly'         => 0,
			'quarterly'       => 0,
			'total'           => 0,
			'level'           => 'Sem Publicações',
			'average'         => 'Sem Publicações',
			'average_90_days' => 'Sem Publicações',
		);
		update_post_meta( $story_id, 'release_frequencies', $data );
		set_transient( $cache_key, $data, 5 * HOUR_IN_SECONDS );
		return $data;
	}
	$release_dates = array();
	foreach ( $chapters as $chapter ) {
		$date_str = get_the_date( 'Y-m-d', $chapter );
		if ( $date_str ) {
			$release_dates[] = new DateTime( $date_str, wp_timezone() );
		}
	}
	if ( empty( $release_dates ) ) {
		$data = array(
			'daily'           => 0,
			'weekly'          => 0,
			'monthly'         => 0,
			'quarterly'       => 0,
			'total'           => 0,
			'level'           => 'Sem Dados Válidos',
			'average'         => 'Sem Dados Válidos',
			'average_90_days' => 'Sem Dados Válidos',
		);
		update_post_meta( $story_id, 'release_frequencies', $data );
		set_transient( $cache_key, $data, 5 * HOUR_IN_SECONDS );
		return $data;
	}
	$now              = new DateTime( 'now', wp_timezone() );
	$daily_count      = 0;
	$weekly_count     = 0;
	$monthly_count    = 0;
	$quarterly_count  = 0;
	foreach ( $release_dates as $date ) {
		$interval    = $now->diff( $date );
		$total_hours = ( $interval->days * 24 ) + $interval->h;
		if ( $total_hours < 24 ) {
			$daily_count++;
		}
		$days = $interval->days;
		if ( $days < 7 ) {
			$weekly_count++;
		}
		if ( $days < 30 ) {
			$monthly_count++;
		}
		if ( $days < 90 ) {
			$quarterly_count++;
		}
	}
	$total_count      = count( $release_dates );
	$days_since_first = max( 1, $now->diff( $release_dates[0] )->days );
	$weeks_since_first = max( 1, $days_since_first / 7 );
	$weekly_average    = $total_count / $weeks_since_first;
	$release_dates_90  = array_filter(
		$release_dates,
		function( $date ) use ( $now ) {
			return $now->diff( $date )->days <= 90;
		}
	);
	$total_last_90      = count( $release_dates_90 );
	if ( ! empty( $release_dates_90 ) ) {
		$first_date_90  = reset( $release_dates_90 );
		$days_90        = max( 1, $now->diff( $first_date_90 )->days );
		$weeks_90       = max( 1, $days_90 / 7 );
		$weekly_average_90 = $total_last_90 / $weeks_90;
	} else {
		$weekly_average_90 = 0;
	}
	$taxonomy     = get_the_terms( $story_id, 'fcn_fandom' );
	$is_brazilian = false;
	if ( $taxonomy ) {
		foreach ( $taxonomy as $term ) {
			if ( 'brasileira' === $term->slug ) {
				$is_brazilian = true;
				break;
			}
		}
	}
	if ( $is_brazilian ) {
		if ( 0 === $total_count ) {
			$level = 'Inexistente';
		} elseif ( $weekly_average <= 0.5 ) {
			$level = 'Muito Baixa';
		} elseif ( $weekly_average <= 1.5 ) {
			$level = 'Baixa';
		} elseif ( $weekly_average <= 3 ) {
			$level = 'Moderada';
		} elseif ( $weekly_average <= 7 ) {
			$level = 'Alta';
		} else {
			$level = 'Frenética';
		}
	} else {
		if ( 0 === $total_count ) {
			$level = 'Inexistente';
		} elseif ( $weekly_average <= 1 ) {
			$level = 'Muito Baixa';
		} elseif ( $weekly_average <= 3 ) {
			$level = 'Baixa';
		} elseif ( $weekly_average <= 7 ) {
			$level = 'Moderada';
		} elseif ( $weekly_average <= 14 ) {
			$level = 'Alta';
		} else {
			$level = 'Frenética';
		}
	}
	if ( $is_brazilian ) {
		if ( 0 === $total_last_90 ) {
			$level_90 = 'Inexistente';
		} elseif ( $weekly_average_90 <= 0.5 ) {
			$level_90 = 'Muito Baixa';
		} elseif ( $weekly_average_90 <= 1.5 ) {
			$level_90 = 'Baixa';
		} elseif ( $weekly_average_90 <= 3 ) {
			$level_90 = 'Moderada';
		} elseif ( $weekly_average_90 <= 7 ) {
			$level_90 = 'Alta';
		} else {
			$level_90 = 'Frenética';
		}
	} else {
		if ( 0 === $total_last_90 ) {
			$level_90 = 'Inexistente';
		} elseif ( $weekly_average_90 <= 1 ) {
			$level_90 = 'Muito Baixa';
		} elseif ( $weekly_average_90 <= 3 ) {
			$level_90 = 'Baixa';
		} elseif ( $weekly_average_90 <= 7 ) {
			$level_90 = 'Moderada';
		} elseif ( $weekly_average_90 <= 14 ) {
			$level_90 = 'Alta';
		} else {
			$level_90 = 'Frenética';
		}
	}
	$data = array(
		'daily'           => $daily_count,
		'weekly'          => $weekly_count,
		'monthly'         => $monthly_count,
		'quarterly'       => $quarterly_count,
		'total'           => $total_count,
		'level'           => $level,
		'level_90_days'   => $level_90,
		'average'         => round( $weekly_average, 2 ) . ' Caps/Sem',
		'average_90_days' => round( $weekly_average_90, 2 ) . ' Caps/Sem',
	);
	update_post_meta( $story_id, 'release_frequencies', $data );
	set_transient( $cache_key, $data, 5 * HOUR_IN_SECONDS );
	return $data;
}

/**
 * Limpa o cache de frequência sempre que um capítulo é salvo.
 */
add_action(
	'save_post_fcn_chapter',
	function( $post_id ) {
		$story_id = get_post_meta( $post_id, 'fictioneer_chapter_story', true );
		if ( $story_id ) {
			delete_transient( 'story_frequency_' . $story_id );
			delete_post_meta( $story_id, 'release_frequencies' );
		}
	}
);
