<?php

// =====       TELAS.CSS       =====

// Função para gerar o HTML comum para telas
function gerarTelaRPG($classe = 'litrpg-box') {
    return '<div class="' . esc_attr($classe) . '"><div class="litrpg-frame"><div class="litrpg-body">';
}

// Função para gerar o HTML comum para telas
function gerarTela($classe = '') {
    return '<div class="' . esc_attr($classe) . '">';
}

// Função para gerar o HTML comum para telas com uma div dentro da outra
function gerarTelaDiv($classeExterna = '', $classeInterna = '') {
    $classeExterna = esc_attr($classeExterna);
    $classeInterna = esc_attr($classeInterna);
    
    return '<div class="' . $classeExterna . '"><div id="' . $classeInterna . '"></div>';
}

// Função específica para HUDs com estrutura dupla de div
function gerarTelaHUD($classeExterna = '', $classeInterna = '') {
    $classeExterna = esc_attr($classeExterna);
    $classeInterna = esc_attr($classeInterna);

    return '<div class="' . $classeExterna . '"><div class="' . $classeInterna . '">';
}

// Função para processar o conteúdo do HUD
function processarHUD($conteudo) {
    // Encontrar todas as ocorrências de #hud#...#hud-fim#
    $pattern = '/#hud#(.*?)#hud-fim#/';
    return preg_replace_callback($pattern, function($matches) {
        // Dividir o conteúdo em label e título usando o delimitador |
        $partes = explode('|', $matches[1], 2);
        if (count($partes) !== 2) {
            // Caso o delimitador não seja encontrado, usar um valor padrão para o label
            $label = 'Informação';
            $titulo = trim($matches[1]);
        } else {
            $label = trim($partes[0]);
            $titulo = trim($partes[1]);
        }

        // Gerar o HTML do HUD com o label e título dinâmicos
        return gerarTelaHUD('hud-caixa hud-text', 'hud-conteudo') .
               '<div class="hud-label">' . htmlspecialchars($label) . '</div>' .
               '<div class="hud-divisor"></div>' .
               '<div class="hud-titulo">' . htmlspecialchars($titulo) . '</div>' .
               fecharTela(2);
    }, $conteudo);
}

// Função para gerar 1 fechamento da tela
function fecharTela($quantidade = 1) {
    return str_repeat('</div>', (int) $quantidade);
}

// Função para gerar o HTML com uma hr dentro de outra
function divisor($classeExterna = '', $classeInterna = '') {
    $classeExterna = esc_attr($classeExterna);
    $classeInterna = esc_attr($classeInterna);
    
    return '<hr class="' . $classeExterna . '" id="' . $classeInterna . '">';
}

function remove_themoneytizer_shortcode_simple($content) {
    $content = str_replace('[themoneytizer id=”35847-1″]', '', $content);
    $content = str_replace('[themoneytizer id="35847-1"]', '', $content);
    return $content;
}

// Função para gerar o Divisor de Pontos
function divisorPontos() {
    return '<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots">';
}

// Função para gerar o Divisor de Pontos
function divisorMeiaLinha() {
    return '<hr class="wp-block-separator has-alpha-channel-opacity">';
}

function limpar_conteudo_personalizado($content) {
    $patterns = [
        '/\[themoneytizer id=.*\]/U',
        '/\<p style\=\"text.*.apítulo .nterior.*.róximo .apít.*<\/p\>/U',
        '/\<p class\=\"has-text.*.apítulo .nterior.*.róximo .apít.*<\/p\>/U',
        '/\<.*AVALIE.*CENTRAL NOVEL.*\>/U',
        '/\<div.*Avalie.*Central Novel.*div\>/U',
        '/<a class\="broken_link".*\/a>/U',
        '/  \|\|.*\|\|  /U',
        '/<div id\=\"quads-ad3\".*\/div>/U',
        '/\<\!\-\-.*\-\-\>/',
        '/⇐ Capítulo Anterior \| Índice \| Próximo Capítulo ⇒/U'
    ];

    return preg_replace($patterns, '', $content);
}

add_filter('the_content', 'limpar_conteudo_personalizado');
add_filter('widget_text', 'limpar_conteudo_personalizado');
add_filter('acf/load_value', 'limpar_conteudo_personalizado', 10, 3);
add_filter('get_post_metadata', function($value, $post_id, $meta_key, $single) {
    if (is_string($value)) {
        return limpar_conteudo_personalizado($value);
    }
    return $value;
}, 10, 4);


// Função para substituir as tags personalizadas por HTML
function replace_custom_tags($content) {
    // Primeiro, processa o conteúdo do HUD
    $content = processarHUD($content);

    // Define as tags e seus HTML correspondentes
    $replacements = array(
        // Telas
        '#tela-neutra#' => gerarTela('tela-base tela-neutra').gerarTela('neutra-text'),
        '#tela-arkus#' => gerarTelaDiv('tela-arkus', 'hr-arkus').gerarTela('arkus-text'),
        '#tela-arkus2#' => gerarTelaDiv('tela-arkus2', 'hr-arkus2').gerarTela('arkus2-text'),
        '#tela-neko#' => gerarTelaDiv('tela-neko', 'hr-neko').gerarTela('neko-text'),
        '#tela-neko2#' => gerarTelaDiv('tela-neko2', 'hr-neko2').gerarTela('neko2-text'),
        '#tela-brosa#' => gerarTela('tela-base tela-brosa').gerarTela('brosa-text'),
        '#tela-escura#' => gerarTela('tela-base tela-escura').gerarTela('escura-text'),
        '#tela-escura2#' => gerarTela('tela-base tela-escura2').gerarTela('escura2-text'),
        '#tela-escura3#' => gerarTela('tela-base tela-escura3').gerarTela('escura3-text'),
        '#tela-escura4#' => gerarTela('tela-base tela-escura4').gerarTela('escura4-text'),
        '#tela-escura5#' => gerarTela('tela-base tela-escura5').gerarTela('escura5-text'),
        '#tela-prata#' => gerarTela('tela-base tela-prata').gerarTela('prata-text'),
        '#tela-preta#' => gerarTela('tela-base tela-preta').gerarTela('preta-text'),
        '#tela-roxa#' => gerarTela('tela-base tela-roxa').gerarTela('roxa-text'),
        '#tela-fundo#' => gerarTela('fundo-base tela-fundo').gerarTela('fundo-text'),
        '#tela-fundo2#' => gerarTela('fundo-base tela-fundo2').gerarTela('fundo-text'),
        '#tela-fundo3#' => gerarTela('fundo-base tela-fundo3').gerarTela('fundo-text'),
        '#tela-fundo4#' => gerarTela('fundo-base tela-fundo4').gerarTela('fundo-text'),
        '#tela-fundo5#' => gerarTela('fundo-base tela-fundo5').gerarTela('fundo-text'),
        '#tela-fundo6#' => gerarTela('fundo-base tela-fundo6').gerarTela('fundo-text'),
        '#tela-fundo7#' => gerarTela('fundo-base tela-fundo7').gerarTela('fundo-text'),
        '#tela-fundo8#' => gerarTela('fundo-base tela-fundo8').gerarTela('fundo-text'),
        '#tela-fundo9#' => gerarTela('fundo-base tela-fundo9').gerarTela('fundo-text'),
        '#tela-fundo10#' => gerarTela('fundo-base tela-fundo10').gerarTela('fundo-text'),
        '#tela-fundo11#' => gerarTela('fundo-base tela-fundo11').gerarTela('fundo-text'),
        '#tela-fundo12#' => gerarTela('fundo-base tela-fundo12').gerarTela('fundo-text'),
        '#tela-fundo13#' => gerarTela('fundo-base tela-fundo13').gerarTela('fundo-text'),
        '#tela-sangue#' => gerarTela('fundo-base tela-sangue').gerarTela('sangue-text'),

        // Sistemas
        '#sistema-avalon#' => gerarTelaDiv('sistema-base sistema-avalon', 'hr-avalon').gerarTela('texto-sistema'),
        '#sistema-sangrento#' => gerarTelaDiv('sistema-base sistema-sangrento', 'hr-sangrento').gerarTela('texto-sistema'),
        '#sistema-azulado#' => gerarTelaDiv('sistema-base sistema-azulado', 'hr-azulado').gerarTela('texto-sistema'),
        '#sistema-violeta#' => gerarTelaDiv('sistema-base sistema-violeta', 'hr-violeta').gerarTela('texto-sistema'),
        '#sistema-marrom#' => gerarTelaDiv('sistema-base sistema-marrom', 'hr-marrom').gerarTela('texto-sistema'),
        '#sistema-laranja#' => gerarTelaDiv('sistema-base sistema-laranja', 'hr-laranja').gerarTela('texto-sistema'),
        '#sistema-violet#' => gerarTelaDiv('sistema-base sistema-violeta2', 'hr-violeta2').gerarTela('texto-sistema'),
        '#sistema-indigo#' => gerarTelaDiv('sistema-base sistema-indigo', 'hr-indigo').gerarTela('texto-sistema'),
        '#sistema-orange#' => gerarTelaDiv('sistema-base sistema-laranja2', 'hr-laranja2').gerarTela('texto-sistema'),
        '#sistema-yellow#' => gerarTelaDiv('sistema-base sistema-amarelo', 'hr-amarelo').gerarTela('texto-sistema'),
        '#sistema-escuro#' => gerarTelaDiv('sistema-base sistema-escuro', 'hr-escuro').gerarTela('texto-sistema'),
        '#sistema-cell#' => gerarTelaDiv('sistema-base sistema-cell', 'hr-escuro').gerarTela('texto-sistema'),
        '#sistema-red#' => gerarTelaDiv('sistema-base sistema-vermelho', 'hr-vermelho').gerarTela('texto-sistema'),
        '#sistema-verde#' => gerarTelaDiv('sistema-base sistema-verde', 'hr-verde').gerarTela('texto-sistema'),
        '#sistema-cor#' => gerarTelaDiv('sistema-base sistema-cor', 'hr-cor').gerarTela('texto-sistema'),
        '#sistema#' => gerarTelaDiv('sistema-base sistema', 'hr-sistema').gerarTela('texto-sistema'),
        '#sistema-roxo#' => gerarTelaDiv('sistema-base sistema-roxo', 'hr-roxo').gerarTela('texto-sistema'),
        '#sistema-draco#' => gerarTelaDiv('draco-sen-base sistema-draco', 'hr-draco').gerarTela('texto-sistema'),
        '#sistema-sen#' => gerarTelaDiv('draco-sen-base sistema-sen', 'hr-sen').gerarTela('texto-sistema'),
        '#sistema-azul#' => gerarTelaDiv('chifre-base sistema-azul', 'hr-azul').gerarTela('azul-text'),
        '#sistema-vermelho3#' => gerarTelaDiv('chifre-base sistema-vermelho3', 'hr-vermelho3').gerarTela('vermelho-text'),
        '#sistema-over#' => gerarTelaDiv('chifre-base sistema-over', 'hr-over').gerarTela('over-text'),
        '#sistema-over2#' => gerarTelaDiv('chifre-base sistema-over2', 'hr-over2').gerarTela('vermelho-text'),
        '#sistema-ouro#' => gerarTelaDiv('chifre-base sistema-ouro', 'hr-ouro').gerarTela('ouro-text'),
		'#sistema-ouro2#' => gerarTelaDiv('chifre-base sistema-ouro').gerarTela('ouro-text'),
        '#sistema-vermelho#' => gerarTelaDiv('chifre-base sistema-vermelho2', 'hr-vermelho2').gerarTela('vermelho-text'),
        '#sistema-avermelhado#' => gerarTelaDiv('chifre-base sistema-avermelhado', 'hr-avermelhado').gerarTela('avermelhado-text'),
        '#sistema-esverdeado#' => gerarTelaDiv('chifre-base sistema-esverdeado', 'hr-esverdeado').gerarTela('esverdeado-text'),

        // HUDs
        '#hud#' => '', // Substituição será feita pelo processarHUD
        '#hud-fim#' => '',

        // Displays
        '#display-ia#' => gerarTela('fundo-base display-10').gerarTela('display-text'),
        '#display#' => gerarTela('fundo-base display-4').gerarTela('display-text'),
        '#display-2#' => gerarTela('fundo-base display-5').gerarTela('display-text'),
        '#display-3#' => gerarTela('fundo-base display-6').gerarTela('display-text'),
        '#display-4#' => gerarTela('fundo-base display-7').gerarTela('display-text'),
        '#display-5#' => gerarTela('fundo-base display-8').gerarTela('display-text'),
        '#display-6#' => gerarTela('fundo-base display-9').gerarTela('display-text'),
        '#display-7#' => gerarTela('fundo-base display-11').gerarTela('display-text'),        
        '#display-rpg#' => gerarTelaRPG(),
        '#display-red#' => gerarTela('fundo-base display-red').gerarTela('display-text-red'),

        // 8-bits
        '#8bAzul#' => gerarTela('_8b _8bAzul').gerarTela('_8b-text'),
        '#8bVerde#' => gerarTelaDiv('_8b _8bVerde').gerarTela('_8b-text'),
        '#8bVermelho#' => gerarTelaDiv('_8b _8bVermelha').gerarTela('_8b-text'),
        '#8bAmarelo#' => gerarTelaDiv('_8b _8bAmarelo').gerarTela('_8b-text'),
        '#8bLaranja#' => gerarTelaDiv('_8b _8bLaranja').gerarTela('_8b-text'),
        '#8bRoxo#' => gerarTelaDiv('_8b _8bRoxo').gerarTela('_8b-text'),
        '#8bCiano#' => gerarTelaDiv('_8b _8bCiano').gerarTela('_8b-text'),
        '#8bMagenta#' => gerarTelaDiv('_8b _8bMagenta').gerarTela('_8b-text'),
        '#8bCinza#' => gerarTelaDiv('_8b _8bCinza').gerarTela('_8b-text'),
        '#8bMarrom#' => gerarTelaDiv('_8b _8bMarrom').gerarTela('_8b-text'),
        '#8bAzulClaro#' => gerarTelaDiv('_8b _8bAzulClaro').gerarTela('_8b-text'),
        '#8bVerdeClaro#' => gerarTelaDiv('_8b _8bVerdeClaro').gerarTela('_8b-text'),
        '#8bVermelhoClaro#' => gerarTelaDiv('_8b _8bVermelhoClaro').gerarTela('_8b-text'),
        '#8bAmareloClaro#' => gerarTelaDiv('_8b _8bAmareloClaro').gerarTela('_8b-text'),
        '#8bLaranjaClaro#' => gerarTelaDiv('_8b _8bLaranjaClaro').gerarTela('_8b-text'),
        '#8bRoxoClaro#' => gerarTelaDiv('_8b _8bRoxoClaro').gerarTela('_8b-text'),
        '#8bCianoClaro#' => gerarTelaDiv('_8b _8bCianoClaro').gerarTela('_8b-text'),
        '#8bMagentaClaro#' => gerarTelaDiv('_8b _8bMagentaClaro').gerarTela('_8b-text'),
        '#8bCinzaClaro#' => gerarTelaDiv('_8b _8bCinzaClaro').gerarTela('_8b-text'),
        '#8bMarromClaro#' => gerarTelaDiv('_8b _8bMarromClaro').gerarTela('_8b-text'),

        // Balões
        '#bs-dir#' => gerarTela('_balao-simples _b-dir').gerarTela('_b-text'),
        '#bs-esq#' => gerarTela('_balao-simples _b-esq').gerarTela('_b-text'),
        '#bsg-dir#' => gerarTela('_balao-gradient _bg-dir'),
        '#bsg-esq#' => gerarTela('_balao-gradient _bg-esq'),

        // Hexágonos
        '#hex-azul#' => gerarTela('_hex _hex-azul _hex-filter').gerarTela('_hex-text'),

		// Filtros
		'#blur-1' => gerarTela('_blur-1'),
		'#blur-2' => gerarTela('_blur-2'),
		'#blur-3' => gerarTela('_blur-3'),
		'#blur-4' => gerarTela('_blur-4'),
		'#blur-5' => gerarTela('_blur-5'),
		'#blur-6' => gerarTela('_blur-6'),
		'#blur-7' => gerarTela('_blur-7'),
		'#blur-8' => gerarTela('_blur-8'),
		'#blur-9' => gerarTela('_blur-9'),
		'#blur-x' => gerarTela('_blur-10'),

		'#brit-1' => gerarTela('_brit-1'),
		'#brit-2' => gerarTela('_brit-2'),
		'#brit-3' => gerarTela('_brit-3'),
		'#brit-4' => gerarTela('_brit-4'),
		'#brit-5' => gerarTela('_brit-5'),
		'#brit-6' => gerarTela('_brit-6'),
		'#brit-7' => gerarTela('_brit-7'),
		'#brit-8' => gerarTela('_brit-8'),

		'#cont-1' => gerarTela('_cont-1'),
		'#cont-2' => gerarTela('_cont-2'),
		'#cont-3' => gerarTela('_cont-3'),
		'#cont-4' => gerarTela('_cont-4'),
		'#cont-5' => gerarTela('_cont-5'),
		'#cont-6' => gerarTela('_cont-6'),
		'#cont-7' => gerarTela('_cont-7'),
		'#cont-8' => gerarTela('_cont-8'),

		'#sat-1' => gerarTela('_sat-1'),
		'#sat-2' => gerarTela('_sat-2'),
		'#sat-3' => gerarTela('_sat-3'),
		'#sat-4' => gerarTela('_sat-4'),
		'#sat-5' => gerarTela('_sat-5'),
		'#sat-6' => gerarTela('_sat-6'),
		'#sat-7' => gerarTela('_sat-7'),
		'#sat-8' => gerarTela('_sat-8'),

		'#hue-1' => gerarTela('_hue-1'),
		'#hue-2' => gerarTela('_hue-2'),
		'#hue-3' => gerarTela('_hue-3'),
		'#hue-4' => gerarTela('_hue-4'),
		'#hue-5' => gerarTela('_hue-5'),
		'#hue-6' => gerarTela('_hue-6'),
		'#hue-7' => gerarTela('_hue-7'),
		'#hue-8' => gerarTela('_hue-8'),
		'#hue-9' => gerarTela('_hue-9'),
		'#hue-x' => gerarTela('_hue-10'),
		'#hue-y' => gerarTela('_hue-11'),
		'#hue-z' => gerarTela('_hue-12'),

		'#op-x' => gerarTela('_op-10'),
		'#op-25' => gerarTela('_op-25'),
		'#op-50' => gerarTela('_op-50'),
		'#op-75' => gerarTela('_op-75'),
		'#op-90' => gerarTela('_op-90'),
		'#op-100' => gerarTela('_op-100'),

		'#cinzento' => gerarTela('_gray'),
		'#invertido' => gerarTela('_invert'),
		'#sepia-1' => gerarTela('_sepia-1'),
		'#sepia-2' => gerarTela('_sepia-2'),

		'#darken' => gerarTela('_darken'),
		'#lighten' => gerarTela('_lighten'),
		'#vintage' => gerarTela('_vintage'),
		'#washed' => gerarTela('_washed'),
		'#fantasma' => gerarTela('_ghost'),
		'#brilho-max' => gerarTela('_glow'),
		'#dourado' => gerarTela('_golden'),
		'#noir' => gerarTela('_noir'),
		'#visao-noturna' => gerarTela('_nightvision'),
		'#vazio' => gerarTela('_void'),

		'#alerta' => gerarTela('_alerta'),
		'#frio' => gerarTela('_frio'),
		'#quente' => gerarTela('_quente'),
		'#neutro' => gerarTela('_neutro'),
		'#distorcido' => gerarTela('_distorcido'),
		'#batida' => gerarTela('_batida'),
		'#sonho' => gerarTela('_sonho'),
		'#caos' => gerarTela('_caos'),
		'#pesadelo' => gerarTela('_pesadelo'),
		'#prisma' => gerarTela('_prisma'),
		'#solarizado' => gerarTela('_solarizado'),
		'#oculto' => gerarTela('_oculto'),
		'#corrompido' => gerarTela('_corrompido'),
		'#radiante' => gerarTela('_radiante'),
		'#cinza-profundo' => gerarTela('_cinza-profundo'),
		'#vivido' => gerarTela('_vivido'),
		'#antigo' => gerarTela('_antigo'),
		'#liquido' => gerarTela('_liquido'),
		'#plasma' => gerarTela('_plasma'),
		'#etereo' => gerarTela('_etereo'),

		
				// Fechamentos por tipo de filtro
		'blur#'        => fecharTela(),        
		'sepia#'       => fecharTela(),    
		'hue#'         => fecharTela(),
		'cinzento#'    => fecharTela(),
		'invertido#'   => fecharTela(),
		'sat#'         => fecharTela(),
		'brit#'        => fecharTela(),
		'cont#'        => fecharTela(),
		'op#'          => fecharTela(),
		'darken#'      => fecharTela(),
		'lighten#'     => fecharTela(),
		'vintage#'     => fecharTela(),
		'washed#'      => fecharTela(),
		'fantasma#'    => fecharTela(),
		'brilho-max#'  => fecharTela(),
		'dourado#'     => fecharTela(),
		'noir#'        => fecharTela(),
		'visao-noturna#' => fecharTela(),
		'vazio#'       => fecharTela(),

		'alerta#'        => fecharTela(),
		'frio#'          => fecharTela(),
		'quente#'        => fecharTela(),
		'neutro#'        => fecharTela(),
		'distorcido#'    => fecharTela(),
		'batida#'        => fecharTela(),
		'sonho#'         => fecharTela(),
		'caos#'          => fecharTela(),
		'pesadelo#'      => fecharTela(),
		'prisma#'        => fecharTela(),
		'solarizado#'    => fecharTela(),
		'oculto#'        => fecharTela(),
		'corrompido#'    => fecharTela(),
		'radiante#'      => fecharTela(),
		'cinza-profundo#' => fecharTela(),
		'vivido#'        => fecharTela(),
		'antigo#'        => fecharTela(),
		'liquido#'       => fecharTela(),
		'plasma#'        => fecharTela(),
		'etereo#'        => fecharTela(),

        // Outros
        '#folha#' => gerarTela('folha').gerarTela('folha-text'),
        '#quadro#' => gerarTela('quadro'),
        '#carta#' => gerarTela('letter'),
        '#pergaminho#' => gerarTela('tela-base tela-preta').gerarTela('preta-text'),
        '#sp' => '<span class="spoiler">',
        'sp#' => '</span>',
        '#sc' => '<span class="sensitive-content">',
        'sc#' => '</span>',

        // Fechamento de Telas
        '#tela-fim#' => fecharTela(2),
        '#display-rpg-fim#' => fecharTela(3),
        '#folha-fim#' => fecharTela(2),
        '#quadro-fim#' => fecharTela(),
        '#sistema-fim#' => fecharTela(2),
        '#display-fim#' => fecharTela(2),
        '#cell-fim#' => fecharTela(2),
        '#escuro-fim#' => fecharTela(2),
        '#draco-fim#' => fecharTela(2),
        '#fim-sen#' => fecharTela(2),
        '#azul-fim#' => fecharTela(2),
        '#over-fim#' => fecharTela(2),
        '#ouro-fim#' => fecharTela(2),
        '#vermelho-fim#' => fecharTela(2),
        '#avermelhado-fim#' => fecharTela(2),
        '#esverdeado-fim#' => fecharTela(2),
        '#8b-fim#' => fecharTela(2),
        '#centro' => gerarTela('centro'),
        'centro#' => fecharTela(),
		
        '#autor' => '<hr><p style="text-align: center;">『',
        'autor#' => '』</p><hr>',
        '#bs-fim#' => fecharTela(2),
        '#bsg-fim#' => fecharTela(),
        '#carta-fim#' => fecharTela(),
        '#pergaminho-fim#' => fecharTela(2),

        // Divisores
        '#00#' => divisor('_divisor v2', '_00'),
        '#01#' => divisor('_divisor v1', '_01'),
        '#02#' => divisor('_divisor v1', '_02'),
        '#03#' => divisor('_divisor v1', '_03'),
        '#04#' => divisor('_divisor v1', '_04'),
        '#05#' => divisor('_divisor v1', '_05'),
        '#06#' => divisor('_divisor v1', '_06'),
        '#07#' => divisor('_divisor v1', '_07'),
        '#08#' => divisor('_divisor v1', '_08'),
        '#09#' => divisor('_divisor v1', '_09'),
        '#10#' => divisor('_divisor v2', '_10'),
        '#11#' => divisor('_divisor v1', '_11'),
        '#12#' => divisor('_divisor v1', '_12'),
        '#13#' => divisor('_divisor v1', '_13'),
        '#14#' => divisor('_divisor v1', '_14'),
        '#15#' => divisor('_divisor v1', '_15'),
        '#16#' => divisor('_divisor v1', '_16'),
        '#17#' => divisor('_divisor v1', '_17'),
        '#18#' => divisor('_divisor v1', '_18'),
        '#19#' => divisor('_divisor v1', '_19'),
        '#20#' => divisor('_divisor v1', '_20'),
        '#21#' => divisor('_divisor v1', '_21'),
        '#22#' => divisor('_divisor v1', '_22'),
        '#23#' => divisor('_divisor v1', '_23'),
        '#24#' => divisor('_divisor v1', '_24'),
        '#25#' => divisor('_divisor v1', '_25'),
        '#26#' => divisor('_divisor v1', '_26'),
        '#27#' => divisor('_divisor v1', '_27'),
        '#28#' => divisor('_divisor v1', '_28'),
        '#29#' => divisor('_divisor v1', '_29'),
        '#30#' => divisor('_divisor v1', '_30'),
        '#31#' => divisor('_divisor v1', '_31'),
        '#32#' => divisor('_divisor v1', '_32'),
        '#33#' => divisor('_divisor v1', '_33'),
        '#34#' => divisor('_divisor v1', '_34'),
        '#35#' => divisor('_divisor v1', '_35'),
        '#36#' => divisor('_divisor v1', '_36'),
        '#37#' => divisor('_divisor v1', '_37'),
        '#38#' => divisor('_divisor v1', '_38'),
        '#39#' => divisor('_divisor v1', '_39'),
        '#40#' => divisor('_divisor v1', '_40'),
        '#41#' => divisor('_divisor v1', '_41'),
        '#42#' => divisor('_divisor v1', '_42'),
        '#43#' => divisor('_divisor v1', '_43'),
        '#44#' => divisor('_divisor v1', '_44'),
        '#45#' => divisor('_divisor v1', '_45'),
        '#46#' => divisor('_divisor v1', '_46'),
        '#47#' => divisor('_divisor v1', '_47'),
        '#48#' => divisor('_divisor v1', '_48'),
        '#49#' => divisor('_divisor v1', '_49'),
        '#50#' => divisor('_divisor v1', '_50'),
        '#51#' => divisor('_divisor v1', '_51'),
        '#52#' => divisor('_divisor v1', '_52'),
        '#53#' => divisor('_divisor v1', '_53'),
        '#54#' => divisor('_divisor v1', '_54'),
        '#55#' => divisor('_divisor v1', '_55'),
        '#56#' => divisor('_divisor v1', '_56'),
        '#57#' => divisor('_divisor v1', '_57'),
        '#58#' => divisor('_divisor v1', '_58'),
        '#59#' => divisor('_divisor v1', '_59'),
        '#60#' => divisor('_divisor v1', '_60'),
        '#61#' => divisor('_divisor v1', '_61'),
        '#62#' => divisor('_divisor v1', '_62'),
        '#63#' => divisor('_divisor v1', '_63'),
        '#64#' => divisor('_divisor v1', '_64'),
        '#65#' => divisor('_divisor v1', '_65'),
        '#66#' => divisor('_divisor v1', '_66'),
        '#67#' => divisor('_divisor v1', '_67'),
        '#68#' => divisor('_divisor v1', '_68'),
        '#69#' => divisor('_divisor v1', '_69'),
        '#70#' => divisor('_divisor v1', '_70'),
        '#71#' => divisor('_divisor v1', '_71'),
        '#72#' => divisor('_divisor v1', '_72'),
        '#73#' => divisor('_divisor v1', '_73'),
        '#74#' => divisor('_divisor v1', '_74'),
        '#75#' => divisor('_divisor v1', '_75'),
        '#76#' => divisor('_divisor v1', '_76'),
        '#77#' => divisor('_divisor v1', '_77'),
        '#78#' => divisor('_divisor v1', '_78'),
        '#79#' => divisor('_divisor v1', '_79'),
        '#80#' => divisor('_divisor v1', '_80'),
        '#81#' => divisor('_divisor v1', '_81'),
        '#82#' => divisor('_divisor v1', '_82'),
        '#83#' => divisor('_divisor v1', '_83'),
        '#84#' => divisor('_divisor v1', '_84'),
        '#85#' => divisor('_divisor v1', '_85'),
        '#86#' => divisor('_divisor v1', '_86'),
        '#87#' => divisor('_divisor v1', '_87'),
        '#88#' => divisor('_divisor v1', '_88'),
        '#89#' => divisor('_divisor v1', '_89'),
        '#90#' => divisor('_divisor v1', '_90'),
        '#91#' => divisor('_divisor v1', '_91'),
        '#92#' => divisor('_divisor v1', '_92'),
        '#93#' => divisor('_divisor v1', '_93'),
        '#94#' => divisor('_divisor v1', '_94'),
        '#95#' => divisor('_divisor v1', '_95'),
        '#96#' => divisor('_divisor v1', '_96'),
        '#97#' => divisor('_divisor v1', '_97'),
        '#98#' => divisor('_divisor v1', '_98'),
        '#99#' => divisor('_divisor v1', '_99'),
        '#100#' => divisor('_divisor v1', '_100'),
        '#101#' => divisor('_divisor v1', '_101'),
        '#102#' => divisor('_divisor v1', '_102'),
        '#103#' => divisor('_divisor v1', '_103'),
        '#104#' => divisor('_divisor v1', '_104'),
        '#105#' => divisor('_divisor v1', '_105'),
        '#106#' => divisor('_divisor v1', '_106'),
        '#107#' => divisor('_divisor v1', '_107'),
        '#108#' => divisor('_divisor v1', '_108'),
        '#109#' => divisor('_divisor v1', '_109'),
        '#110#' => divisor('_divisor v1', '_110'),
        '#111#' => divisor('_divisor v1', '_111'),
        '#112#' => divisor('_divisor v1', '_112'),
        '#113#' => divisor('_divisor v1', '_113'),
        '#114#' => divisor('_divisor v1', '_114'),
        '#115#' => divisor('_divisor v1', '_115'),
        '#116#' => divisor('_divisor v1', '_116'),
        '#117#' => divisor('_divisor v1', '_117'),
        '#118#' => divisor('_divisor v1', '_118'),
        '#119#' => divisor('_divisor v1', '_119'),
        '#120#' => divisor('_divisor v1', '_120'),
        '#121#' => divisor('_divisor v1', '_121'),
        '#122#' => divisor('_divisor v1', '_122'),
        '#123#' => divisor('_divisor v1', '_123'),
        '#124#' => divisor('_divisor v1', '_124'),
        '#125#' => divisor('_divisor v1', '_125'),
        '#126#' => divisor('_divisor v1', '_126'),
        '#127#' => divisor('_divisor v1', '_127'),
        '#128#' => divisor('_divisor v1', '_128'),
        '#129#' => divisor('_divisor v1', '_129'),
        '#130#' => divisor('_divisor v1', '_130'),
        '#131#' => divisor('_divisor v1', '_131'),
        '#132#' => divisor('_divisor v1', '_132'),
        '#133#' => divisor('_divisor v1', '_133'),
        '#134#' => divisor('_divisor v1', '_134'),
        '#135#' => divisor('_divisor v1', '_135'),
        '#136#' => divisor('_divisor v1', '_136'),
        '#137#' => divisor('_divisor v1', '_137'),
        '#138#' => divisor('_divisor v1', '_138'),
        '#139#' => divisor('_divisor v1', '_139'),
        '#140#' => divisor('_divisor v1', '_140'),
        '#141#' => divisor('_divisor v1', '_141'),
        '#142#' => divisor('_divisor v1', '_142'),
        '#143#' => divisor('_divisor v1', '_143'),
        '#144#' => divisor('_divisor v1', '_144'),
        '#145#' => divisor('_divisor v1', '_145'),
        '#146#' => divisor('_divisor v1', '_146'),
        '#147#' => divisor('_divisor v1', '_147'),
        '#148#' => divisor('_divisor v1', '_148'),
        '#149#' => divisor('_divisor v1', '_149'),
        '#150#' => divisor('_divisor v1', '_150'),
        '#151#' => divisor('_divisor v1', '_151'),
        '#152#' => divisor('_divisor v1', '_152'),
        '#153#' => divisor('_divisor v1', '_153'),
        '#154#' => divisor('_divisor v1', '_154'),
        '#155#' => divisor('_divisor v1', '_155'),
        '#156#' => divisor('_divisor v1', '_156'),
        '#157#' => divisor('_divisor v1', '_157'),
        '#158#' => divisor('_divisor v1', '_158'),
        '#159#' => divisor('_divisor v1', '_159'),
        '#160#' => divisor('_divisor v1', '_160'),
        '#161#' => divisor('_divisor v1', '_161'),
        '#162#' => divisor('_divisor v1', '_162'),
        '#163#' => divisor('_divisor v1', '_163'),
        '#164#' => divisor('_divisor v1', '_164'),
        '#165#' => divisor('_divisor v1', '_165'),
        '#166#' => divisor('_divisor v1', '_166'),
        '#167#' => divisor('_divisor v1', '_167'),
        '#168#' => divisor('_divisor v1', '_168'),
        '#169#' => divisor('_divisor v1', '_169'),
        '#170#' => divisor('_divisor v1', '_170'),
        '#171#' => divisor('_divisor v1', '_171'),
        '#172#' => divisor('_divisor v1', '_172'),
        '#173#' => divisor('_divisor v1', '_173'),
        '#174#' => divisor('_divisor v1', '_174'),
        '#175#' => divisor('_divisor v1', '_175'),
        '#176#' => divisor('_divisor v1', '_176'),
        '#177#' => divisor('_divisor v1', '_177'),
        '#178#' => divisor('_divisor v2', '_178'),
        '#179#' => divisor('_divisor v2', '_179'),
        '⸻℣⸻' => divisorMeiaLinha(),
        '⸻Ꝟ⸻' => divisorMeiaLinha(),
        '#sdom#' => divisor('_divisor v1', '_sdom'),
        '—♦♦♦—' => divisorMeiaLinha(),
        '―◊◊◊―' => divisorMeiaLinha(),
    );

    // Substitui as tags personalizadas pelo HTML correspondente
    return str_replace(array_keys($replacements), array_values($replacements), $content);
}

// Adiciona um filtro para aplicar a substituição em todo o conteúdo e título
add_filter('the_content', function($content) {
    $content = limpar_conteudo_personalizado($content);
    $content = processarHUD($content);
    return replace_custom_tags($content);
});