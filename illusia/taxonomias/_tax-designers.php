<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe que gerencia a taxonomia 'cover_designer'.
 *
 * Inclui campos customizados (redes sociais, descrição, foto),
 * uma infobox no front-end e preview da imagem tanto estático
 * quanto dinâmico no admin.
 */
class Cover_Designer_Taxonomy {
	// Nomes para registros no banco (term_meta).
	const TAXONOMY         = 'cover_designer';
	const META_SOCIALS     = 'cover_designer_socials';
	const META_DESCRIPTION = 'cover_designer_description';
	const META_IMAGE       = 'cover_designer_image';

	/**
	 * Método principal para registrar todos os hooks.
	 */
	public static function init() {
		// Registra a taxonomia.
		add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );

		// Formulários de adição e edição de termos.
		add_action( self::TAXONOMY . '_add_form_fields', [ __CLASS__, 'add_form_fields' ] );
		add_action( self::TAXONOMY . '_edit_form_fields', [ __CLASS__, 'edit_form_fields' ] );

		// Salva metadados ao criar/editar termo.
		add_action( 'created_' . self::TAXONOMY, [ __CLASS__, 'save_fields' ], 10, 2 );
		add_action( 'edited_' . self::TAXONOMY, [ __CLASS__, 'save_fields' ], 10, 2 );

		// Filtros para manipular header/infobox no front-end.
		add_filter( 'fictioneer_filter_archive_header', [ __CLASS__, 'add_infobox' ], 10, 4 );
		add_filter( 'fictioneer_filter_archive_header', [ __CLASS__, 'override_archive_heading' ], 20, 4 );

		// Enfileira CSS no front-end para a infobox.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );

		// Adiciona script de preview dinâmico no admin.
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Registra a taxonomia 'cover_designer'.
	 */
	public static function register_taxonomy() {
		$labels = [
			'name'              => _x( 'Designers de Capa', 'taxonomy general name', 'text-domain' ),
			'singular_name'     => _x( 'Designer de Capa', 'taxonomy singular name', 'text-domain' ),
			'search_items'      => __( 'Procurar Designers', 'text-domain' ),
			'all_items'         => __( 'Todos os Designers', 'text-domain' ),
			'parent_item'       => __( 'Designer Pai', 'text-domain' ),
			'parent_item_colon' => __( 'Designer Pai:', 'text-domain' ),
			'edit_item'         => __( 'Editar Designer', 'text-domain' ),
			'update_item'       => __( 'Atualizar Designer', 'text-domain' ),
			'add_new_item'      => __( 'Adicionar Novo Designer', 'text-domain' ),
			'new_item_name'     => __( 'Novo Nome de Designer', 'text-domain' ),
			'menu_name'         => __( 'Designers de Capa', 'text-domain' ),
		];

		$args = [
			'hierarchical'       => false,
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'show_admin_column'  => true,
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'cover-designer' ],
		];

		register_taxonomy( self::TAXONOMY, [ 'fcn_story' ], $args );
	}

	/**
	 * Enfileira CSS (infobox-designer.css) somente nas páginas do front-end da taxonomia.
	 */
	public static function enqueue_styles() {
		if ( ! is_admin() && is_tax( self::TAXONOMY ) ) {
			wp_enqueue_style( 'infobox-designer', get_stylesheet_directory_uri() . '/css/infobox-designer.css', [], '1.0' );
		}
	}

	/**
	 * Adiciona campos de Redes Sociais, Descrição e URL da Imagem
	 * ao formulário de criação de termo (tela 'Add New Term').
	 */
	public static function add_form_fields() {
		?>
		<div class="form-field term-custom-field">
			<label for="cover_designer_socials"><?php esc_html_e( 'Redes Sociais', 'text-domain' ); ?></label>
			<textarea name="cover_designer_socials" id="cover_designer_socials" rows="3"></textarea>
			<p class="description"><?php esc_html_e( 'Informe as URLs das redes sociais, uma por linha.', 'text-domain' ); ?></p>
		</div>
		<div class="form-field term-custom-field">
			<label for="cover_designer_description"><?php esc_html_e( 'Descrição do Designer', 'text-domain' ); ?></label>
			<textarea name="cover_designer_description" id="cover_designer_description" rows="5"></textarea>
			<p class="description"><?php esc_html_e( 'Breve descrição sobre o trabalho e o perfil do designer.', 'text-domain' ); ?></p>
		</div>
		<div class="form-field term-custom-field">
			<label for="cover_designer_image"><?php esc_html_e( 'Foto de Perfil (URL)', 'text-domain' ); ?></label>
			<input type="text" name="cover_designer_image" id="cover_designer_image" value="" />
			<p class="description"><?php esc_html_e( 'Informe a URL da imagem de perfil do designer.', 'text-domain' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Exibe e permite editar campos (incluindo preview da foto) ao editar termo existente.
	 *
	 * @param WP_Term $term Objeto do termo atual.
	 */
	public static function edit_form_fields( $term ) {
		$socials     = get_term_meta( $term->term_id, self::META_SOCIALS, true );
		$description = get_term_meta( $term->term_id, self::META_DESCRIPTION, true );
		$image       = get_term_meta( $term->term_id, self::META_IMAGE, true );
		?>
		<tr class="form-field term-custom-field">
			<th><label for="cover_designer_socials"><?php esc_html_e( 'Redes Sociais', 'text-domain' ); ?></label></th>
			<td>
				<textarea name="cover_designer_socials" id="cover_designer_socials" rows="3"><?php echo esc_textarea( $socials ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Informe as URLs das redes sociais, uma por linha.', 'text-domain' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-custom-field">
			<th><label for="cover_designer_description"><?php esc_html_e( 'Descrição do Designer', 'text-domain' ); ?></label></th>
			<td>
				<textarea name="cover_designer_description" id="cover_designer_description" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Breve descrição sobre o trabalho e o perfil do designer.', 'text-domain' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-custom-field">
			<th><label for="cover_designer_image"><?php esc_html_e( 'Foto de Perfil (URL)', 'text-domain' ); ?></label></th>
			<td>
				<input type="text" name="cover_designer_image" id="cover_designer_image" value="<?php echo esc_attr( $image ); ?>" style="width: 70%;" />
				<?php if ( $image ) : ?>
					<div style="margin-top: 10px;">
						<!-- Preview estático inicial -->
						<img id="cover_designer_image_preview" src="<?php echo esc_url( $image ); ?>" alt="Preview" style="max-width:150px; height:auto; border:1px solid #ccc; display:block; margin-top:5px;" />
					</div>
				<?php else : ?>
					<!-- Caso não tenha imagem, deixamos sem 'src' -->
					<img id="cover_designer_image_preview" src="" alt="Preview" style="display:none; max-width:150px; height:auto; border:1px solid #ccc; margin-top:5px;" />
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Informe a URL da imagem de perfil do designer. (Preview dinâmico abaixo)', 'text-domain' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Salva os valores submetidos nos metadados do termo.
	 *
	 * @param int $term_id
	 */
	public static function save_fields( $term_id ) {
		// Redes Sociais
		if ( isset( $_POST['cover_designer_socials'] ) ) {
			update_term_meta(
				$term_id,
				self::META_SOCIALS,
				sanitize_textarea_field( wp_unslash( $_POST['cover_designer_socials'] ) )
			);
		}
		// Descrição
		if ( isset( $_POST['cover_designer_description'] ) ) {
			update_term_meta(
				$term_id,
				self::META_DESCRIPTION,
				wp_kses_post( wp_unslash( $_POST['cover_designer_description'] ) )
			);
		}
		// URL da Imagem
		if ( isset( $_POST['cover_designer_image'] ) ) {
			update_term_meta(
				$term_id,
				self::META_IMAGE,
				esc_url_raw( wp_unslash( $_POST['cover_designer_image'] ) )
			);
		}
	}

	/**
	 * Gera o infobox exibido no front-end (topo do arquivo da taxonomia),
	 * mostrando foto, redes sociais e descrição do designer.
	 *
	 * @param array  $output
	 * @param string $taxonomy
	 * @param WP_Term $term
	 * @param WP_Term|null $parent
	 * @return array
	 */
	public static function add_infobox( $output, $taxonomy, $term, $parent ) {
		// Só aplicar se for mesmo a 'cover_designer'.
		if ( self::TAXONOMY !== $taxonomy ) {
			return $output;
		}

		// Recupera metas
		$socials     = get_term_meta( $term->term_id, self::META_SOCIALS, true );
		$description = get_term_meta( $term->term_id, self::META_DESCRIPTION, true );
		$image       = get_term_meta( $term->term_id, self::META_IMAGE, true );

		ob_start();
		?>
		<div class="cover-designer-infobox-wrapper">
			<div class="cover-designer-profile-box">
				<?php if ( $image ) : ?>
					<div class="designer-box-item box-image">
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $term->name ); ?>">
					</div>
				<?php endif; ?>
				<?php if ( $socials ) : ?>
					<div class="designer-box-item">
						<strong><?php esc_html_e( 'Redes Sociais', 'text-domain' ); ?></strong>
						<ul class="designer-socials">
							<?php
							$social_list = preg_split( '/[\r\n,]+/', $socials, -1, PREG_SPLIT_NO_EMPTY );
							foreach ( $social_list as $social ) {
								$icon = self::get_social_icon( $social );
								echo '<li><a href="' . esc_url( $social ) . '" target="_blank" title="' . esc_attr( $social ) . '">' . $icon . '</a></li>';
							}
							?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
			<div class="cover-designer-content">
				<h1 class="designer-name"><?php echo esc_html( $term->name ); ?></h1>
				<?php if ( $description ) : ?>
					<div class="designer-description">
						<?php echo wpautop( wp_kses_post( $description ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="cover-designer-works-title">
			<h2><?php esc_html_e( 'Meus trabalhos aqui na Illusia:', 'text-domain' ); ?></h2>
		</div>
		<?php
		$output['cover_designer_infobox'] = ob_get_clean();
		return $output;
	}

	/**
	 * Sobrescreve o heading padrão do arquivo da taxonomia,
	 * exibindo apenas o nome do termo e, se houver, o nome do pai.
	 *
	 * @param array  $output
	 * @param string $taxonomy
	 * @param WP_Term $term
	 * @param WP_Term|null $parent
	 * @return array
	 */
	public static function override_archive_heading( $output, $taxonomy, $term, $parent ) {
		if ( self::TAXONOMY === $taxonomy ) {
			$output['heading'] = sprintf(
				'<h1 class="archive__heading"><span style="color: var(--inline-link-color);">%s</span>%s</h1>',
				esc_html( $term->name ),
				$parent ? ' (' . esc_html( $parent->name ) . ')' : ''
			);
		}
		return $output;
	}

	/**
	 * Retorna o ícone apropriado (fontawesome) para determinada URL de rede social,
	 * de acordo com substring (facebook.com, instagram, etc.).
	 *
	 * @param string $url
	 * @return string HTML do ícone <i>...
	 */
	private static function get_social_icon( $url ) {
		if ( strpos( $url, 'facebook.com' ) !== false )  return '<i class="fab fa-facebook-f"></i>';
		if ( strpos( $url, 'twitter.com' ) !== false )   return '<i class="fab fa-twitter"></i>';
		if ( strpos( $url, 'x.com' ) !== false )         return '<i class="fab fa-x-twitter"></i>';
		if ( strpos( $url, 'instagram.com' ) !== false ) return '<i class="fab fa-instagram"></i>';
		if ( strpos( $url, 'linkedin.com' ) !== false )  return '<i class="fab fa-linkedin-in"></i>';
		if ( strpos( $url, 'youtube.com' ) !== false )   return '<i class="fab fa-youtube"></i>';
		if ( strpos( $url, 'discord.com' ) !== false )   return '<i class="fab fa-discord"></i>';
		if ( strpos( $url, 'pinterest.com' ) !== false ) return '<i class="fab fa-pinterest"></i>';
		if ( strpos( $url, 'behance.net' ) !== false )   return '<i class="fab fa-behance"></i>';
		if ( strpos( $url, 'dribbble.com' ) !== false )  return '<i class="fab fa-dribbble"></i>';
		return '<i class="fas fa-globe"></i>';
	}

	/**
	 * Carrega script JS no admin para preview dinâmico da URL da imagem.
	 * Assim, enquanto digita no campo, o preview <img> é atualizado.
	 *
	 * @param string $hook Hook da tela atual (ex.: edit-tags.php, term.php).
	 */
	public static function admin_enqueue_scripts( $hook ) {
		// Verifica se estamos na tela de edição/adição de termos (edit-tags.php ou term.php).
		// Exemplo: Ao editar taxonomias, $hook pode ser 'edit-tags.php' ou 'term.php'.
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}

		// Checa se a query param 'taxonomy=cover_designer' (nossa tax).
		if ( ! isset( $_GET['taxonomy'] ) || $_GET['taxonomy'] !== self::TAXONOMY ) {
			return;
		}

		// Registramos um handle fictício p/ injetar JS inline.
		wp_register_script( 'cover-designer-admin', false, [ 'jquery' ], null, true );

		// Script de preview dinâmico:
		$script = <<<'JS'
jQuery(document).ready(function($){
	// Seleciona o input.
	var $input = $('#cover_designer_image');
	if(!$input.length) return;

	// Seleciona/Cria <img> de preview.
	// Como definimos no HTML, deve ter id='cover_designer_image_preview'.
	var $preview = $('#cover_designer_image_preview');

	// Se não existir, podemos criar dinamicamente (ex. na tela de Add).
	if(!$preview.length){
		$input.after('<div style="margin-top:10px;"><img id="cover_designer_image_preview" src="" style="max-width:150px; height:auto; border:1px solid #ccc; display:none; margin-top:5px;" /></div>');
		$preview = $('#cover_designer_image_preview');
	}

	// Ao digitar no input, atualiza o 'src' do preview.
	$input.on('input', function(){
		var newVal = $(this).val().trim();
		if(newVal){
			$preview.attr('src', newVal).show();
		} else {
			$preview.hide();
		}
	});
});
JS;

		// Adiciona script inline e enfileira.
		wp_add_inline_script( 'cover-designer-admin', $script );
		wp_enqueue_script( 'cover-designer-admin' );
	}
}

// Inicializa a classe.
Cover_Designer_Taxonomy::init();
