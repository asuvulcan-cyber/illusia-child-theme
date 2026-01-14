<?php
/**
 * Classe principal de recomendação (AVALIAÇÃO POR ESTRELAS) - VERSÃO REATORADA
 *
 * MELHORIAS:
 * - Otimização de performance (remove sync pesado do admin_init).
 * - Compatibilidade com votos antigos (número) e novos (array com timestamp).
 * - Redução de consultas ao banco de dados em cada chamada.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Fictioneer_Recommendation_Refactored')) {
    class Fictioneer_Recommendation_Refactored {

        public static function init() {
            add_action('fictioneer_story_after_content', array(__CLASS__, 'display_buttons'));
            add_action('fictioneer_chapter_before_comments', array(__CLASS__, 'display_buttons'));
            add_action('wp_ajax_process_recommendation_vote', array(__CLASS__, 'process_vote'));
            add_action('wp_ajax_nopriv_process_recommendation_vote', array(__CLASS__, 'process_vote'));

            // MELHORIA 1: A sincronização agora é manual para não sobrecarregar o admin.
            // Para executar, visite: /wp-admin/?sync_fictioneer_votes=true
            if (is_admin() && current_user_can('manage_options') && isset($_GET['sync_fictioneer_votes']) && $_GET['sync_fictioneer_votes'] === 'true') {
                add_action('admin_init', array(__CLASS__, 'sync_existing_votes'));
            }
        }

        public static function display_buttons($hook_args) {
            $content_id = $hook_args['chapter_id'] ?? $hook_args['story_id'] ?? 0;
            if (!$content_id) {
                return;
            }

            $user_id = get_current_user_id();
            $user_votes = get_user_meta($user_id, '_user_votes', true);
            $user_votes = is_array($user_votes) ? $user_votes : array();

            // MELHORIA 2: Lida com ambos os formatos de voto (antigo e novo)
            $current_vote_data = $user_votes[$content_id] ?? null;
            $current_vote_value = is_array($current_vote_data) ? ($current_vote_data['vote'] ?? null) : $current_vote_data;

            // MELHORIA 3: Pega todos os metadados de uma vez para otimizar
            $all_meta = get_post_meta($content_id);
            $vote_counts = array();
            for ($i = 1; $i <= 5; $i++) {
                $vote_counts[$i] = isset($all_meta['_vote_count_' . $i][0]) ? (int) $all_meta['_vote_count_' . $i][0] : 0;
            }
            
            ob_start();
            ?>
            <div class="recommendation-section">
                <h5 class="rating-title">AVALIE ESTE CONTEÚDO</h5>
                <div class="recommendation-buttons">
                    <?php
                    $labels = array(5 => 'Péssimo', 4 => 'Ruim', 3 => 'Regular', 2 => 'Bom', 1 => 'Excelente');
                    foreach ($labels as $vote => $label) :
                        $button_class = ((int)$current_vote_value === $vote) ? 'voted' : '';
                        ?>
                        <button class="_custom-recommend-button <?php echo esc_attr($button_class); ?>" data-vote="<?php echo esc_attr($vote); ?>" data-content-id="<?php echo esc_attr($content_id); ?>" title="<?php echo esc_attr($label); ?>">
                            <i class="<?php echo ((int)$current_vote_value === $vote) ? 'fas' : 'far'; ?> fa-star"></i>
                            <span class="vote-count"><?php echo esc_html($vote_counts[$vote]); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php self::display_percentage($content_id, $all_meta, $vote_counts); // Passa os dados já buscados ?>
                <div class="vote-message"></div>
            </div>
            <?php
            echo ob_get_clean();
        }

        public static function display_percentage($content_id, $all_meta = null, $vote_counts = null) {
            // Se os dados não foram passados, busca (mantém compatibilidade)
            if (is_null($all_meta)) { $all_meta = get_post_meta($content_id); }
            if (is_null($vote_counts)) {
                $vote_counts = [];
                for ($i = 1; $i <= 5; $i++) { $vote_counts[$i] = isset($all_meta['_vote_count_' . $i][0]) ? (int) $all_meta['_vote_count_' . $i][0] : 0; }
            }
            
            $percentage = calculate_recommendation_percentage($content_id); // Assumindo que esta função externa existe e funciona
            $total_votes = array_sum($vote_counts);
            ?>
            <div class="recommendation-result">
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                </div>
                <div class="percentage-text">
                    <i class="fas fa-star"></i>
                    <?php printf('Avaliação: %s%% (%d votos)', $percentage, $total_votes); ?>
                </div>
            </div>
            <?php
        }

        public static function process_vote() {
            if (!isset($_POST['security']) || !wp_verify_nonce(wp_unslash($_POST['security']), 'recommendation_nonce')) {
                wp_send_json_error(['message' => 'Falha na verificação de segurança'], 403);
            }
            if (!($user_id = get_current_user_id())) {
                wp_send_json_error(['message' => 'Você precisa estar logado para votar'], 401);
            }
            $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
            $vote = isset($_POST['vote']) ? intval($_POST['vote']) : 0;
            if (!$content_id || $vote < 1 || $vote > 5) {
                wp_send_json_error(['message' => 'Dados inválidos enviados'], 400);
            }

            // --- Início da Lógica de Votação Otimizada ---
            $user_votes = get_user_meta($user_id, '_user_votes', true);
            $user_votes = is_array($user_votes) ? $user_votes : array();

            // MELHORIA 2: Lida com ambos os formatos de voto
            $old_vote_data = $user_votes[$content_id] ?? null;
            $old_vote_value = is_array($old_vote_data) ? ($old_vote_data['vote'] ?? null) : $old_vote_data;

            // MELHORIA 3: Pega todos os metadados de uma vez
            $all_meta = get_post_meta($content_id);
            $vote_counts = [];
            for ($i = 1; $i <= 5; $i++) {
                $vote_counts[$i] = isset($all_meta['_vote_count_' . $i][0]) ? (int) $all_meta['_vote_count_' . $i][0] : 0;
            }

            // Se já existia um voto, remove a contagem antiga
            if ($old_vote_value !== null) {
                $vote_counts[$old_vote_value] = max(0, $vote_counts[$old_vote_value] - 1);
            }
            
            // Adiciona a nova contagem
            $vote_counts[$vote]++;
            
            // Salva as contagens individuais
            for ($i = 1; $i <= 5; $i++) {
                update_post_meta($content_id, '_vote_count_' . $i, $vote_counts[$i]);
            }

            // Calcula e salva os totais
            $total_votes = array_sum($vote_counts);
            $weighted_votes = 0;
            foreach ($vote_counts as $rating => $count) {
                $weighted_votes += $count * (6 - $rating);
            }
            update_post_meta($content_id, '_total_votes', $total_votes);
            update_post_meta($content_id, '_weighted_votes', $weighted_votes);
            
            // Atualiza o voto do usuário com o novo formato
            $user_votes[$content_id] = ['vote' => $vote, 'timestamp' => time()];
            update_user_meta($user_id, '_user_votes', $user_votes);
            
            $percentage = calculate_recommendation_percentage($content_id);
            
            wp_send_json_success([
                'message'     => 'Voto atualizado com sucesso!',
                'percentage'  => $percentage,
                'total_votes' => $total_votes,
                'vote_counts' => $vote_counts,
                'new_vote'    => $vote,
            ]);
        }

        public static function sync_existing_votes() {
            // Esta função agora só executa se chamada manualmente
            $all_posts = get_posts(['post_type' => ['fcn_story', 'fcn_chapter'], 'numberposts' => -1, 'fields' => 'ids']);
            foreach ($all_posts as $post_id) {
                $vote_counts = [];
                for ($i = 1; $i <= 5; $i++) {
                    $vote_counts[$i] = (int) get_post_meta($post_id, '_vote_count_' . $i, true);
                }
                $total_votes = array_sum($vote_counts);
                $weighted_votes = 0;
                foreach ($vote_counts as $rating => $count) {
                    $weighted_votes += $count * (6 - $rating);
                }
                update_post_meta($post_id, '_total_votes', $total_votes);
                update_post_meta($post_id, '_weighted_votes', $weighted_votes);
            }
            // Adiciona uma mensagem de sucesso no painel admin
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Sincronização de votos concluída com sucesso!</p></div>';
            });
        }
    }
    Fictioneer_Recommendation_Refactored::init();
}