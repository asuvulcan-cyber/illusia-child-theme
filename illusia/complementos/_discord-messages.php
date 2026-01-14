<?php
// =================================================================================
// CÓDIGO COMPLETO E CORRIGIDO PARA A INTEGRAÇÃO COM O DISCORD
// =================================================================================

// ---------------------------------------------------------------------------------
// PARTE 1: CAMPOS PERSONALIZADOS (ID do Discord para Usuários e Histórias)
// ---------------------------------------------------------------------------------

// --- CAMPO "ID DO DISCORD" NO PERFIL DO USUÁRIO ---
function exibir_campo_discord_id_perfil($user) {
    // A função que adiciona a opção de desativar menções já cria o título "Configurações do Discord"
    ?>
    <table class="form-table">
        <tr>
            <th><label for="discord_user_id">ID do Usuário no Discord</label></th>
            <td>
                <input type="text" name="discord_user_id" id="discord_user_id" value="<?php echo esc_attr(get_user_meta($user->ID, 'discord_user_id', true)); ?>" class="regular-text" />
                <br>
                <span class="description">Insira o ID numérico do usuário do Discord para menções.</span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'exibir_campo_discord_id_perfil', 9); // Prioridade 9 para aparecer antes do outro campo
add_action('edit_user_profile', 'exibir_campo_discord_id_perfil', 9);

function salvar_campo_discord_id_perfil($user_id) {
    if (!current_user_can('edit_user', $user_id)) { return false; }
    if (isset($_POST['discord_user_id'])) {
        update_user_meta($user_id, 'discord_user_id', sanitize_text_field($_POST['discord_user_id']));
    }
}
add_action('personal_options_update', 'salvar_campo_discord_id_perfil');
add_action('edit_user_profile_update', 'salvar_campo_discord_id_perfil');


// --- CAMPO "ID DO CARGO DO DISCORD" NAS HISTÓRIAS ---
function adicionar_meta_box_discord_role() {
    add_meta_box('discord_role_meta_box', 'Configurações Discord', 'meta_box_discord_role_html', 'fcn_story', 'side', 'default');
}
add_action('add_meta_boxes', 'adicionar_meta_box_discord_role');

function meta_box_discord_role_html($post) {
    $value = get_post_meta($post->ID, 'discord_role_id', true);
    wp_nonce_field('salvar_discord_role_nonce', 'discord_role_nonce');
    ?>
    <label for="discord_role_id_field"><strong>ID do Cargo (Role):</strong></label>
    <br>
    <input type="text" name="discord_role_id_field" id="discord_role_id_field" value="<?php echo esc_attr($value); ?>" class="widefat">
    <p class="description">Insira a ID do cargo do Discord para notificações desta história.</p>
    <?php
}

function salvar_meta_box_discord_role($post_id) {
    if (!isset($_POST['discord_role_nonce']) || !wp_verify_nonce($_POST['discord_role_nonce'], 'salvar_discord_role_nonce')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }
    if (isset($_POST['discord_role_id_field'])) {
        update_post_meta($post_id, 'discord_role_id', sanitize_text_field($_POST['discord_role_id_field']));
    }
}
add_action('save_post_fcn_story', 'salvar_meta_box_discord_role');

// ---------------------------------------------------------------------------------
// PARTE 2: LÓGICA DE NOTIFICAÇÕES (Comentários e Lançamentos)
// ---------------------------------------------------------------------------------

// --- FUNÇÃO AUXILIAR ATUALIZADA ---
function get_discord_id_by_wp_user_id($wp_user_id) {
    return get_user_meta($wp_user_id, 'discord_user_id', true);
}

// --- LÓGICA DE NOTIFICAÇÃO DE COMENTÁRIOS (UNIFICADA E CORRIGIDA) ---
function gerenciar_notificacao_comentario_discord($message, $comment, $post, $user) {
    $post_author_id = $post->post_author;
    $discord_author_id = get_discord_id_by_wp_user_id($post_author_id);
    
    // Lógica para o segundo webhook ([del] e [ins])
    if (strpos($comment->comment_content, '[del]') !== false || strpos($comment->comment_content, '[ins]') !== false) {
        if ($discord_author_id) {
            $message['content'] = "<@{$discord_author_id}> " . $message['content'];
        }
        // Recomendação: Mova o webhook para as opções do site para não deixá-lo exposto no código.
        $second_webhook = 'https://discord.com/api/webhooks/1301329770662858784/ZA42503H0_VUjurw6k-EL9ENtFQXxKZQ981-cOkm_J1rqJ9NVaSd6Y5gPNTyXpNNSM55';
        fictioneer_discord_send_message($second_webhook, $message);
        
        // Retorna null para IMPEDIR o envio ao webhook principal, como era antes.
        return null; 
    }

    // Lógica para o webhook principal (menção ao autor)
    $disable_mentions = get_user_meta($post_author_id, 'disable_discord_mentions', true);
    if (!$disable_mentions && $discord_author_id) {
        $mention = "<@{$discord_author_id}>";
        $message['content'] = $mention . ' ' . $message['content'];
    }

    return $message; // Retorna a mensagem modificada para o webhook principal
}
add_filter('fictioneer_filter_discord_comment_message', 'gerenciar_notificacao_comentario_discord', 10, 4);

function tratar_e_truncar_texto($texto, $limite = 250) {
    if (empty($texto)) { return ''; }
    $texto_limpo = html_entity_decode(strip_tags($texto));
    if (mb_strlen($texto_limpo) > $limite) {
        return mb_substr($texto_limpo, 0, $limite) . '...';
    }
    return $texto_limpo;
}


/**
 * Notificação de Lançamento para o Discord (VERSÃO 3 - SIMPLIFICADA)
 * Remove toda a lógica de capítulos premium e tiers.
 */
function custom_discord_chapter_notification($post_id, $story_id) {
    // Busca o webhook principal salvo nas opções do WordPress
    $webhook = get_option('fictioneer_discord_channel_chapters_webhook');
    if (!$webhook) {
        return; // Sai se não houver webhook configurado
    }

    // Se por algum motivo a história não for encontrada, envia um alerta.
    if (!$story_id) {
        $chapter_info = get_the_title($post_id);
        $author_id = get_post_field('post_author', $post_id);
        $author_name = $author_id ? get_the_author_meta('display_name', $author_id) : 'Desconhecido';

        $error_message['content'] = "⚠️ **Erro de Publicação!** ⚠️\n\n"
            . "📌 O **autor/tradutor** **$author_name** cometeu um erro ao postar o capítulo **$chapter_info**.\n\n"
            . "💡 Marque-o e lembre-o gentilmente (*ou não*) de assistir ao **tutorial** antes de fazer besteira de novo!";

        fictioneer_discord_send_message($webhook, $error_message);
        return;
    }

    // Coleta as informações necessárias
    $story_title = get_the_title($story_id);
    $story_link = get_permalink($story_id);
    $chapter_title = get_the_title($post_id);
    $chapter_link = get_permalink($post_id);
    $chapter_excerpt = get_the_excerpt($post_id);

    // Busca o Role ID do campo personalizado da história, com fallback para o cargo "Todas"
    $role_id = get_post_meta($story_id, 'discord_role_id', true);
    $role_id_final = !empty($role_id) ? $role_id : '863456249873825812'; // ID do cargo "Todas"

    // Monta a mensagem de menção
    $message['content'] = "📖 Novo capítulo de *[$story_title]($story_link)* disponível! <@&863456249873825812> <@&{$role_id_final}>";

    // Monta o "embed" (a caixa de notificação rica)
    $author_id = get_post_field('post_author', $story_id);
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_avatar = get_avatar_url($author_id);
    $thumbnail_url = get_the_post_thumbnail_url($story_id, 'thumbnail');

    $embed = array(
        'color'     => 65280, // Verde padrão
        'timestamp' => current_time('c'),
        'footer'    => array(
            'text'     => "$author_name | História: $story_title",
            'icon_url' => $author_avatar
        ),
        'fields'    => array(
            array(
                'name'   => 'NOVO CAPÍTULO!!',
                'value'  => "[$chapter_title]($chapter_link)\n" . tratar_e_truncar_texto($chapter_excerpt, 250),
                'inline' => false
            )
        )
    );

    if ($thumbnail_url) {
        $embed['thumbnail'] = array('url' => $thumbnail_url);
    }

    // Adiciona o embed à mensagem e envia
    $message['embeds'][] = $embed;
    fictioneer_discord_send_message($webhook, $message);
}

// Hook que dispara a notificação quando um capítulo é publicado
add_filter('fictioneer_filter_discord_chapter_message', function($message, $post, $story_id) {
    // Chama nossa nova função simplificada, passando os IDs necessários
    custom_discord_chapter_notification($post->ID, $story_id);
    
    // Retorna null para impedir que a notificação padrão do plugin seja enviada
    return null;
}, 10, 3);

// ---------------------------------------------------------------------------------
// PARTE 3: OUTRAS FUNCIONALIDADES (Restauradas do seu código original)
// ---------------------------------------------------------------------------------

// --- CAMPO PARA DESATIVAR MENÇÕES ---
function my_show_extra_profile_fields($user) {
    ?>
    <h3><?php esc_html_e('Configurações do Discord', 'text-domain'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="disable_discord_mentions"><?php esc_html_e('Desativar menção automática em comentários', 'text-domain'); ?></label></th>
            <td>
                <input type="checkbox" name="disable_discord_mentions" id="disable_discord_mentions" value="1" <?php checked(get_user_meta($user->ID, 'disable_discord_mentions', true), 1); ?> />
                <span class="description"><?php esc_html_e('Marque para não receber menção nas notificações de comentários.', 'text-domain'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'my_show_extra_profile_fields', 10);
add_action('edit_user_profile', 'my_show_extra_profile_fields', 10);



function my_save_extra_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) { return false; }
    update_user_meta($user_id, 'disable_discord_mentions', isset($_POST['disable_discord_mentions']) ? 1 : 0);
}
add_action('personal_options_update', 'my_save_extra_profile_fields');
add_action('edit_user_profile_update', 'my_save_extra_profile_fields');


// --- BOTÃO DE CONVITE DO DISCORD ---
function child_theme_discord_invite_link() {
    ?>
    <a href="https://discord.com/invite/DR8fWtfVd7" target="_blank" rel="noopener" class="button _secondary">
        <i class="fa-brands fa-discord"></i>
    </a>
    <?php
}
add_action('fictioneer_chapter_actions_top_center', 'child_theme_discord_invite_link', 15);
?>