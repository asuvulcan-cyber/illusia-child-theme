<?php
/**
 * Cover Designer custom taxonomy archives
 *
 * @package WordPress
 * @subpackage Fictioneer
 * @since 4.0.0
 * @see partials/_archive-loop.php
 */

// Header
get_header();

// Setup
$term   = get_queried_object(); // termo atual
$parent = get_term_by( 'id', $term->parent, get_query_var( 'taxonomy' ) );
$output = [];

// Heading
$output['heading'] = '<h1 class="archive__heading">' . sprintf(
	/* traduzi _n para Results com base no count do termo */
	_n(
		'<span class="archive__heading-number">%1$s</span> Result with designer <em>"%2$s"</em> %3$s',
		'<span class="archive__heading-number">%1$s</span> Results with designer <em>"%2$s"</em> %3$s',
		$term->count,
		'fictioneer'
	),
	$term->count,
	single_tag_title( '', false ),
	$parent ? sprintf( _x( '(%s)', 'Taxonomy page parent suffix.', 'fictioneer' ), $parent->name ) : ''
) . '</h1>';

// Description
if ( ! empty( $term->description ) ) {
	$output['description'] = '<p class="archive__description">' . sprintf(
		__( '<strong>Description:</strong> %s', 'fictioneer' ), // Exemplo: “Description” no lugar de “Definition”
		$term->description
	) . '</p>';
}

// Divider
$output['divider'] = '<hr class="archive__divider">';

// Tax cloud (caso queira exibir nuvem de “outros designers”)
$output['tax_cloud'] = '<div class="archive__tax-cloud">' . wp_tag_cloud(
	array(
		'fictioneer_query_name' => 'tag_cloud',
		'smallest' => .625,
		'largest'  => 1.25,
		'unit'     => 'rem',
		'number'   => 0,
		// Troquei para 'cover_designer'
		'taxonomy' => [ 'cover_designer' ],
		'exclude'  => $term->term_id,
		'show_count' => true,
		'pad_counts' => true,
		'echo' => false
	)
) . '</div>';

?>
<main id="main" class="main archive designer-archive"><!-- alterado de character-archive para designer-archive -->

	<?php do_action( 'fictioneer_main', 'designer-archive' ); ?>

	<div class="main__wrapper">

		<?php do_action( 'fictioneer_main_wrapper' ); ?>

		<article class="archive__article">

			<header class="archive__header">
				<?php
				/**
				 * Filtro para manipular o header (infobox, heading, etc.).
				 * Passamos 'cover_designer' no lugar de 'fcn_character'.
				 */
				echo implode( '', apply_filters(
					'fictioneer_filter_archive_header',
					$output,
					'cover_designer',
					$term,
					$parent
				));
				?>
			</header>

			<?php
			/**
			 * Carrega o loop de posts relacionados a essa tax.
			 * Passamos 'cover_designer' em vez de 'fcn_character'.
			 */
			get_template_part(
				'partials/_archive-loop',
				null,
				array( 'taxonomy' => 'cover_designer' )
			);
			?>

		</article>

	</div><!-- .main__wrapper -->

	<?php do_action( 'fictioneer_main_end', 'designer-archive' ); ?>

</main>

<?php
/**
 * Monta breadcrumbs e chama o footer:
 * - Se quiser mudar a label do breadcrumb, troque “Results for Designer”
 */
$footer_args = array(
	'post_type' => null,
	'post_id'   => null,
	'template'  => 'taxonomy-cover_designer.php',
	'breadcrumbs' => array(
		[ fcntr( 'frontpage' ), get_home_url() ],
		[ sprintf( __( 'Results for Designer "%s"', 'fictioneer' ), single_tag_title( '', false ) ), null ]
	)
);

get_footer( null, $footer_args );