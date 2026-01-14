<?php

// Função para adicionar metaboxes de suporte
function adicionar_metaboxes_suporte( $output, $post ) {
    // Discord link
    $output['fictioneer_discord_link'] = fictioneer_get_metabox_url(
        $post,
        'fictioneer_discord_link',
        array(
            'label' => _x('Discord Link', 'Discord link meta field label.', 'fictioneer'),
            'placeholder' => 'https://discord.gg/...'
        )
    );

    // Apoia.se link
    $output['fictioneer_apoia_link'] = fictioneer_get_metabox_url(
        $post,
        'fictioneer_apoia_link',
        array(
            'label' => _x('Apoia.se Link', 'Apoia.se link meta field label.', 'fictioneer'),
            'placeholder' => 'https://apoia.se/...'
        )
    );

    return $output;
}
add_filter( 'fictioneer_filter_metabox_support_links', 'adicionar_metaboxes_suporte', 10, 2 );

// Função para salvar metaboxes de suporte
function salvar_metaboxes_suporte( $fields, $post_id ) {
    // Discord link
    if ( isset( $_POST['fictioneer_discord_link'] ) ) {
        $discord = fictioneer_sanitize_url( $_POST['fictioneer_discord_link'], null, '#^https://(www\.)?discord\.gg/#' );
        $fields['fictioneer_discord_link'] = $discord;
    }

    // Apoia.se link
    if ( isset( $_POST['fictioneer_apoia_link'] ) ) {
        $apoia = fictioneer_sanitize_url( $_POST['fictioneer_apoia_link'], null, '#^https://(www\.)?apoia\.se/#' );
        $fields['fictioneer_apoia_link'] = $apoia;
    }

    return $fields;
}
add_filter( 'fictioneer_filter_metabox_updates_support_links', 'salvar_metaboxes_suporte', 10, 2 );

// Função para adicionar links de suporte ao filtro de capítulos
add_filter( 'fictioneer_filter_chapter_support_links', 'adicionar_links_suporte_capitulo', 10, 2 );

function adicionar_links_suporte_capitulo( $support_links, $args ) {
    // Obtém os links do post atual
    $discord_link = get_post_meta( $args['story_post']->ID, 'fictioneer_discord_link', true );
    $apoia_link = get_post_meta( $args['story_post']->ID, 'fictioneer_apoia_link', true );

    // Adiciona o link do Discord se existir
    if ( !empty( $discord_link ) ) {
        $support_links['discord'] = array(
            'label' => _x('Discord', 'Discord support link label', 'fictioneer'),
            'icon' => '<i class="fab fa-discord"></i>', // Certifique-se de ter o FontAwesome ou outro ícone disponível
            'link' => esc_url( $discord_link )
        );
    }

    // Adiciona o link do Apoia.se se existir
    if ( !empty( $apoia_link ) ) {
        $support_links['apoia'] = array(
            'label' => _x('Apoia.se', 'Apoia.se support link label', 'fictioneer'),
            'icon' => '<i class="fas fa-handshake"></i>', // Certifique-se de ter o FontAwesome ou outro ícone disponível
            'link' => esc_url( $apoia_link )
        );
    }

    return $support_links;
}

// Função para adicionar links de suporte ao filtro de post
add_filter( 'fictioneer_filter_get_support_links', 'adicionar_links_suporte', 10, 4 );

function adicionar_links_suporte( $links, $post_id, $parent_id, $author_id ) {
    // Obtém os links do post atual
    $discord_link = get_post_meta( $post_id, 'fictioneer_discord_link', true );
    $apoia_link = get_post_meta( $post_id, 'fictioneer_apoia_link', true );

    // Adiciona o link do Discord se existir
    if ( !empty( $discord_link ) ) {
        $links['discord'] = esc_url( $discord_link );
    }

    // Adiciona o link do Apoia.se se existir
    if ( !empty( $apoia_link ) ) {
        $links['apoia'] = esc_url( $apoia_link );
    }

    return $links;
}

