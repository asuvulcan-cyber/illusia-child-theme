<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Impede acesso direto.
}

if ( ! class_exists( 'FCN_Character' ) ) {

    class FCN_Character {

        /**
         * Cache estático para os metadados dos termos.
         *
         * @var array
         */
        protected static $meta_cache = array();

        /**
         * Recupera os metadados de um termo utilizando cache estático.
         *
         * @param int $term_id
         * @return array
         */
        protected static function get_cached_term_meta( $term_id ) {
            if ( ! isset( self::$meta_cache[ $term_id ] ) ) {
                self::$meta_cache[ $term_id ] = get_term_meta( $term_id );
            }
            return self::$meta_cache[ $term_id ];
        }

        /**
         * Retorna a configuração dos campos personalizados.
         *
         * Cada item define:
         * - label: o rótulo do campo
         * - type: 'text', 'textarea' ou 'checkbox'
         * - rows: (opcional) número de linhas para textarea
         * - description: a descrição do campo
         */
        private static function get_fields_config() {
            return array(
                'fcn_char_is_org' => array(
                    'label'       => __('Este termo é uma Organização?', 'text-domain'),
                    'type'        => 'checkbox',
                    'description' => __('Marque para termos como “Corporação”, “Clã”, etc.', 'text-domain'),
                ),
                'fcn_char_full_name' => array(
                    'label'       => __('Nome Completo / Identidade', 'text-domain'),
                    'type'        => 'text',
                    'description' => __('Ex: Duncan Abnomar, “Corporação X”, etc.', 'text-domain'),
                ),
                'fcn_char_nickname' => array(
                    'label'       => __('Apelido / Codinome', 'text-domain'),
                    'type'        => 'text',
                    'description' => __('Separe vários apelidos por vírgula; cada um será exibido em uma linha.', 'text-domain'),
                ),
                'fcn_char_appearance' => array(
                    'label'       => __('Aparência', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 2,
                    'description' => __('Breve descrição visual ou física.', 'text-domain'),
                ),
                'fcn_char_bio' => array(
                    'label'       => __('Biografia Detalhada', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 5,
                    'description' => __('Escreva a história do personagem/organização.', 'text-domain'),
                ),
                'fcn_char_attributes' => array(
                    'label'       => __('Atributos Principais', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 2,
                    'description' => __('Ex: Força: 10, Agilidade: 8, Habilidades especiais etc.', 'text-domain'),
                ),
                'fcn_char_status' => array(
                    'label'       => __('Status Atual', 'text-domain'),
                    'type'        => 'text',
                    'description' => __('Ex: Vivo, morto, desaparecido, etc.', 'text-domain'),
                ),
                'fcn_char_external_links' => array(
                    'label'       => __('Links Externos / Referências', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 2,
                    'description' => __('URLs de wikis, páginas de livros, etc. Uma por linha.', 'text-domain'),
                ),
                'fcn_char_image' => array(
                    'label'       => __('Foto de Perfil (URL)', 'text-domain'),
                    'type'        => 'text',
                    'description' => __('URL da imagem principal (avatar).', 'text-domain'),
                ),
                'fcn_char_gallery' => array(
                    'label'       => __('Galeria de Fotos (URLs)', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 3,
                    'description' => __('Separe cada URL por vírgula ou por linha para criar uma galeria.', 'text-domain'),
                ),
                'fcn_char_relationships' => array(
                    'label'       => __('Relacionamentos (Nome|URL)', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 2,
                    'description' => __('Ex: "Fulano|http://exemplo.com, Beltrano|/personagem/beltrano" (separar por vírgula).', 'text-domain'),
                ),
                'fcn_char_origin' => array(
                    'label'       => __('Local de Origem (Nome|URL)', 'text-domain'),
                    'type'        => 'text',
                    'description' => __('Opcionalmente adicione link. Ex: "Reino X|https://exemplo.com" ou só "Reino X".', 'text-domain'),
                ),
                'fcn_char_affiliations' => array(
                    'label'       => __('Afiliações (Nome|URL)', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 2,
                    'description' => __('Ex: "Clã Strian|/tag/strian, Ordem X|http://exemplo.com" (separar por vírgula).', 'text-domain'),
                ),
                'fcn_char_quotes' => array(
                    'label'       => __('Citações / Frases de Efeito', 'text-domain'),
                    'type'        => 'textarea',
                    'rows'        => 2,
                    'description' => __('Exibidas ao lado do box, sem limpar float.', 'text-domain'),
                ),
            );
        }

        /**
         * Inicializa os hooks e shortcodes.
         */
        public static function init() {
            // Formulários de adição e edição de termos
            add_action('fcn_character_add_form_fields', array(__CLASS__, 'add_form_fields'));
            add_action('fcn_character_edit_form_fields', array(__CLASS__, 'edit_form_fields'));
            add_action('created_fcn_character', array(__CLASS__, 'save_fields'));
            add_action('edited_fcn_character', array(__CLASS__, 'save_fields'));

            // Filtros para o heading e infobox na taxonomia
            add_filter('fictioneer_filter_archive_header', array(__CLASS__, 'override_archive_heading'), 20, 4);
            add_filter('fictioneer_filter_archive_header', array(__CLASS__, 'add_character_infobox'), 10, 4);

            // CSS externo para o infobox (melhor cache e desempenho)
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));

            // Shortcode para a galeria de personagens
            add_shortcode('unique_people_gallery', array(__CLASS__, 'unique_people_gallery_shortcode'));
        }

        /**
         * Renderiza o campo para o formulário de adição (markup em div).
         */
        private static function render_add_field($field_key, $config) {
            $html = '';
            if ('checkbox' === $config['type']) {
                $html .= '<div class="form-field term-checklist-wrap">';
                $html .= '<label for="' . esc_attr($field_key) . '">';
                $html .= '<input type="checkbox" name="' . esc_attr($field_key) . '" id="' . esc_attr($field_key) . '" value="1" /> ';
                $html .= esc_html($config['label']) . '</label>';
                $html .= '<p class="description">' . esc_html($config['description']) . '</p>';
                $html .= '</div>';
            } else {
                $html .= '<div class="form-field">';
                $html .= '<label for="' . esc_attr($field_key) . '">' . esc_html($config['label']) . '</label>';
                if ('textarea' === $config['type']) {
                    $rows = isset($config['rows']) ? intval($config['rows']) : 2;
                    $html .= '<textarea name="' . esc_attr($field_key) . '" id="' . esc_attr($field_key) . '" rows="' . $rows . '"></textarea>';
                } else {
                    $html .= '<input type="text" name="' . esc_attr($field_key) . '" id="' . esc_attr($field_key) . '" value="" />';
                }
                $html .= '<p class="description">' . esc_html($config['description']) . '</p>';
                $html .= '</div>';
            }
            echo $html;
        }

        /**
         * Renderiza o campo para o formulário de edição (markup em table row).
         */
        private static function render_edit_field($field_key, $config, $value) {
            $html = '';
            if ('checkbox' === $config['type']) {
                $html .= '<tr class="form-field term-checklist-wrap">';
                $html .= '<th scope="row"><label for="' . esc_attr($field_key) . '">' . esc_html($config['label']) . '</label></th>';
                $html .= '<td>';
                $html .= '<input type="checkbox" name="' . esc_attr($field_key) . '" id="' . esc_attr($field_key) . '" value="1" ' . checked($value, '1', false) . ' />';
                $html .= '</td></tr>';
            } else {
                $html .= '<tr class="form-field">';
                $html .= '<th><label for="' . esc_attr($field_key) . '">' . esc_html($config['label']) . '</label></th>';
                $html .= '<td>';
                if ('textarea' === $config['type']) {
                    $rows = isset($config['rows']) ? intval($config['rows']) : 2;
                    $html .= '<textarea name="' . esc_attr($field_key) . '" id="' . esc_attr($field_key) . '" rows="' . $rows . '">' . esc_textarea($value) . '</textarea>';
                } else {
                    $html .= '<input type="text" name="' . esc_attr($field_key) . '" id="' . esc_attr($field_key) . '" value="' . esc_attr($value) . '" />';
                }
                if (!empty($config['description'])) {
                    $html .= '<p class="description">' . esc_html($config['description']) . '</p>';
                }
                $html .= '</td></tr>';
            }
            echo $html;
        }

        /**
         * Exibe os campos personalizados no formulário de adição.
         */
        public static function add_form_fields() {
            foreach (self::get_fields_config() as $field_key => $config) {
                self::render_add_field($field_key, $config);
            }
        }

        /**
         * Exibe os campos personalizados no formulário de edição.
         *
         * Usa uma única chamada a get_term_meta para reduzir queries.
         */
        public static function edit_form_fields($term) {
            $meta = self::get_cached_term_meta($term->term_id);
            foreach (self::get_fields_config() as $field_key => $config) {
                $value = isset($meta[$field_key][0]) ? $meta[$field_key][0] : '';
                self::render_edit_field($field_key, $config, $value);
            }
        }

        /**
         * Salva os metadados personalizados ao criar ou editar um termo.
         */
        public static function save_fields($term_id) {
            foreach (self::get_fields_config() as $field_key => $config) {
                if ('checkbox' === $config['type']) {
                    $value = isset($_POST[$field_key]) ? '1' : '';
                } elseif (isset($_POST[$field_key])) {
                    $value = wp_kses_post($_POST[$field_key]);
                } else {
                    $value = '';
                }
                update_term_meta($term_id, $field_key, $value);
            }
            // Limpa o cache estático para esse termo, se existir.
            if ( isset(self::$meta_cache[$term_id]) ) {
                unset(self::$meta_cache[$term_id]);
            }
        }

        /**
         * Sobrescreve o heading do arquivo para a taxonomia fcn_character.
         */
        public static function override_archive_heading($output, $taxonomy, $term, $parent) {
            if ('fcn_character' === $taxonomy) {
                $output['heading'] = sprintf(
                    '<h1 class="archive__heading"><span style="color: var(--inline-link-color);">%s</span>%s</h1>',
                    esc_html($term->name),
                    $parent ? ' (' . esc_html($parent->name) . ')' : ''
                );
            }
            return $output;
        }

        /**
         * Adiciona o infobox de personagem no header.
         *
         * Utiliza o cache estático para reduzir chamadas duplicadas.
         */
        public static function add_character_infobox($output, $taxonomy, $term, $parent) {
            if ('fcn_character' !== $taxonomy) {
                return $output;
            }

            // Recupera os metadados usando o cache.
            $meta = self::get_cached_term_meta($term->term_id);

            $is_org            = isset($meta['fcn_char_is_org'][0]) ? $meta['fcn_char_is_org'][0] : '';
            $full_name         = isset($meta['fcn_char_full_name'][0]) ? $meta['fcn_char_full_name'][0] : '';
            $nickname_raw      = isset($meta['fcn_char_nickname'][0]) ? $meta['fcn_char_nickname'][0] : '';
            $appearance_raw    = isset($meta['fcn_char_appearance'][0]) ? $meta['fcn_char_appearance'][0] : '';
            $bio               = isset($meta['fcn_char_bio'][0]) ? $meta['fcn_char_bio'][0] : '';
            $attributes        = isset($meta['fcn_char_attributes'][0]) ? $meta['fcn_char_attributes'][0] : '';
            $status            = isset($meta['fcn_char_status'][0]) ? $meta['fcn_char_status'][0] : '';
            $ext_links         = isset($meta['fcn_char_external_links'][0]) ? $meta['fcn_char_external_links'][0] : '';
            $image_url         = isset($meta['fcn_char_image'][0]) ? $meta['fcn_char_image'][0] : '';
            $gallery_raw       = isset($meta['fcn_char_gallery'][0]) ? $meta['fcn_char_gallery'][0] : '';
            $relationships_raw = isset($meta['fcn_char_relationships'][0]) ? $meta['fcn_char_relationships'][0] : '';
            $origin_raw        = isset($meta['fcn_char_origin'][0]) ? $meta['fcn_char_origin'][0] : '';
            $affiliations_raw  = isset($meta['fcn_char_affiliations'][0]) ? $meta['fcn_char_affiliations'][0] : '';
            $quotes_raw        = isset($meta['fcn_char_quotes'][0]) ? $meta['fcn_char_quotes'][0] : '';

            // Processa apelidos
            $nickname_html = '';
            if ( ! empty( $nickname_raw ) ) {
                $nicks = array_filter( array_map( 'trim', explode( ',', $nickname_raw ) ) );
                if ( $nicks ) {
                    $nickname_html = '<ul>';
                    foreach ( $nicks as $nick ) {
                        $nickname_html .= sprintf( '<li>%s</li>', esc_html( $nick ) );
                    }
                    $nickname_html .= '</ul>';
                }
            }

            // Processa afiliações
            $aff_label = $is_org ? 'Afiliado(s)' : 'Afiliação(ões)';
            $aff_html  = '';
            if ( ! empty( $affiliations_raw ) ) {
                $items = array_filter( array_map( 'trim', explode( ',', $affiliations_raw ) ) );
                if ( $items ) {
                    $aff_html = '<ul>';
                    foreach ( $items as $it ) {
                        if ( strpos( $it, '|' ) !== false ) {
                            list( $lab, $url ) = array_map( 'trim', explode( '|', $it, 2 ) );
                            $aff_html .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $lab ) );
                        } else {
                            $aff_html .= sprintf( '<li>%s</li>', esc_html( $it ) );
                        }
                    }
                    $aff_html .= '</ul>';
                }
            }

            // Processa relacionamentos
            $relationships_html = '';
            if ( ! empty( $relationships_raw ) ) {
                $rels = array_filter( array_map( 'trim', explode( ',', $relationships_raw ) ) );
                if ( $rels ) {
                    $relationships_html = '<ul>';
                    foreach ( $rels as $r ) {
                        if ( strpos( $r, '|' ) !== false ) {
                            list( $lab, $url ) = array_map( 'trim', explode( '|', $r, 2 ) );
                            $relationships_html .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $lab ) );
                        } else {
                            $relationships_html .= sprintf( '<li>%s</li>', esc_html( $r ) );
                        }
                    }
                    $relationships_html .= '</ul>';
                }
            }

            // Processa local de origem
            $origin_html = '';
            if ( ! empty( $origin_raw ) ) {
                if ( strpos( $origin_raw, '|' ) !== false ) {
                    list( $lab, $url ) = array_map( 'trim', explode( '|', $origin_raw, 2 ) );
                    $origin_html = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $lab ) );
                } else {
                    $origin_html = esc_html( $origin_raw );
                }
            }

            // Bloco de Aparência
            $appearance_html = '';
            if ( ! empty( $appearance_raw ) ) {
                $appearance_html  = '<div class="char-appearance-block">';
                $appearance_html .= '<h3>Aparência</h3>';
                $appearance_html .= wpautop( wp_kses_post( $appearance_raw ) );
                $appearance_html .= '</div>';
            }

            // Bloco de Biografia
            $bio_html = '';
            if ( ! empty( $bio ) ) {
                $bio_html  = '<div class="char-bio-block">';
                $bio_html .= '<h3>Biografia</h3>';
                $bio_html .= wpautop( wp_kses_post( $bio ) );
                $bio_html .= '</div>';
            }

            // Bloco de Citações
            $quotes_html = '';
            if ( ! empty( $quotes_raw ) ) {
                $quotes_html  = '<blockquote class="wp-block-quote character-quotes">';
                $quotes_html .= wpautop( wp_kses_post( $quotes_raw ) );
                $nome_citacao = ! empty( $full_name ) ? $full_name : $term->name;
                $quotes_html .= '<cite>' . esc_html( $nome_citacao ) . '</cite>';
                $quotes_html .= '</blockquote>';
            }

            // Bloco de Galeria
            $gallery_html = '';
            if ( ! empty( $gallery_raw ) ) {
                $gals = preg_split( "/[\n,]+/", $gallery_raw, -1, PREG_SPLIT_NO_EMPTY );
                if ( $gals ) {
                    $gallery_html  = '<div class="character-gallery-grid">';
                    foreach ( $gals as $g ) {
                        $g = trim( $g );
                        if ( $g ) {
                            $parts       = explode( '|', $g, 2 );
                            $img_url     = trim( $parts[0] );
                            $img_caption = isset( $parts[1] ) ? trim( $parts[1] ) : '';
                            $gallery_html .= sprintf(
                                '<div class="gallery-item">
                                    <a href="%1$s" data-fancybox="gallery" data-caption="%2$s">
                                       <img src="%1$s" alt="%3$s">
                                    </a>
                                 </div>',
                                esc_url( $img_url ),
                                esc_attr( $img_caption ),
                                esc_attr( ! empty( $full_name ) ? $full_name : $term->name )
                            );
                        }
                    }
                    $gallery_html .= '</div>';
                }
            }

            ob_start();
            ?>
            <div class="character-infobox-wrapper">
                <!-- Coluna Esquerda: Perfil -->
                <div class="character-profile-box">
                    <?php if ( ! empty( $image_url ) ) : ?>
                        <div class="char-box-item box-image">
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( ! empty( $full_name ) ? $full_name : $term->name ); ?>">
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $full_name ) ) : ?>
                        <div class="char-box-item box-fullname">
                            <strong>Nome:</strong> <em><?php echo esc_html( $full_name ); ?></em>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $nickname_html ) ) : ?>
                        <div class="char-box-item box-nickname">
                            <strong>Alias:</strong><br><em><?php echo $nickname_html; ?></em>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $status ) ) : ?>
                        <div class="char-box-item box-status">
                            <strong>Status:</strong> <em><?php echo esc_html( $status ); ?></em>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $origin_html ) ) : ?>
                        <div class="char-box-item box-origin">
                            <strong>Origem:</strong> <em><?php echo $origin_html; ?></em>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $aff_html ) ) : ?>
                        <div class="char-box-item box-affiliations">
                            <strong><?php echo esc_html( $aff_label ); ?>:</strong><br><em><?php echo $aff_html; ?></em>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $relationships_html ) ) : ?>
                        <div class="char-box-item box-relationships">
                            <strong>Relacionamentos:</strong><br><em><?php echo $relationships_html; ?></em>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $attributes ) ) : ?>
                        <div class="char-box-item box-attributes">
                            <strong>Atributos:</strong><br><em><?php echo nl2br(esc_html($attributes)); ?></em>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Coluna Direita: Conteúdo -->
                <div class="character-content">
                    <?php if ( ! empty( $quotes_html ) ) : ?>
                        <div class="char-quote-block">
                            <?php echo $quotes_html; ?>
                        </div>
                    <?php endif; ?>
                    <?php echo $appearance_html; ?>
                    <?php echo $bio_html; ?>
                    <?php if ( ! empty( $gallery_html ) ) : ?>
                        <div class="char-gallery-block">
                            <h3>Galeria de Fotos</h3>
                            <?php echo $gallery_html; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $output['character_infobox'] = ob_get_clean();
            return $output;
        }

        /**
         * Enfileira o CSS externo para o infobox, permitindo melhor cache no navegador.
         */
        public static function enqueue_styles() {
            if (is_tax('fcn_character')) {
                wp_enqueue_style('child-theme-infobox', get_stylesheet_directory_uri() . '/css/infobox-char.css', array(), '1.0');
            }
        }

		/**
		 * Shortcode [unique_people_gallery] para exibir a galeria de personagens, com paginação e limite.
		 *
		 * Exemplos de uso:
		 *   [unique_people_gallery story_id="123" columns="4" limit="12"]
		 *     -> Mostra 12 personagens por página (4 colunas), para a história ID=123
		 */
		public static function unique_people_gallery_shortcode($atts) {
			// Define atributos padrão do shortcode
			$atts = shortcode_atts(array(
				'story_id'    => get_the_ID(),  // ID da história (fcn_story)
				'columns'     => 3,            // número de colunas na grid
				'limit'       => 20,           // número máximo de personagens por página
				'paged'       => !empty($_GET['pg']) ? intval($_GET['pg']) : 1, // página atual, via ?pg=2 (por exemplo)
				'placeholder' => get_stylesheet_directory_uri() . '/images/site-logo-placeholder.png',
			), $atts, 'unique_people_gallery');

			// Valida se é uma história de fato
			$story_id = fictioneer_validate_id($atts['story_id'], 'fcn_story');
			if (!$story_id) {
				return '';
			}

			// Busca os dados da história em cache (transient) ou carrega se ainda não estiver.
			$transient_key = 'story_data_' . $story_id;
			$story_data = get_transient($transient_key);
			if (false === $story_data) {
				$story_data = fictioneer_get_story_data($story_id);
				// Armazena no transient por 12 horas
				set_transient($transient_key, $story_data, 12 * HOUR_IN_SECONDS);
			}

			// Se não houver dados ou estiver vazio, sai.
			if (empty($story_data) || empty($story_data['characters'])) {
				return '';
			}

			// Array de todos os personagens vinculados à história
			$characters = $story_data['characters'];

			// Cálculo de paginação
			$limit = max(1, absint($atts['limit']));
			$paged = max(1, absint($atts['paged']));
			$offset = ($paged - 1) * $limit;

			// Fatiar (slice) o array de personagens para obter apenas a página atual
			$paged_characters = array_slice($characters, $offset, $limit);

			// Cálculo de total de páginas
			$total_characters = count($characters);
			$total_pages = ceil($total_characters / $limit);

			// Quantidade de colunas (ajusta se quiser evitar valores muito altos)
			$columns = max(1, absint($atts['columns']));

			// Construção do HTML de saída
			ob_start();
			?>
			<div class="unique-people-gallery" style="display: grid; grid-template-columns: repeat(<?php echo $columns; ?>, 1fr); gap: 1rem;">
				<?php foreach ($paged_characters as $char):
					// Cada $char deve ser um objeto WP_Term
					if (!is_a($char, 'WP_Term')) {
						continue;
					}

					// Tenta obter a imagem de perfil
					$image_url = get_term_meta($char->term_id, 'fcn_char_image', true);
					$is_placeholder = false;
					if (empty($image_url)) {
						$image_url = $atts['placeholder'];
						$is_placeholder = true;
					}

					// Link do termo
					$term_link = get_term_link($char);
					if (is_wp_error($term_link)) {
						$term_link = '';
					}

					// Nome do personagem
					$char_name = esc_html($char->name);
					?>
					<div class="unique-people-item">
						<a href="<?php echo esc_url($term_link); ?>" class="unique-people-link">
							<img
								src="<?php echo esc_url($image_url); ?>"
								alt="<?php echo esc_attr($char_name); ?>"
								class="unique-people-image<?php echo $is_placeholder ? ' unique-people-placeholder' : ''; ?>"
								loading="lazy" 
							/>
							<p class="unique-people-name"><?php echo $char_name; ?></p>
						</a>
					</div>
				<?php endforeach; ?>
			</div>

			<?php 
			// Se há mais de uma página, exibe a paginação
			if ($total_pages > 1): ?>
				<div class="gallery-pagination" style="margin-top: 1rem;">
					<?php for ($page = 1; $page <= $total_pages; $page++):
						$active_style = ($page == $paged) ? ' style="font-weight:bold;"' : '';
						// Monta o link adicionando ?pg=N (ou substituindo, se já existir)
						$link = add_query_arg('pg', $page);
						?>
						<a href="<?php echo esc_url($link); ?>"<?php echo $active_style; ?>>
							<?php echo $page; ?>
						</a>
						<?php if ($page < $total_pages) echo ' | '; ?>
					<?php endfor; ?>
				</div>
			<?php endif; ?>

			<?php
			return ob_get_clean();
		}

    }

    FCN_Character::init();
}

if ( ! class_exists( 'FCN_Character_Owner_Manager' ) ) {

    class FCN_Character_Owner_Manager {

        /**
         * Inicializa os hooks e filtros.
         */
        public static function init() {
            // Exibe o campo manual no formulário de adição e edição
            add_action( 'fcn_character_add_form_fields', array( __CLASS__, 'owner_field_add' ) );
            add_action( 'fcn_character_edit_form_fields', array( __CLASS__, 'owner_field_edit' ) );
            add_action( 'created_fcn_character', array( __CLASS__, 'save_owner_edit' ) );
            add_action( 'edited_fcn_character', array( __CLASS__, 'save_owner_edit' ) );
            // Restringe edição direta para termos que não pertencem ao usuário
            add_action( 'current_screen', array( __CLASS__, 'block_direct_editing' ) );
            // Remove ações de edição rápida na listagem de termos
            add_filter( 'tag_row_actions', array( __CLASS__, 'filter_tag_row_actions' ), 10, 2 );
            // Oculta o menu "Personagens" para usuários sem termos vinculados a eles
            add_action( 'admin_menu', array( __CLASS__, 'hide_character_menu' ), 999 );
        }

        /**
         * Exibe o campo manual no formulário de adição para que o usuário se autoatribua como dono.
         */
        public static function owner_field_add() {
            wp_nonce_field( 'save_taxonomy_owner', 'taxonomy_owner_nonce' );
            ?>
            <div class="form-field term-checklist-wrap">
                <label for="fcn_is_owner"><?php esc_html_e( 'Eu sou o dono deste personagem', 'Illusia' ); ?></label>
                <input type="checkbox" name="fcn_is_owner" id="fcn_is_owner" value="1" />
                <p class="description"><?php esc_html_e( 'Marque se você é o dono deste personagem.', 'Illusia' ); ?></p>
            </div>
            <?php
        }

        /**
         * Exibe o campo manual no formulário de edição para que o usuário se autoatribua como dono.
         *
         * @param WP_Term $term O termo atual.
         */
        public static function owner_field_edit( $term ) {
            wp_nonce_field( 'save_taxonomy_owner', 'taxonomy_owner_nonce' );
            $owner_id = get_term_meta( $term->term_id, 'taxonomy_owner', true );
            ?>
            <tr class="form-field">
                <th>
                    <label for="fcn_is_owner"><?php esc_html_e( 'Eu sou o dono deste personagem', 'Illusia' ); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="fcn_is_owner" id="fcn_is_owner" value="1" <?php checked( $owner_id, get_current_user_id() ); ?> />
                    <p class="description"><?php esc_html_e( 'Marque se você é o dono deste personagem.', 'Illusia' ); ?></p>
                </td>
            </tr>
            <?php
        }

        /**
         * Salva o valor do campo manual.
         *
         * @param int $term_id O ID do termo.
         */
        public static function save_owner_edit( $term_id ) {
            if ( ! isset( $_POST['taxonomy_owner_nonce'] ) || ! wp_verify_nonce( $_POST['taxonomy_owner_nonce'], 'save_taxonomy_owner' ) ) {
                return;
            }

            if ( isset( $_POST['fcn_is_owner'] ) && $_POST['fcn_is_owner'] == 1 ) {
                update_term_meta( $term_id, 'taxonomy_owner', get_current_user_id() );
            } else {
                update_term_meta( $term_id, 'taxonomy_owner', 0 );
            }
        }

        /**
         * Restringe a edição direta de termos para usuários que não são os donos.
         */
        public static function block_direct_editing() {
            $screen = get_current_screen();
            if ( ! $screen || 'term' !== $screen->base || 'fcn_character' !== $screen->taxonomy ) {
                return;
            }
            
            $term_id = isset( $_GET['tag_ID'] ) ? absint( $_GET['tag_ID'] ) : 0;
            if ( ! $term_id ) {
                return;
            }
            
            $owner_id = get_term_meta( $term_id, 'taxonomy_owner', true );
            if ( ! current_user_can( 'manage_options' ) && absint( $owner_id ) !== get_current_user_id() ) {
                add_action( 'admin_footer', array( __CLASS__, 'restrict_editing_popup' ) );
            }
        }

        /**
         * Exibe um popup informando que o usuário não pode editar o termo.
         */
        public static function restrict_editing_popup() {
            ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const modalHTML = `
                        <div id="character-restrict-modal" style="
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                            background: rgba(0,0,0,0.5); display: flex;
                            align-items: center; justify-content: center; z-index: 9999;">
                            <div style="
                                background: #fff; padding: 20px; border-radius: 10px;
                                max-width: 400px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                <h2 style="margin-top: 0;"><?php _e( 'Acesso Negado', 'Illusia' ); ?></h2>
                                <p><?php _e( 'Você só pode editar seus próprios personagens.', 'Illusia' ); ?></p>
                                <button id="close-character-modal" style="
                                    background: #0073aa; color: #fff; border: none;
                                    padding: 10px 15px; cursor: pointer; border-radius: 5px;">
                                    <?php _e( 'OK', 'Illusia' ); ?>
                                </button>
                            </div>
                        </div>`;
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                    document.getElementById('close-character-modal').addEventListener('click', () => {
                        window.history.back();
                    });
                });
            </script>
            <?php
        }

        /**
         * Remove as ações de edição rápida na listagem de termos para usuários que não são donos.
         *
         * @param array $actions Ações disponíveis.
         * @param WP_Term $term O termo atual.
         * @return array As ações filtradas.
         */
        public static function filter_tag_row_actions( $actions, $term ) {
            $owner_id = get_term_meta( $term->term_id, 'taxonomy_owner', true );
            if ( ! current_user_can( 'manage_options' ) && absint( $owner_id ) !== get_current_user_id() ) {
                unset( $actions['inline hide-if-no-js'] );
                unset( $actions['edit'] );
            }
            return $actions;
        }

        /**
         * Oculta o menu "Personagens" para usuários que não possuem termos atribuídos a eles.
         */
        public static function hide_character_menu() {
            if ( ! is_admin() || current_user_can( 'manage_options' ) ) {
                return;
            }
            
            $args = array(
                'taxonomy'   => 'fcn_character',
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key'     => 'taxonomy_owner',
                        'value'   => get_current_user_id(),
                        'compare' => '=',
                        'type'    => 'NUMERIC',
                    ),
                ),
            );
            $terms = get_terms( $args );
            if ( empty( $terms ) ) {
                remove_menu_page( 'edit-tags.php?taxonomy=fcn_character' );
            }
        }
    }

    FCN_Character_Owner_Manager::init();
}
?>
