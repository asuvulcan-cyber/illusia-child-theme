<?php
// === Atualiza cargo conforme tier do Patreon ===

/**
 * Atualiza a role do usuário com base nos tiers do Patreon.
 *
 * @param WP_User $user Objeto do usuário.
 * @param array|null $args Dados do Patreon fornecidos pelo action (opcional).
 */
function illusia_update_role_from_patreon_data( $user, $args = null ) {
    if ( ! $user instanceof WP_User ) return;

    $user_id = $user->ID;

    $protected_roles = array(
        'administrator', 'editor', 'author', 'contributor', 'subscriber',
        'fcn_moderator', 'fcn_revisor', 'fcn_autor', 'fcn_tradutor', 'fcn_novato',
        'translator'
    );

    if ( array_intersect( $user->roles, $protected_roles ) ) return;

    $mapping = array(
        '25217687' => 'fcn_illusiano',
        '25217909' => 'fcn_explorador_de_illusia',
        '25217943' => 'fcn_cronista_illusrio',
        '25217944' => 'fcn_guardio_das_crnicas',
        '25282557' => 'fcn_mestre_dos_contos',
        '25282582' => 'fcn_ilustre_patrono_de_illusia'
    );

    // Usa dados do action se disponíveis, caso contrário, obtém via fictioneer_get_user_patreon_data
    if ( is_array( $args ) && isset( $args['patreon_tiers'] ) && $args['channel'] === 'patreon' ) {
        $patreon_data = array(
            'tiers' => ! empty( $args['patreon_tiers'] ) ? array_values( $args['patreon_tiers'] ) : [],
            'valid' => ! empty( $args['patreon_tiers'] )
        );
    } else {
        $patreon_data = fictioneer_get_user_patreon_data( $user_id );
    }

    if ( ! is_array( $patreon_data['tiers'] ) || empty( $patreon_data['tiers'] ) || empty( $patreon_data['valid'] ) ) {
        $user->set_role( 'fcn_illusiano' );
        return;
    }

    $selected_role = 'fcn_illusiano';
    $highest_amount = 0;

    foreach ( $patreon_data['tiers'] as $tier ) {
        if ( ! isset( $tier['id'], $tier['published'], $tier['amount_cents'] ) ) continue;
        if ( ! empty( $tier['published'] ) && isset( $mapping[ $tier['id'] ] ) ) {
            if ( $tier['amount_cents'] > $highest_amount ) {
                $highest_amount = $tier['amount_cents'];
                $selected_role = $mapping[ $tier['id'] ];
            }
        }
    }

    $fictioneer_roles = array_values( $mapping );
    foreach ( $user->roles as $role ) {
        if ( in_array( $role, $fictioneer_roles, true ) ) {
            $user->remove_role( $role );
        }
    }

    $user->set_role( $selected_role );
}

// Conecta ao action fictioneer_after_oauth_user
add_action( 'fictioneer_after_oauth_user', function( $user, $args ) {
    illusia_update_role_from_patreon_data( $user, $args );
}, 10, 2 );

// Fallback para profile_update
add_action( 'profile_update', function( $user_id ) {
    $user = new WP_User( $user_id );
    illusia_update_role_from_patreon_data( $user );
}, 10, 1 );

// === Mural de Apoiadores (sem fantasmas, com mapeamento personalizado) ===

add_shortcode( 'mural_apoiadores', function( $atts ) {
    $args = shortcode_atts( [
        'max' => 100,
        'columns' => 3,
        'title' => ''
    ], $atts );

    $cache_key = 'fictioneer_mural_' . md5( serialize( $args ) );
    $output = get_transient( $cache_key );
    if ( false !== $output ) {
        return $output;
    }

    $mapping = [
        '25217687' => 'fcn_illusiano',
        '25217909' => 'fcn_explorador_de_illusia',
        '25217943' => 'fcn_cronista_illusrio',
        '25217944' => 'fcn_guardio_das_crnicas',
        '25282557' => 'fcn_mestre_dos_contos',
        '25282582' => 'fcn_ilustre_patrono_de_illusia'
    ];

    $tiers = [
        'fcn_ilustre_patrono_de_illusia' => ['name' => 'Ilustre Patrono', 'order' => 5],
        'fcn_mestre_dos_contos' => ['name' => 'Mestre dos Contos', 'order' => 4],
        'fcn_guardio_das_crnicas' => ['name' => 'Guardião das Crônicas', 'order' => 3],
        'fcn_cronista_illusrio' => ['name' => 'Cronista Ilusório', 'order' => 2],
        'fcn_explorador_de_illusia' => ['name' => 'Explorador de Illusia', 'order' => 1],
        'fcn_illusiano' => ['name' => 'Illusiano', 'order' => 0],
    ];

    $all_users = get_users( [
        'meta_key' => 'fictioneer_patreon_tiers',
        'number'   => $args['max']
    ] );

    $tier_groups = [];

    foreach ( $all_users as $user ) {
        $patreon = fictioneer_get_user_patreon_data( $user->ID );
        if ( empty( $patreon['tiers'] ) || empty( $patreon['valid'] ) ) continue;

        $top = null;
        foreach ( $patreon['tiers'] as $tier ) {
            if ( ! empty( $tier['published'] ) && isset( $mapping[ $tier['id'] ] ) ) {
                $mapped = $mapping[ $tier['id'] ];
                if ( ! $top || $tiers[$mapped]['order'] > $top['order'] ) {
                    $top = array_merge( $tiers[$mapped], [
                        'key' => $mapped,
                        'timestamp' => $tier['timestamp'] ?? 0,
                        'amount_cents' => $tier['amount_cents'] ?? 0
                    ] );
                }
            }
        }

        if ( $top ) {
            $tier_groups[$top['key']][] = [
                'user' => $user,
                'tier_data' => $top
            ];
        }
    }

    uasort( $tiers, fn( $a, $b ) => $b['order'] <=> $a['order'] );

    ob_start(); ?>
    <div class="fictioneer-mural" style="--cols: <?php echo esc_attr( $args['columns'] ); ?>">
        <?php if ( ! empty( $args['title'] ) ) : ?>
            <h2 class="fictioneer-mural-title"><?php echo esc_html( $args['title'] ); ?></h2>
            <p class="fictioneer-mural-notice"><?php _e( 'A autenticação do Patreon dura uma semana. Após esse período, seu nome pode ser removido do mural. Faça login novamente com o Patreon para atualizar suas informações.', 'illusia' ); ?></p>
        <?php endif; ?>

        <?php foreach ( $tiers as $key => $tier ) : ?>
            <?php if ( empty( $tier_groups[$key] ) ) continue; ?>
            <div class="fictioneer-tier-group">
                <h3 class="fictioneer-tier-title <?php echo esc_attr( $key ); ?>">
                    <?php echo esc_html( $tier['name'] ); ?>
                    <span class="fictioneer-tier-count"><?php echo count( $tier_groups[$key] ); ?> <?php _e( 'membros', 'illusia' ); ?></span>
                </h3>
                <div class="fictioneer-tier-cards">
                    <?php foreach ( $tier_groups[$key] as $member ) :
                        $u = $member['user'];
                        $avatar = get_avatar( $u->ID, 96 );
                        $profile = get_author_posts_url( $u->ID );
                        $since = $member['tier_data']['timestamp'] ? wp_date( 'M/Y', $member['tier_data']['timestamp'] ) : __( 'Indefinido', 'illusia' );
                        $cents = $member['tier_data']['amount_cents'] ?? 0;
                        $dollars = $cents / 100;

                        $custom_conversion = [
                            1 => 'R$ 7,50',
                            2 => 'R$ 15,00',
                            3 => 'R$ 22,50',
                            4 => 'R$ 30,00',
                            5 => 'R$ 35,00'
                        ];

                        if ( isset( $custom_conversion[$dollars] ) ) {
                            $pledge = $custom_conversion[$dollars];
                        } else {
                            $pledge = 'R$ ' . number_format( $dollars * 7.50, 2, ',', '.' );
                        }

                        if ( $cents == 0 ) {
                            $pledge = __( 'Gratuito', 'illusia' );
                        }
                        ?>
                        <div class="fictioneer-card <?php echo esc_attr( $key ); ?>">
                            <div class="fictioneer-card__ribbon"><?php _e( 'Tier', 'illusia' ); ?> <?php echo esc_html( $tier['order'] ); ?></div>
                            <a href="<?php echo esc_url( $profile ); ?>" class="fictioneer-card__link">
                                <div class="fictioneer-card__glow"></div>
                                <div class="fictioneer-card__avatar"><?php echo $avatar; ?></div>
                                <div class="fictioneer-card__content">
                                    <span class="fictioneer-card__name"><?php echo esc_html( $u->display_name ); ?></span>
                                    <div class="fictioneer-card__meta">
                                        <span class="fictioneer-comment__badge <?php echo esc_attr( $key ); ?>"><?php echo esc_html( $tier['name'] ); ?></span>
                                        <span class="fictioneer-card__since"><?php _e( 'Desde', 'illusia' ); ?> <?php echo esc_html( $since ); ?></span>
                                    </div>
                                    <div class="fictioneer-card__pledge">
                                        <?php 
                                        echo esc_html( $pledge );
                                        echo ( $pledge !== __( 'Gratuito', 'illusia' ) ) ? '/' . __( 'mês', 'illusia' ) : '';
                                        ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php 
    $output = ob_get_clean();
    set_transient( $cache_key, $output, HOUR_IN_SECONDS );
    return $output;
} );

// === Popup Patreon ===

add_action( 'wp_footer', function() {
    // Inicializa variável para verificar se o popup deve ser exibido
    $show_popup = true;

    // Verifica se o usuário está logado e tem roles que impedem o popup
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $paid_roles = [ 'fcn_explorador_de_illusia', 'fcn_cronista_illusrio', 'fcn_guardio_das_crnicas', 'fcn_mestre_dos_contos', 'fcn_ilustre_patrono_de_illusia' ];
        $protected_roles = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'fcn_moderator', 'fcn_autor', 'fcn_revisor', 'fcn_tradutor', 'translator' ];

        if ( array_intersect( $user->roles, array_merge( $paid_roles, $protected_roles ) ) ) {
            $show_popup = false;
        }
    }

    // Renderiza o popup se permitido
    if ( ! $show_popup ) return;
    ?>
    <div id="patreon-popup" class="popup-overlay" role="dialog" aria-labelledby="popup-title" tabindex="-1">
        <div class="popup-content">
		<h3 id="popup-title"><?php _e( 'CONSEGUIMOS REMOVER OS ANÚNCIOS!', 'illusia' ); ?></h3>
		<p><?php _e( 'Graças ao apoio da comunidade, batemos nossa meta inicial e agora todos os usuários logados podem desfrutar do site sem anúncios 🎉', 'illusia' ); ?></p>
		<p><?php _e( 'Queremos manter essa experiência livre de distrações, e para isso, precisamos da sua ajuda contínua. Contribua com qualquer valor e nos ajude a manter Illusia assim: leve, imersivo e focado na leitura.', 'illusia' ); ?></p>
		<p><?php _e( 'Saiba mais sobre como apoiar acessando nossa ', 'illusia' ); ?><a href="https://illusia.com.br/apoie-nos-no-patreon/" target="_blank"><?php _e( 'página de orientação', 'illusia' ); ?></a>.</p>
		<p><?php _e( 'Precisa de ajuda ou quer se conectar com a comunidade? Junte-se ao nosso ', 'illusia' ); ?><a href="https://discord.gg/dEzaDfEwWu" target="_blank"><?php _e( 'Discord', 'illusia' ); ?></a>!</p>
		<button id="close-popup"><?php _e( 'Fechar', 'illusia' ); ?></button>
		<a href="https://www.patreon.com/c/illusia_" target="_blank"><button><?php _e( 'Continuar Contribuindo', 'illusia' ); ?></button></a>
        </div>
    </div>


    <!-- CSS do Popup -->
    <style type="text/css">
        .popup-overlay {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000000b5;
            z-index: 9999;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.8s ease;
            backdrop-filter: blur(8px);
        }
        .popup-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .popup-content {
            background: var(--bg-600);
            padding: 20px;
            border-radius: 5px;
            max-width: 600px;
            text-align: center;
            box-shadow: 10px 0 10px rgba(0, 0, 0, 0.3);
        }
        .popup-content h3 {
            margin-top: 0;
            color: var(--fg-100);
            font-size: x-large;
        }
        .popup-content p {
            margin: 10px 0;
            color: var(--fg-500);
        }
        .popup-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            cursor: pointer;
        }
        #close-popup {
            background: var(--bg-700);
            border: 1px solid var(--bg-500);
            border-radius: 5px;
        }
        .popup-content a button {
            background: var(--primary-400);
            color: var(--fg-inverted);
            border: 1px solid var(--primary-600);
            border-radius: 5px;
        }
        .popup-content a button:hover {
            background: var(--primary-500);
        }
    </style>

    <!-- JavaScript do Popup -->
<script type="text/javascript">
    // Funções para gerenciamento de cookies (sem alteração)
    function getCookie(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    function setCookie(name, value, days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toUTCString();
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // --- LÓGICA MELHORADA ---

    // Função para fechar o popup e definir os cookies
    function closePatreonPopup() {
        var popup = document.getElementById('patreon-popup');
        popup.classList.remove('show');
        // Define um cookie de longa duração (ex: 30 dias)
        setCookie('illusiaPatreonPopup', 'closed', 30); 
    }

    window.onload = function() {
        // Verifica se o popup já foi fechado anteriormente (cookie de 30 dias)
        // E também verifica se o popup já foi exibido NESTA SESSÃO
        if (!getCookie('illusiaPatreonPopup') && !sessionStorage.getItem('patreonPopupShown')) {
            setTimeout(function() {
                var popup = document.getElementById('patreon-popup');
                popup.classList.add('show');
                popup.focus();
                // Marca que o popup foi exibido nesta sessão para não mostrar de novo
                sessionStorage.setItem('patreonPopupShown', 'true');
            }, 1000);
        }
    };

    // Adiciona os eventos para fechar o popup
    document.getElementById('close-popup').addEventListener('click', closePatreonPopup);
    
    document.getElementById('patreon-popup').addEventListener('click', function(e) {
        if (e.target === this) {
            closePatreonPopup();
        }
    });

    document.getElementById('patreon-popup').addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePatreonPopup();
        }
    });
</script>
<?php } );
?>