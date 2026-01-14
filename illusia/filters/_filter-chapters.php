<?php
// ============================================
// FILTRO - ANTES DOS COMENTÁRIOS - NOS CAPÍTULO
// ============================================

function add_comment_rules_with_bell_icon( $args ) {
    ?>
    <div class="comment-rules-notifications">
        <!-- Regras dos comentários -->
        <div class="comment-rules">
            <h3>Regras dos Comentários:</h3>
            <ul>
                <li class="regras-comentarios"><strong>‣ Seja respeitoso</strong> e <strong>gentil</strong> com os outros leitores.</li>
                <li class="regras-comentarios"><strong>‣ Evite spoilers</strong> do capítulo ou da história.</li>
                <li class="regras-comentarios"><strong>‣ Comentários ofensivos</strong> serão removidos.</li>
            </ul>
        </div>

        <!-- Instrução de notificação com ícone de sininho -->
        <div class="email-notifications">
            <p>Para receber notificações por e-mail quando seu comentário for respondido, <span class="sininho"> ative o sininho<i class="fas fa-bell"></i></span> ao lado do botão de <strong>Publicar Comentário</strong>.</p>
        </div>
    </div>
    <?php
}

add_action( 'fictioneer_chapter_before_comments', 'add_comment_rules_with_bell_icon' );


// TIMER PARA EXPIRAR A SENHA 

add_action('after_setup_theme', function () {
    // Remove os filtros do tema pai
    remove_filter('the_password_form', 'fictioneer_password_form');
    // remove_filter('the_password_form', 'fictioneer_unlock_with_patreon', 20);
});

add_filter('the_password_form', function () {
    global $post;

    // Obter metadados de expiração da senha
    $expiration_date = get_post_meta($post->ID, 'fictioneer_post_password_expiration_date', true);
    $expiration_timestamp = $expiration_date ? strtotime(get_date_from_gmt($expiration_date)) : null;
    $current_timestamp = time();

    // Se expirado, não exibe nada relacionado ao timer
    if ($expiration_timestamp && $expiration_timestamp <= $current_timestamp) {
        return '
        <form class="post-password-form custom-form" action="' . esc_url(site_url('wp-login.php?action=postpass', 'login_post')) . '" method="post">
            <div class="form-header">
                <h3>Conteúdo Protegido</h3>
                <p>Digite a senha para desbloquear este conteúdo.</p>
            </div>
            <div class="form-body">
                <div class="password-wrapper">
                    <input name="post_password" id="pwbox-' . esc_attr($post->ID) . '" type="password" required placeholder="Digite a senha">
                </div>
                <div class="password-submit">
                    <button type="submit" name="Submit" class="btn-submit">Desbloquear</button>
                    <input type="hidden" name="_wp_http_referer" value="' . esc_attr(wp_unslash($_SERVER['REQUEST_URI'])) . '">
                </div>
            </div>
        </form>';
    }

    // Gera o ID único do campo de senha
    $label = 'pwbox-' . (empty($post->ID) ? wp_generate_uuid4() : $post->ID . '-' . wp_generate_uuid4());

    // Cria o formulário com o timer
    $form = '
    <form class="post-password-form custom-form" action="' . esc_url(site_url('wp-login.php?action=postpass', 'login_post')) . '" method="post">
        <div class="form-header">
            <h3>Conteúdo Protegido</h3>
            <p>Digite a senha para desbloquear este conteúdo.</p>
        </div>
        <div class="form-body">
            <div class="password-wrapper">
                <input name="post_password" id="' . esc_attr($label) . '" type="password" required placeholder="Digite a senha">
            </div>
            <div class="password-submit">
                <button type="submit" name="Submit" class="btn-submit">Desbloquear</button>
                <input type="hidden" name="_wp_http_referer" value="' . esc_attr(wp_unslash($_SERVER['REQUEST_URI'])) . '">
            </div>';

    // Adiciona o timer dentro do formulário
    if ($expiration_timestamp && $expiration_timestamp > $current_timestamp) {
        $time_remaining = calculate_time_difference($expiration_timestamp, $current_timestamp);

        $form .= '<div class="countdown-message" style="margin-top: 15px; text-align: center;">';
        if ($time_remaining['days'] > 0) {
		$form .= "<p>Disponível em:</p><p><strong>{$time_remaining['days']}d, {$time_remaining['hours']}h, {$time_remaining['minutes']}m</strong></p>";
	}	elseif ($time_remaining['hours'] > 0) {
			$form .= "<p>Disponível em:</p><p><strong>{$time_remaining['hours']}h, {$time_remaining['minutes']}m</strong></p>";
	} 	else {
			$form .= "<p>Disponível em:</p><p><strong>{$time_remaining['minutes']}m, {$time_remaining['seconds']}s</strong></p>";
	}
        $form .= '</div>';
    }

    $form .= '</div></form>';

    return $form;
});

/**
 * Calcula a diferença de tempo entre dois timestamps.
 *
 * @param int $future_timestamp Timestamp futuro.
 * @param int $current_timestamp Timestamp atual.
 * @return array Diferença em dias, horas, minutos e segundos.
 */
function calculate_time_difference($future_timestamp, $current_timestamp) {
    $distance = $future_timestamp - $current_timestamp;

    return [
        'days' => floor($distance / (60 * 60 * 24)),
        'hours' => floor(($distance % (60 * 60 * 24)) / (60 * 60)),
        'minutes' => floor(($distance % (60 * 60)) / 60),
        'seconds' => $distance % 60,
    ];
}
