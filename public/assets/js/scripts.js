/**
 * Sistema de AssistÃªncia TÃ©cnica - Main Scripts
 */

$(document).ready(function () {

    // =====================================================
    // SIDEBAR TOGGLE
    // =====================================================
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
        });
    }

    // Restore sidebar state
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar && sidebar.classList.add('collapsed');
    }

    // =====================================================
    // DATATABLES INITIALIZATION
    // =====================================================
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: baseUrl + 'assets/json/pt-BR.json',
                search: '',
                searchPlaceholder: 'Buscar...',
            },
            pageLength: 25,
            responsive: true,
            order: [],
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip',
        });
    }

    // =====================================================
    // INPUT MASKS
    // =====================================================
    if ($.fn.mask) {
        $('.mask-cpf').mask('000.000.000-00');
        $('.mask-cnpj').mask('00.000.000/0000-00');
        $('.mask-telefone').mask('(00) 00000-0000');
        $('.mask-cep').mask('00000-000');
        $('.mask-money').mask('#.##0,00', { reverse: true });
    }

    // Dynamic CPF/CNPJ mask based on person type
    $('select[name="tipo_pessoa"]').on('change', function () {
        const campo = $('input[name="cpf_cnpj"]');
        if ($(this).val() === 'juridica') {
            campo.mask('00.000.000/0000-00');
            $('label[for="cpf_cnpj"]').text('CNPJ');
        } else {
            campo.mask('000.000.000-00');
            $('label[for="cpf_cnpj"]').text('CPF');
        }
    });

    // =====================================================
    // CEP LOOKUP (VIA CEP API)
    // =====================================================
    const handleCepLookup = function (el) {
        const $input = $(el);
        const cep = $input.val().replace(/\D/g, '');
        const $container = $input.closest('form, .modal-body, .row');

        if (cep.length === 8) {
            // Adiciona feedback de loading
            $input.addClass('loading-input').parent().addClass('position-relative');
            const $spinner = $('<div class="spinner-border spinner-border-sm position-absolute" style="right: 10px; top: 12px; z-index: 5;" role="status"></div>');
            $input.after($spinner);

            $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function (data) {
                $spinner.remove();
                $input.removeClass('loading-input');

                if (!data.erro) {
                    // Preenchimento inteligente baseado em nomes ou classes
                    $container.find('[name="endereco"], .js-logradouro').val(data.logradouro).trigger('change');
                    $container.find('[name="bairro"], .js-bairro').val(data.bairro).trigger('change');
                    $container.find('[name="cidade"], .js-cidade').val(data.localidade).trigger('change');
                    $container.find('[name="uf"], .js-uf').val(data.uf).trigger('change');
                    
                    // Foco no nÃºmero apÃ³s preenchimento
                    $container.find('[name="numero"], .js-numero').focus();
                } else {
                    alert('CEP nÃ£o encontrado.');
                    $input.val('').focus();
                }
            }).fail(function() {
                $spinner.remove();
                $input.removeClass('loading-input');
                console.warn('ServiÃ§o de CEP temporariamente indisponÃ­vel.');
            });
        }
    };

    // Gatilho no Blur
    $(document).on('blur', '.mask-cep, input[name="cep"]', function () {
        handleCepLookup(this);
    });

    // Gatilho automÃ¡tico ao completar os 8 dÃ­gitos (via mask callback se disponÃ­vel)
    if ($.fn.mask) {
        $('.mask-cep').mask('00000-000', {
            onComplete: function(cep, e, field) {
                handleCepLookup(field);
            }
        });
    }



    // =====================================================
    // CONFIRM DELETE
    // =====================================================
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const nome = $(this).data('nome') || 'este registro';

        if (confirm(`Tem certeza que deseja excluir "${nome}"? Esta aÃ§Ã£o nÃ£o pode ser desfeita.`)) {
            window.location.href = url;
        }
    });

    // =====================================================
    // OS ITEM CALCULATIONS
    // =====================================================
    $(document).on('input', 'input[name="quantidade"], input[name="valor_unitario"]', function () {
        const form = $(this).closest('form, .item-row');
        const qtd = parseFloat(form.find('input[name="quantidade"]').val()) || 0;
        const unitario = parseFloat(form.find('input[name="valor_unitario"]').val()) || 0;
        const total = qtd * unitario;
        form.find('input[name="valor_total"], .item-total').val(total.toFixed(2)).text('R$ ' + total.toFixed(2).replace('.', ','));
    });

    // =====================================================
    // FLASH MESSAGE AUTO-DISMISS
    // =====================================================
    setTimeout(function () {
        $('.alert-dismissible').fadeOut(500, function () {
            $(this).remove();
        });
    }, 5000);

    // =====================================================
    // TOOLTIP INIT
    // =====================================================
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

});

// Global base URL
var baseUrl = document.querySelector('meta[name="base-url"]')?.content ||
    window.location.origin + '/';

/**
 * Voltar padronizado: usa histÃ³rico se disponÃ­vel, senÃ£o vai para URL padrÃ£o.
 * @param {string} defaultUrl
 * @returns {boolean} false para evitar navegaÃ§Ã£o padrÃ£o
 */
function resolveFromParam() {
    try {
        const params = new URLSearchParams(window.location.search);
        const from = params.get('from');
        if (!from) return null;
        const url = new URL(from, window.location.origin);
        if (url.origin !== window.location.origin) return null;
        return url.href;
    } catch (e) {
        return null;
    }
}

function goBack(defaultUrl) {
    const fromTarget = resolveFromParam();
    if (fromTarget) {
        window.location.href = fromTarget;
        return false;
    }

    try {
        const ref = document.referrer || '';
        const sameOrigin = ref && ref.indexOf(window.location.origin) === 0;
        if (window.history.length > 1 && sameOrigin) {
            window.history.back();
            return false;
        }
    } catch (e) {
        // fallback abaixo
    }

    const target = defaultUrl || (baseUrl + 'dashboard');
    window.location.href = target;
    return false;
}

// Delegated handler for buttons/links with data-back-default
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-back-default]');
    if (!btn) return;
    e.preventDefault();
    const fallback = btn.getAttribute('data-back-default') || (baseUrl + 'dashboard');
    goBack(fallback);
});

/**
 * FunÃ§Ã£o para confirmar o encerramento de registros
 * @param {string} modulo - O slug do mÃ³dulo (os, equipamentos, estoque)
 * @param {number} id - O ID do registro
 */
function confirmarEncerramento(modulo, id) {
    const titulos = {
        'os': 'Ordem de ServiÃ§o',
        'equipamentos': 'Equipamento',
        'estoque': 'PeÃ§a/Item'
    };
    const nome = titulos[modulo] || 'registro';
    
    if (confirm(`Deseja realmente encerrar este ${nome}? O registro serÃ¡ mantido para histÃ³rico, mas nÃ£o estarÃ¡ mais disponÃ­vel para novas operaÃ§Ãµes.`)) {
        // Redirecionamento ou chamada AJAX para a lÃ³gica de encerramento
        // Por enquanto exibe alerta conforme status da evoluÃ§Ã£o do projeto
        alert(`A funcionalidade de processamento de encerramento para ${nome} estÃ¡ em fase de implementaÃ§Ã£o tÃ©cnica. O controle de acesso atual jÃ¡ valida sua permissÃ£o para esta aÃ§Ã£o.`);
    }
}

/**
 * Abre a pÃ¡gina de documentaÃ§Ã£o correspondente na mesma aba.
 * @param {string} page - Slugs ou caminhos curtos (ex: 'equipamentos', 'os')
 */
function openDocPage(page) {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || window.location.origin + '/';
    let path = page;

    // Mapeamento de atalhos para caminhos reais da documentaÃ§Ã£o
    const mapping = {
        'equipamentos': '01-manual-do-usuario/equipamentos.md',
        'ordens-de-servico': '01-manual-do-usuario/ordens-de-servico.md',
        'dashboard': '01-manual-do-usuario/dashboard.md',
        'clientes': '01-manual-do-usuario/clientes.md',
        'contatos': '01-manual-do-usuario/contatos.md',
        'estoque': '01-manual-do-usuario/estoque.md',
        'financeiro': '01-manual-do-usuario/financeiro.md',
        'relatorios': '01-manual-do-usuario/relatorios.md',
        'perfil': '01-manual-do-usuario/perfil.md',
        'fornecedores': '01-manual-do-usuario/fornecedores.md',
        'funcionarios': '01-manual-do-usuario/funcionarios.md',
        'servicos': '01-manual-do-usuario/servicos.md',
        'usuarios': '02-manual-administrador/usuarios-e-permissoes.md',
        'grupos': '02-manual-administrador/usuarios-e-permissoes.md',
        'configuracoes': '02-manual-administrador/configuracao-do-sistema.md',
        'equipamentos-tipos': '06-modulos-do-sistema/equipamentos-tipos.md',
        'equipamentos-marcas': '06-modulos-do-sistema/equipamentos-marcas.md',
        'equipamentos-modelos': '06-modulos-do-sistema/equipamentos-modelos.md',
        'equipamentos-defeitos': '06-modulos-do-sistema/defeitos-comuns.md',
        'defeitos-relatados': '06-modulos-do-sistema/defeitos-relatados.md',
        'crm': '06-modulos-do-sistema/crm.md',
        'crm-campanhas': '06-modulos-do-sistema/crm.md#campanhas',
        'crm-metricas-marketing': '06-modulos-do-sistema/crm.md#metricas-marketing',
        'crm-clientes-inativos': '06-modulos-do-sistema/crm.md#clientes-inativos',
        'whatsapp': '06-modulos-do-sistema/whatsapp.md',
        'atendimento-whatsapp': '06-modulos-do-sistema/central-de-mensagens.md',
        'atendimento-whatsapp-chatbot': '06-modulos-do-sistema/central-de-mensagens.md#chatbot',
        'atendimento-whatsapp-metricas': '06-modulos-do-sistema/central-de-mensagens.md#metricas',
        'atendimento-whatsapp-filas': '06-modulos-do-sistema/central-de-mensagens.md#filas',
        'atendimento-whatsapp-faq': '06-modulos-do-sistema/central-de-mensagens.md#faq',
        'atendimento-whatsapp-fluxos': '06-modulos-do-sistema/central-de-mensagens.md#fluxos',
        'atendimento-whatsapp-respostas': '06-modulos-do-sistema/central-de-mensagens.md#respostas-rapidas',
        'atendimento-whatsapp-config': '06-modulos-do-sistema/central-de-mensagens.md#configuracoes',
        'central-mensagens': '06-modulos-do-sistema/central-de-mensagens.md',
        'central-mensagens-chatbot': '06-modulos-do-sistema/central-de-mensagens.md#chatbot',
        'central-mensagens-metricas': '06-modulos-do-sistema/central-de-mensagens.md#metricas',
        'central-mensagens-filas': '06-modulos-do-sistema/central-de-mensagens.md#filas',
        'central-mensagens-faq': '06-modulos-do-sistema/central-de-mensagens.md#faq',
        'central-mensagens-fluxos': '06-modulos-do-sistema/central-de-mensagens.md#fluxos',
        'central-mensagens-respostas': '06-modulos-do-sistema/central-de-mensagens.md#respostas-rapidas',
        'central-mensagens-config': '06-modulos-do-sistema/central-de-mensagens.md#configuracoes',
        'design-system': '06-modulos-do-sistema/design-system.md',
        'estoque-movimentacoes': '01-manual-do-usuario/estoque.md#movimentacoes',
        'deploy-vps': '10-deploy/manual-tecnico-oficial-vps-ubuntu-24-ci4.md',
        'deploy-vps-script': '10-deploy/scripts/install_erp.sh',
        'deploy-vps-guia': '10-deploy/linux-vps-deployment.md',
        'vendas': '06-modulos-do-sistema/vendas.md'
    };

    if (mapping[page]) {
        path = mapping[page];
    }

    const from = encodeURIComponent(window.location.pathname + window.location.search + window.location.hash);
    window.location.href = `${baseUrl}documentacao?from=${from}#${encodeURIComponent(path)}`;
}

