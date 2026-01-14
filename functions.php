<?php

define( 'CHILD_VERSION', '1.0.2' );
define( 'CHILD_NAME', 'Fictioneer Child Theme' );
define( 'FICTIONEER_ENABLE_ALL_AUTHOR_PROFILES', 'false');

/**
 * Retorna uma string formatada usando o fuso horário do WP.
 *
 * Exemplo: get_localized_date_key( 'Ymd' ) => "20250329"
 *
 * @param string $format Formato de data (padrão 'Ymd').
 * @return string
 */
function get_localized_date_key( $format = 'Ymd' ) {
	$local_datetime = new DateTime( 'now', wp_timezone() );
	return $local_datetime->format( $format );
}

/**
 * Retorna as meta_keys para contagem de views com base na data/hora atual no fuso do WP.
 *
 * @return array
 */
function get_view_meta_keys() {
	$local_datetime = new DateTime( 'now', wp_timezone() );
	$year_key       = $local_datetime->format( 'Y' );
	$month_key      = $local_datetime->format( 'Ym' );
	$day_key        = $local_datetime->format( 'Ymd' );
	return array( '_views', '_views_year_' . $year_key, '_views_month_' . $month_key, '_views_day_' . $day_key );
}


/**
 * Enfileira estilos e scripts do tema filho.
 *
 * @return void
 */
 function fictioneer_child_enqueue_styles_and_scripts() {
	$parenthandle = 'fictioneer-application';
	wp_enqueue_style( 'fictioneer-child-style', get_stylesheet_directory_uri() . '/css/fictioneer-child-style.css', array( $parenthandle ) );
	wp_enqueue_style( 'telas-css', get_stylesheet_directory_uri() . '/css/telas.css' );
	wp_enqueue_style( 'divisores-css', get_stylesheet_directory_uri() . '/css/divisores.css' );
	wp_enqueue_style( 'footnote-css', get_stylesheet_directory_uri() . '/css/footnote.css' );
	wp_enqueue_style( 'story-identity-css', get_stylesheet_directory_uri() . '/css/story-identity.css' );
	wp_enqueue_style( 'filter-chapters-css', get_stylesheet_directory_uri() . '/css/filter_chapters.css' );
	wp_enqueue_style( 'design-geral-css', get_stylesheet_directory_uri() . '/css/design-geral.css' );
	wp_enqueue_style( 'patreon-geral-css', get_stylesheet_directory_uri() . '/css/patreon-geral.css' );
	wp_enqueue_style( 'ranking-css', get_stylesheet_directory_uri() . '/css/ranking.css', array(), '1.0' );
	wp_enqueue_style( 'fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css', array(), null );
	wp_register_script( 'child-script-handle', get_stylesheet_directory_uri() . '/js/fictioneer-child-script.js', array( 'fictioneer-application-scripts' ), false, true );
	wp_enqueue_script( 'fictioneer-child-scripts' );
	wp_enqueue_script( 'recommendation-ajax', get_stylesheet_directory_uri() . '/js/recommendation.js', array( 'jquery' ), null, true );
	wp_enqueue_script( 'canvas-confetti', 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js', array(), '1.5.1', true );
	wp_enqueue_script( 'fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js', array(), null, true );
	wp_localize_script( 'recommendation-ajax', 'recommendation_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'recommendation_nonce' ) ) );
}
add_action( 'wp_enqueue_scripts', 'fictioneer_child_enqueue_styles_and_scripts', 99 );




/**
 * Ações do tema pai no front-end (vazio como exemplo).
 *
 * @return void
 */
function fictioneer_child_customize_parent() {}
add_action( 'init', 'fictioneer_child_customize_parent' );

/**
 * Remove ou adiciona filtros no painel administrativo.
 *
 * @return void
 */
function fictioneer_child_customize_admin() {if ( ! current_user_can( 'administrator' ) ) {remove_action( 'add_meta_boxes', 'fictioneer_add_seo_metabox', 10 );}}
add_action( 'admin_init', 'fictioneer_child_customize_admin' );

/**
 * Define "História" como padrão nas pesquisas.
 *
 * @param array $args Argumentos do formulário de busca.
 * @return array
 */
function fictioneer_child_pre_select_search_page_post_type( $args ) {$args['preselect_type'] = 'fcn_story';	return $args;}
add_filter( 'fictioneer_filter_default_search_form_args', 'fictioneer_child_pre_select_search_page_post_type' );

/**
 * Altera o rótulo do post type "post" para "Blog".
 *
 * @return void
 */
function fictioneer_child_change_post_labels_to_blog() {global $wp_post_types;	if ( isset( $wp_post_types['post'] ) ) {$wp_post_types['post']->labels->menu_name = 'Blog';}}
add_action( 'init', 'fictioneer_child_change_post_labels_to_blog' );

/**
 * Habilita transient da lista de capítulos.
 */
add_filter('fictioneer_filter_enable_chapter_list_transients',	function( $bool, $post_id ) {return true;},	10,	2);

/**
 * Remove o filtro wptexturize do título.
 */
remove_filter( 'the_title', 'wptexturize' );

/**
 * Desabilita o multiusuário (caso não definido).
 */
if ( ! defined( 'FICTIONEER_MU_REGISTRATION' ) ) {define( 'FICTIONEER_MU_REGISTRATION', false );}

/* REQUIRE_ONCE - Módulos do tema filho */

// HELPERS

require_once get_stylesheet_directory() . '/illusia/helpers/recommendation_helper.php';             // Helpers de recomendação

// TAXONOMIAS
//require_once get_stylesheet_directory() . '/illusia/taxonomias/_tax-personagem.php';                // Página de Personagem
require_once get_stylesheet_directory() . '/illusia/taxonomias/_tax-designers.php';                 // Página de Designer

// METABOXES
require_once get_stylesheet_directory() . '/illusia/metaboxes/_metaboxes-support.php';              // Metabox de links de suporte

// PARTIALS
require_once get_stylesheet_directory() . '/illusia/partials/card_footer_info.php';                 // Informações de rodapé

// CLASSES
require_once get_stylesheet_directory() . '/illusia/classes/fictioneer_identity.php';               // Identidade customizada

// FILTERS
require_once get_stylesheet_directory() . '/illusia/filters/_filter-chapters.php';                  // Filtros para capítulos

// COMPLEMENTOS
require_once get_stylesheet_directory() . '/illusia/complementos/recommendation.php';               // Lógica de recomendação
require_once get_stylesheet_directory() . '/illusia/complementos/release_frequencies.php';          // Frequência de publicação
require_once get_stylesheet_directory() . '/illusia/complementos/views_and_comments.php';           // Contagem de views/comentários
require_once get_stylesheet_directory() . '/illusia/complementos/vote_summation.php';               // Soma total de votos
require_once get_stylesheet_directory() . '/illusia/complementos/_patreon-config.php';              // Opções de Patreon
require_once get_stylesheet_directory() . '/illusia/complementos/_ranking.php';                     // Ranking de histórias
require_once get_stylesheet_directory() . '/illusia/complementos/_telas-e-divisores.php';           // Telas e divisores
require_once get_stylesheet_directory() . '/illusia/complementos/_discord-messages.php';            // Mensagens personalizadas do Discord

/**
 * Shortcode FINAL (v7) com ordenação por PALAVRAS e depois por CAPÍTULOS.
 */
function tabela_acompanhamento_autores_shortcode($atts) {
    $atts = shortcode_atts(
        array( 'data_inicio' => '', 'dias' => 30, 'titulo' => '' ),
        $atts, 'tabela_acompanhamento_autores'
    );

    // Atualizei a chave do cache para v7 para garantir que a nova ordenação seja exibida
    $cache_key = 'tabela_acompanhamento_v7_' . md5(serialize($atts));
    $cached_data = get_transient($cache_key);

    if (false !== $cached_data) {
        $tempo_atras = human_time_diff($cached_data['timestamp'], current_time('timestamp')) . ' atrás';
        $html_final = '<div class="tabela-container">';
        $html_final .= $cached_data['html'];
        $html_final .= '<p class="tabela-ultima-atualizacao">Atualizado ' . $tempo_atras . '</p>';
        $html_final .= '</div>';
        return $html_final;
    }

    if (!empty($atts['data_inicio'])) {
        $data_inicio_sql = $atts['data_inicio'];
    } else {
        $dias_para_voltar = absint($atts['dias']);
        $data_inicio_sql = date('Y-m-d', strtotime("-$dias_para_voltar days"));
    }

    global $wpdb;
    $status_validos = array('publish', 'ongoing', 'completed', 'hiatus');
    $meta_key_palavras = '_word_count';

    $query = $wpdb->prepare(
        "SELECT
            story.ID as historia_id,
            story.post_title as historia_titulo,
            story.post_author as autor_id,
            COUNT(chapter.ID) as total_capitulos,
            SUM(CAST(wordcount_meta.meta_value AS UNSIGNED)) as total_palavras
        FROM
            {$wpdb->posts} as chapter
        INNER JOIN
            {$wpdb->postmeta} as story_meta ON (chapter.ID = story_meta.post_id AND story_meta.meta_key = 'fictioneer_chapter_story')
        INNER JOIN
            {$wpdb->posts} as story ON (story_meta.meta_value = story.ID)
        LEFT JOIN
            {$wpdb->postmeta} as wordcount_meta ON (chapter.ID = wordcount_meta.post_id AND wordcount_meta.meta_key = %s)
        WHERE
            chapter.post_type = 'fcn_chapter' AND chapter.post_status = 'publish'
            AND story.post_type = 'fcn_story' AND story.post_status IN ('" . implode("','", $status_validos) . "')
            AND DATE(chapter.post_date) >= %s
        GROUP BY
            story.ID
        -- --- MUDANÇA IMPORTANTE AQUI: NOVA LÓGICA DE ORDENAÇÃO ---
        ORDER BY
            total_palavras DESC, total_capitulos DESC, historia_titulo ASC",
        $meta_key_palavras,
        $data_inicio_sql
    );
    $resultados = $wpdb->get_results($query);

    ob_start();
    if (empty($resultados)) {
        if (!empty($atts['titulo'])) { echo '<h3>' . esc_html($atts['titulo']) . '</h3>'; }
        echo '<p>Nenhum capítulo foi publicado ainda no período do evento. Sejam os primeiros!</p>';
    } else {
        if (!empty($atts['titulo'])) { echo '<h3>' . esc_html($atts['titulo']) . '</h3>'; }
        else {
            $data_formatada = date_i18n('d/m/Y', strtotime($data_inicio_sql));
            echo '<h4>Contagem de capítulos desde ' . $data_formatada . '</h4>';
        }
        ?>
        <table class="tabela-acompanhamento">
            <thead>
                <tr>
                    <th>#</th>
                    <th>História</th>
                    <th>Autor</th>
                    <th>Capítulos</th>
                    <th>Palavras</th>
                </tr>
            </thead>
            <tbody>
                <?php $posicao = 1; foreach ($resultados as $resultado) {
                    $nome_autor = get_the_author_meta('display_name', $resultado->autor_id);
                    $link_historia = get_permalink($resultado->historia_id);
                    ?>
                    <tr>
                        <td><?php echo $posicao++; ?></td>
                        <td><a href="<?php echo esc_url($link_historia); ?>"><?php echo esc_html($resultado->historia_titulo); ?></a></td>
                        <td><?php echo esc_html($nome_autor); ?></td>
                        <td><?php echo $resultado->total_capitulos; ?></td>
                        <td><?php echo number_format_i18n($resultado->total_palavras); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    }
    $html_output = ob_get_clean();

    $data_to_cache = array('html' => $html_output, 'timestamp' => current_time('timestamp'));
    set_transient($cache_key, $data_to_cache, 1 * HOUR_IN_SECONDS);
    
    $html_final = '<div class="tabela-container">';
    $html_final .= $html_output;
    $html_final .= '<p class="tabela-ultima-atualizacao">Atualizado agora</p>';
    $html_final .= '</div>';
    
    return $html_final;
}

// Garante que a versão mais recente do shortcode esteja registrada
remove_shortcode('tabela_acompanhamento_autores');
add_shortcode('tabela_acompanhamento_autores', 'tabela_acompanhamento_autores_shortcode');

/**
 * Gera o HTML e a lógica da página de Relatório de Votos (VERSÃO 5).
 * - Usa a função wp_date() para garantir a exibição correta do fuso horário.
 */
function pagina_relatorio_votos_html_v5() {
    // --- LÓGICA PARA REMOVER VOTO (continua a mesma) ---
    if (isset($_GET['acao']) && $_GET['acao'] === 'remover_voto' && isset($_GET['post_id']) && isset($_GET['user_id'])) {
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'remover_voto_nonce')) {
            // ... (toda a lógica de remoção continua exatamente igual)
            $post_id = intval($_GET['post_id']); $user_id = intval($_GET['user_id']);
            $votos_do_usuario = get_user_meta($user_id, '_user_votes', true);
            if (is_array($votos_do_usuario) && isset($votos_do_usuario[$post_id])) {
                $voto_removido_data = $votos_do_usuario[$post_id];
                $voto_removido_valor = is_array($voto_removido_data) ? $voto_removido_data['vote'] : $voto_removido_data;
                unset($votos_do_usuario[$post_id]);
                update_user_meta($user_id, '_user_votes', $votos_do_usuario);
                update_post_meta($post_id, '_vote_count_' . $voto_removido_valor, max(0, (int) get_post_meta($post_id, '_vote_count_' . $voto_removido_valor, true) - 1));
                update_post_meta($post_id, '_total_votes', max(0, (int) get_post_meta($post_id, '_total_votes', true) - 1));
                $votos_ponderados = (int) get_post_meta($post_id, '_weighted_votes', true);
                update_post_meta($post_id, '_weighted_votes', max(0, $votos_ponderados - (6 - $voto_removido_valor)));
                echo '<div class="notice notice-success is-dismissible"><p>Voto removido com sucesso!</p></div>';
            }
        } else { echo '<div class="notice notice-error is-dismissible"><p>Falha na verificação de segurança.</p></div>'; }
    }

    // --- HTML E LÓGICA DA PÁGINA (com a mudança na data) ---
    $post_id_atual = isset($_POST['post_id_busca']) ? intval($_POST['post_id_busca']) : (isset($_GET['post_id']) ? intval($_GET['post_id']) : '');
    $ordem_atual = isset($_POST['ordem_busca']) ? sanitize_text_field($_POST['ordem_busca']) : 'piores';
    ?>
    <div class="wrap">
        <h1>Relatório de Votos por Conteúdo</h1>
        <p>Digite o ID da história ou do capítulo para ver quem votou e qual nota foi dada.</p>
        
        <form method="post" action="">
            <label for="post_id_busca"><strong>ID do Conteúdo:</strong></label>
            <input type="number" id="post_id_busca" name="post_id_busca" value="<?php echo $post_id_atual; ?>" placeholder="Ex: 185941" required>
            <label for="ordem_busca"><strong>Ordenar por:</strong></label>
            <select id="ordem_busca" name="ordem_busca">
                <option value="piores" <?php selected($ordem_atual, 'piores'); ?>>Piores Notas Primeiro</option>
                <option value="melhores" <?php selected($ordem_atual, 'melhores'); ?>>Melhores Notas Primeiro</option>
            </select>
            <?php submit_button('Buscar Votos'); ?>
        </form>

        <?php
        if (!empty($post_id_atual)) {
            $post_id_alvo = $post_id_atual;
            $titulo_post = get_the_title($post_id_alvo);
            echo '<h2>Resultados para: "' . esc_html($titulo_post) . '" (ID: ' . $post_id_alvo . ')</h2>';

            $todos_usuarios = get_users(); $votantes = array();

            // A lógica para buscar os votantes continua a mesma
            foreach ($todos_usuarios as $usuario) {
                $votos_do_usuario = get_user_meta($usuario->ID, '_user_votes', true);
                if (is_array($votos_do_usuario) && isset($votos_do_usuario[$post_id_alvo])) {
                    $voto_data = $votos_do_usuario[$post_id_alvo];
                    $voto_valor = is_array($voto_data) ? $voto_data['vote'] : $voto_data;
                    $voto_timestamp = is_array($voto_data) ? $voto_data['timestamp'] : null;
                    $votantes[] = array( 'user_id' => $usuario->ID, 'nome' => $usuario->display_name, 'email' => $usuario->user_email, 'voto' => $voto_valor, 'data_voto' => $voto_timestamp, 'registro' => $usuario->user_registered );
                }
            }

            // A lógica de ordenação continua a mesma
            if (!empty($votantes)) {
                usort($votantes, function($a, $b) use ($ordem_atual) {
                    if ($ordem_atual === 'piores') { return $b['voto'] <=> $a['voto']; } else { return $a['voto'] <=> $b['voto']; }
                });

                $labels_votos = array( 5 => '1 Estrela (Péssimo)', 4 => '2 Estrela (Ruim)', 3 => '3 Estrelas (Regular)', 2 => '4 Estrelas (Bom)', 1 => '5 Estrelas (Excelente)' );
                
                // A tabela de exibição
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>ID</th><th style="width: 20%;">Usuário</th><th style="width: 25%;">Email</th><th>Voto Dado</th><th>Data do Voto</th><th>Conta Criada em</th><th style="width: 15%;">Ações</th></tr></thead>';
                echo '<tbody>';
                foreach ($votantes as $votante) {
                    $link_remover = wp_nonce_url(admin_url('admin.php?page=relatorio_votos_slug&acao=remover_voto&post_id=' . $post_id_alvo . '&user_id=' . $votante['user_id']), 'remover_voto_nonce');
                    $voto_label = isset($labels_votos[$votante['voto']]) ? $labels_votos[$votante['voto']] : 'Voto desconhecido';
                    
                    // --- MUDANÇA IMPORTANTE AQUI ---
                    // Usando wp_date() em vez de date_i18n() para garantir o fuso horário correto.
                    $data_voto_str = $votante['data_voto'] ? wp_date('d/m/Y H:i', $votante['data_voto']) : '<em>N/A</em>';
                    // --- FIM DA MUDANÇA ---
                    
                    $data_registro = date_i18n('d/m/Y', strtotime($votante['registro']));

                    // O resto da tabela continua o mesmo
                    echo '<tr>';
                    echo '<td>' . esc_html($votante['user_id']) . '</td>';
                    echo '<td>' . esc_html($votante['nome']) . '</td>';
                    echo '<td>' . esc_html($votante['email']) . '</td>';
                    echo '<td>' . esc_html($voto_label) . '</td>';
                    echo '<td>' . $data_voto_str . '</td>';
                    echo '<td>' . $data_registro . '</td>';
                    echo '<td><a href="' . esc_url($link_remover) . '" style="color: #a00;" onclick="return confirm(\'Tem certeza?\');">Remover Voto</a></td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            } else { echo '<p>Nenhum voto encontrado para este conteúdo.</p>'; }
        }
        ?>
    </div>
    <?php
}

// Lembre-se de apontar o menu para a função correta (v5)
remove_action('admin_menu', 'criar_menu_relatorio_votos'); // Remove a ação antiga se existir
add_action('admin_menu', function() {
    add_menu_page( 'Relatório de Votos', 'Relatório de Votos', 'manage_options', 'relatorio_votos_slug', 'pagina_relatorio_votos_html_v5', 'dashicons-star-filled', 25 );
});

/**
 * Shortcode [ranking_autores] (VERSÃO 2 - CORRIGIDA)
 * Gera uma tabela de ranking de autores, somando capítulos e palavras de todas as suas obras.
 * Inclui um tooltip com o detalhamento por história.
 * CORREÇÃO: Garante que a soma de palavras seja tratada como número.
 */
add_shortcode('ranking_autores', 'gerar_ranking_autores_shortcode');

function gerar_ranking_autores_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'data_inicio' => '',
            'data_fim'    => '',
            'dias'        => 30,
            'titulo'      => '',
        ),
        $atts, 'ranking_autores'
    );

    $cache_key = 'ranking_autores_v2_' . md5(serialize($atts));
    if (false !== ($cached_data = get_transient($cache_key))) {
        // A lógica de exibir o cache continua a mesma
        $tempo_atras = human_time_diff($cached_data['timestamp'], current_time('timestamp')) . ' atrás';
        $html_final = '<div class="tabela-container">';
        $html_final .= $cached_data['html'];
        $html_final .= '<p class="tabela-ultima-atualizacao">Atualizado ' . $tempo_atras . '</p>';
        $html_final .= '</div>';
        return $html_final;
    }

    // Lógica das datas (igual ao shortcode anterior)
    if (!empty($atts['data_inicio'])) {
        $data_inicio_sql = $atts['data_inicio'];
    } else {
        $dias_para_voltar = absint($atts['dias']);
        $data_inicio_sql = date('Y-m-d', strtotime("-$dias_para_voltar days"));
    }
    $data_fim_sql = !empty($atts['data_fim']) ? $atts['data_fim'] : null;

    global $wpdb;
    $status_validos = array('publish', 'ongoing', 'completed', 'hiatus');
    $meta_key_palavras = '_word_count';

    // PASSO 1: A consulta SQL (continua a mesma)
    $sql_base = "SELECT
            story.post_author as autor_id,
            story.post_title as historia_titulo,
            COUNT(chapter.ID) as total_capitulos,
            SUM(CAST(wordcount_meta.meta_value AS UNSIGNED)) as total_palavras
        FROM {$wpdb->posts} as chapter
        INNER JOIN {$wpdb->postmeta} as story_meta ON (chapter.ID = story_meta.post_id AND story_meta.meta_key = 'fictioneer_chapter_story')
        INNER JOIN {$wpdb->posts} as story ON (story_meta.meta_value = story.ID)
        LEFT JOIN {$wpdb->postmeta} as wordcount_meta ON (chapter.ID = wordcount_meta.post_id AND wordcount_meta.meta_key = %s)
        WHERE
            chapter.post_type = 'fcn_chapter' AND chapter.post_status = 'publish'
            AND story.post_type = 'fcn_story' AND story.post_status IN ('" . implode("','", $status_validos) . "')
            AND DATE(chapter.post_date) >= %s";
    $prepare_args = [$meta_key_palavras, $data_inicio_sql];
    if ($data_fim_sql) {
        $sql_base .= " AND DATE(chapter.post_date) <= %s";
        $prepare_args[] = $data_fim_sql;
    }
    $sql_base .= " GROUP BY story.ID"; // Ainda agrupa por história para pegar os detalhes
    $resultados_por_historia = $wpdb->get_results($wpdb->prepare($sql_base, $prepare_args));

    // PASSO 2: Processa os resultados para agrupar por AUTOR (Lógica Reforçada)
    $dados_autores = [];
    if (is_array($resultados_por_historia)) {
        foreach ($resultados_por_historia as $historia) {
            $autor_id = $historia->autor_id;
            if (!isset($dados_autores[$autor_id])) {
                $dados_autores[$autor_id] = [
                    'nome'            => get_the_author_meta('display_name', $autor_id),
                    'total_capitulos' => 0,
                    'total_palavras'  => 0,
                    'historias'       => []
                ];
            }
            // CORREÇÃO: Garante que os valores são tratados como números inteiros
            $dados_autores[$autor_id]['total_capitulos'] += (int) $historia->total_capitulos;
            $dados_autores[$autor_id]['total_palavras']  += (int) $historia->total_palavras;
            $dados_autores[$autor_id]['historias'][] = $historia;
        }
    }

    // PASSO 3: Ordena o ranking de autores
    uasort($dados_autores, function($a, $b) {
        if ($a['total_palavras'] != $b['total_palavras']) {
            return $b['total_palavras'] <=> $a['total_palavras'];
        }
        return $b['total_capitulos'] <=> $a['total_capitulos'];
    });

    // PASSO 4: Gera o HTML
    ob_start();
    
    // Gera o container e o título
    $titulo_html = '';
    if (!empty($atts['titulo'])) { 
        $titulo_html = '<h3>' . esc_html($atts['titulo']) . '</h3>';
    }
    echo '<div class="tabela-container">' . $titulo_html;
    
    if (empty($dados_autores)) {
        echo '<p>Nenhuma postagem encontrada para o período selecionado.</p>';
    } else {
        ?>
        <table class="tabela-acompanhamento">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Autor</th>
                    <th>Total de Capítulos</th>
                    <th>Total de Palavras</th>
                </tr>
            </thead>
            <tbody>
                <?php $posicao = 1; foreach ($dados_autores as $autor) : ?>
                    <?php
                    // Monta o conteúdo do tooltip
                    $tooltip_text = "";
                    foreach ($autor['historias'] as $historia) {
                        $tooltip_text .= esc_html($historia->historia_titulo) . 
                                         ' (' . number_format_i18n($historia->total_capitulos) . ' caps, ' . 
                                         number_format_i18n($historia->total_palavras) . " palavras)\n";
                    }
                    ?>
                    <tr>
                        <td><?php echo $posicao++; ?></td>
                        <td>
                            <span class="author-tooltip" data-tooltip="<?php echo trim($tooltip_text); ?>">
                                <?php echo esc_html($autor['nome']); ?>
                            </span>
                        </td>
                        <td><?php echo number_format_i18n($autor['total_capitulos']); ?></td>
                        <td><?php echo number_format_i18n($autor['total_palavras']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    $html_output = ob_get_clean();

    // Lógica de cache e timestamp final
    $data_to_cache = ['html' => $html_output, 'timestamp' => current_time('timestamp')];
    set_transient($cache_key, $data_to_cache, 1 * HOUR_IN_SECONDS);
    
    $tempo_atras = 'agora'; // Para a primeira exibição
    $html_final = $html_output;
    $html_final .= '<p class="tabela-ultima-atualizacao">Atualizado ' . $tempo_atras . '</p>';
    $html_final .= '</div>'; // Fecha o .tabela-container
    
    return $html_final;
}
