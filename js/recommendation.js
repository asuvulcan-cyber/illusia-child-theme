jQuery(document).ready(($) => {
    const ratingColors = {
        1: '#4CAF50', // Excelente
        2: '#FFEB3B', // Bom
        3: '#FF9800', // Regular
        4: '#FF5722', // Ruim
        5: '#F44336'  // Péssimo
    };

    // Cache dos botões e container de mensagem
    const $buttons = $('._custom-recommend-button');
    const $voteMessage = $('.vote-message');

    // Efeitos de hover: ao passar o mouse, altera o ícone e cor dos botões com nota maior ou igual
    $buttons.on('mouseenter', function() {
        const currentVote = parseInt($(this).data('vote'), 10);
        $buttons.each(function() {
            const starVote = parseInt($(this).data('vote'), 10);
            if (starVote >= currentVote) {
                $(this).find('i')
                    .removeClass('far')
                    .addClass('fas')
                    .css('color', ratingColors[currentVote]);
            }
        });
    }).on('mouseleave', function() {
        // Ao sair, restaura a aparência original dos botões que não estão marcados como 'voted'
        $buttons.not('.voted').find('i')
            .removeClass('fas')
            .addClass('far')
            .css('color', '');
    });

    // Envio de votos via AJAX
    $buttons.on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        if ($this.hasClass('voted')) return;

        const requestData = {
            action: 'process_recommendation_vote',
            security: recommendation_ajax.nonce,
            content_id: $this.data('content-id'),
            vote: $this.data('vote')
        };

        // Feedback visual de processamento
        $voteMessage.html('<div class="processing-message">Processando...</div>').fadeIn();

        $.ajax({
            type: 'POST',
            url: recommendation_ajax.ajaxurl,
            data: requestData,
            dataType: 'json'
        })
        .done((response) => {
            if (response.success) {
                // Remove a classe 'voted' de todos os botões
                $buttons.removeClass('voted');
                const newVote = response.data.new_vote;
                // Marca os botões com nota maior ou igual à nova nota
                $buttons.each(function() {
                    const btnVote = parseInt($(this).data('vote'), 10);
                    if (btnVote >= newVote) {
                        $(this).addClass('voted');
                    }
                });
                // Atualiza as contagens de votos
                $.each(response.data.vote_counts, (vote, count) => {
                    $(`button[data-vote="${vote}"] .vote-count`).text(count);
                });
                // Atualiza a barra de progresso e o texto da porcentagem
                $('.progress-bar').css('width', response.data.percentage + '%');
                $('.percentage-text').html(
                    `<i class="fas fa-star"></i> Avaliação: ${response.data.percentage}% (${response.data.total_votes} votos)`
                );
                // Se o novo voto for 1 (Excelente), dispara confete
                if (newVote === 1) {
                    confetti({
                        particleCount: 150,
                        spread: 100,
                        origin: { y: 0.6 },
                        colors: ['#4CAF50', '#45a049', '#388e3c']
                    });
                }
            }
            $voteMessage.html(`<div class="success-message">${response.data.message}</div>`);
        })
        .fail((jqXHR) => {
            const errorMsg = jqXHR.responseJSON?.data?.message || 'Erro na comunicação com o servidor';
            $voteMessage.html(`<div class="error-message">${errorMsg}</div>`);
        })
        .always(() => {
            setTimeout(() => $voteMessage.fadeOut(), 3000);
        });
    });
});
