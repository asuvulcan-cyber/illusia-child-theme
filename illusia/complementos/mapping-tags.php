<?php
/**
 * mapping-tags.php
 * Retorna array de tags para HTML, gerado dinamicamente para evitar repetições.
 */

$mapping = [];

// ——— TELAS COM GERARTELEDIV ———
$telas_div = ['arkus','arkus2','neko','neko2'];
foreach ($telas_div as $slug) {
    $mapping["#tela-{$slug}#"] = gerarTelaDiv(
        "tela-{$slug}",
        "hr-{$slug}"
    ) . gerarTela("{$slug}-text");
}

// ——— TELAS BASE ———
$telas_base = ['brosa','escura','escura2','escura3','escura4','escura5','prata','preta','roxa'];
foreach ($telas_base as $slug) {
    $mapping["#tela-{$slug}#"] = gerarTela(
        "tela-base tela-{$slug}"
    ) . gerarTela("{$slug}-text");
}

// ——— TELAS FUNDO (0 a 13) ———
for ($i = 0; $i <= 13; $i++) {
    $suffix = $i === 0 ? '' : $i;
    $mapping["#tela-fundo{$suffix}#"] = gerarTela(
        "fundo-base tela-fundo{$suffix}"
    ) . gerarTela('fundo-text');
}
$mapping['#tela-sangue#'] = gerarTela('fundo-base tela-sangue') . gerarTela('sangue-text');

// ——— SISTEMAS ———
$sistemas = [
    'avalon','sangrento','azulado','violeta','marrom','laranja',
    'violeta2'=>'violeta2','indigo'=>'indigo','laranja2'=>'laranja2','yellow'=>'amarelo',
    'escuro'=>'escuro','cell'=>'escuro','red'=>'vermelho','green'=>'verde',
    'cor'=>'cor','default'=>'sistema','roxo'=>'roxo','draco'=>'draco',
    'sen'=>'sen','azul'=>'azul','vermelho3'=>'vermelho3','over'=>'over',
    'over2'=>'over2','ouro'=>'ouro','vermelho2'=>'vermelho2','avermelhado'=>'avermelhado',
    'esverdeado'=>'esverdeado'
];
foreach ($sistemas as $key => $hr) {
    $slug = is_int($key) ? $hr : $key;
    $hr_id = $hr;
    $mapping["#sistema-{$slug}#"] = gerarTelaDiv(
        "sistema-base sistema-{$slug}",
        "hr-{$hr_id}"
    ) . gerarTela('texto-sistema');
}

// ——— DISPLAYS ———
$displays = ['','ia',2,3,4,5,6,7,'rpg','red'];
foreach ($displays as $d) {
    $key = $d === '' ? '#display#' : "#display-{$d}#";
    if ($d === 'rpg') {
        $mapping[$key] = gerarTelaRPG();
    } elseif ($d === 'red') {
        $mapping[$key] = gerarTela('fundo-base display-red') . gerarTela('display-text-red');
    } else {
        $cls = $d === '' ? '4' : $d;
        $mapping[$key] = gerarTela("fundo-base display-{$cls}") . gerarTela('display-text');
    }
}

// ——— 8-BITS ———
$bits = ['Azul','Verde','Vermelho','Amarelo','Laranja','Roxo','Ciano','Magenta','Cinza','Marrom',
         'AzulClaro','VerdeClaro','VermelhoClaro','AmareloClaro','LaranjaClaro','RoxoClaro','CianoClaro','MagentaClaro','CinzaClaro','MarromClaro'];
foreach ($bits as $color) {
    $mapping["#8b{$color}#"] = gerarTelaDiv(
        "_8b _8b{$color}",
        ''
    ) . gerarTela('_8b-text');
}

// ——— BALÕES ———
$mapping['#bs-dir#'] = gerarTela('_balao-simples _b-dir') . gerarTela('_b-text');
$mapping['#bs-esq#'] = gerarTela('_balao-simples _b-esq') . gerarTela('_b-text');
$mapping['#bsg-dir#'] = gerarTela('_balao-gradient _bg-dir');
$mapping['#bsg-esq#'] = gerarTela('_balao-gradient _bg-esq');

// ——— HEXÁGONOS ———
$mapping['#hex-azul#'] = gerarTela('_hex _hex-azul _hex-filter') . gerarTela('_hex-text');

// ——— FILTROS ———
$filters = ['_blur-','_hue-','_sepia-','_gray','_invert','_sat-','_brit-'];
foreach ($filters as $prefix) {
    $count = strpos($prefix, '-') ? range(1,8) : [0];
    foreach ($count as $i) {
        $code = $prefix === '_gray' ? '#cinzento' : ($prefix === '_invert' ? '#invertido' : "#" . trim($prefix, '-') . "-{$i}");
        $mapping[$code] = gerarTelaSpan(trim($prefix, '-') . ($i ?: ''));
    }
}

// ——— OUTROS ESTÁTICOS ———
$mapping['#folha#'] = gerarTela('folha') . gerarTela('folha-text');
$mapping['#quadro#'] = gerarTela('quadro');
$mapping['#carta#'] = gerarTela('letter');
$mapping['#pergaminho#'] = '<div class="parchment-wrapper"><div class="parchment"></div><div class="parchment-text">';
$mapping['#sp'] = '<span class="spoiler">';
$mapping['sp#'] = '</span>';
$mapping['#sa'] = '<span class="sensitive-alternative">';
$mapping['sa#'] = '</span>';
$mapping['#sc'] = '<span class="sensitive-content">';
$mapping['sc#'] = '</span>';

// ——— FECHAMENTOS ESPECÍFICOS ———
$mapping['#tela-fim#'] = fecharTela(2);
$mapping['#display-rpg-fim#'] = fecharTela(3);
$mapping['#pergaminho-fim#'] = fecharPergaminho();

// ——— DIVISORES MANUAIS ———
$manual = ['00','10'];
foreach ($manual as $num) {
    $mapping["#{$num}#"] = divisor('_divisor v2', "_{$num}");
}

// ——— MEIA LINHA E PONTOS ———
$mapping['⸻℣⸻'] = divisorMeiaLinha();
$mapping['⸻Ꝟ⸻'] = divisorMeiaLinha();
$mapping['—♦♦♦—'] = divisorMeiaLinha();
$mapping['―◊◊◊―'] = divisorMeiaLinha();

return $mapping;
