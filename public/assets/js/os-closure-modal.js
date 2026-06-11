(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const config = window.osListConfig || {};
        if (!config.closureMetaUrlBase || !config.closureSubmitUrlBase) {
            return;
        }

        const modalElement = document.getElementById('osClosureModal');
        const form = document.getElementById('osClosureModalForm');
        if (!modalElement || !form || typeof window.bootstrap === 'undefined') {
            return;
        }

        const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
        const references = {
            numero: document.getElementById('osClosureModalNumero'),
            badges: document.getElementById('osClosureModalBadges'),
            clientName: document.getElementById('osClosureModalClientName'),
            clientPhone: document.getElementById('osClosureModalClientPhone'),
            clientEmail: document.getElementById('osClosureModalClientEmail'),
            equipmentName: document.getElementById('osClosureModalEquipmentName'),
            equipmentMeta: document.getElementById('osClosureModalEquipmentMeta'),
            equipmentSerial: document.getElementById('osClosureModalEquipmentSerial'),
            encerrarComo: document.getElementById('osClosureModalSelect'),
            dataEntrega: document.getElementById('osClosureModalDeliveryDate'),
            observacao: document.getElementById('osClosureModalObservacao'),
            notify: document.getElementById('osClosureModalNotify'),
            notifyHelp: document.getElementById('osClosureModalNotifyHelp'),
            returnToggle: document.getElementById('osClosureModalReturnToggle'),
            returnDate: document.getElementById('osClosureModalReturnDate'),
            addPayment: document.getElementById('osClosureAddPayment'),
            addAdvance: document.getElementById('osClosureAddAdvance'),
            fillOpenBalance: document.getElementById('osClosureFillOpenBalance'),
            paymentsList: document.getElementById('osClosurePaymentsList'),
            paymentsEmpty: document.getElementById('osClosurePaymentsEmpty'),
            receiptsJson: document.getElementById('osClosureReceiptsJson'),
            valorOs: document.getElementById('osClosureValorOs'),
            valorRecebido: document.getElementById('osClosureValorRecebido'),
            valorAberto: document.getElementById('osClosureValorAberto'),
            valorBaixa: document.getElementById('osClosureValorBaixa'),
            typeSummary: document.getElementById('osClosureTypeSummary'),
            statusSummary: document.getElementById('osClosureStatusSummary'),
            receivedSummary: document.getElementById('osClosureReceivedSummary'),
            actionSummary: document.getElementById('osClosureActionSummary'),
            projectedBalanceSummary: document.getElementById('osClosureProjectedBalanceSummary'),
            costSummary: document.getElementById('osClosureCostSummary'),
            feeSummary: document.getElementById('osClosureFeeSummary'),
            netSummary: document.getElementById('osClosureNetSummary'),
            profitSummary: document.getElementById('osClosureProfitSummary'),
            collectionsSummary: document.getElementById('osClosureCollectionsSummary'),
            submit: document.getElementById('osClosureModalSubmit'),
        };

        const state = {
            activeOsId: null,
            meta: null,
            payments: [],
            loading: false,
        };

        const originalConfirmarEncerramento = typeof window.confirmarEncerramento === 'function'
            ? window.confirmarEncerramento
            : null;

        const paymentMethodOptions = [
            { value: 'dinheiro', label: 'Dinheiro' },
            { value: 'pix', label: 'Pix' },
            { value: 'transferencia', label: 'Transferência' },
            { value: 'boleto', label: 'Boleto' },
            { value: 'cartao_credito', label: 'Cartão de crédito' },
            { value: 'cartao_debito', label: 'Cartão de débito' },
        ];

        const receiptKindOptions = [
            { value: 'baixa', label: 'Recebimento da baixa' },
            { value: 'adiantamento', label: 'Adiantamento' },
            { value: 'sinal', label: 'Sinal' },
        ];

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatCurrency(value) {
            const amount = Number(value || 0);
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL',
            }).format(Number.isFinite(amount) ? amount : 0);
        }

        function formatCurrencyInput(value) {
            const amount = Number(value || 0);
            if (!Number.isFinite(amount) || amount <= 0) {
                return '';
            }

            return amount.toFixed(2).replace('.', ',');
        }

        function parseCurrency(value) {
            let normalized = String(value ?? '').trim();
            if (normalized === '') {
                return 0;
            }

            normalized = normalized.replace(/[^\d,.-]/g, '');
            if (normalized.includes(',') && normalized.includes('.')) {
                if (normalized.lastIndexOf(',') > normalized.lastIndexOf('.')) {
                    normalized = normalized.replace(/\./g, '').replace(',', '.');
                } else {
                    normalized = normalized.replace(/,/g, '');
                }
            } else if (normalized.includes(',')) {
                normalized = normalized.replace(/\./g, '').replace(',', '.');
            }

            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? Math.round(parsed * 100) / 100 : 0;
        }

        function updateCsrfFromPayload(payload) {
            if (!payload || typeof payload.csrfHash !== 'string' || payload.csrfHash.trim() === '') {
                return;
            }

            config.csrfTokenValue = payload.csrfHash.trim();
        }

        function isCardPayment(method) {
            return method === 'cartao_credito' || method === 'cartao_debito';
        }

        function getCardDataset() {
            return state.meta?.cartao?.dataset || { operadoras: [], bandeiras: [], taxas: [] };
        }

        function getClosureOptions() {
            return Array.isArray(state.meta?.encerramento?.opcoes) ? state.meta.encerramento.opcoes : [];
        }

        function findClosureLabel(code) {
            const option = getClosureOptions().find((item) => String(item.codigo || '') === String(code || ''));
            return option ? String(option.nome || option.codigo || '') : '-';
        }

        function humanizeStatus(code) {
            const map = {
                entregue_reparado: 'Entregue reparado',
                devolvido_sem_reparo: 'Devolvido sem reparo',
                descartado: 'Descartado',
                entregue_pagamento_pendente: 'Concluída com pagamento pendente',
            };
            return map[String(code || '')] || '-';
        }

        function getOpenBalance() {
            return Number(state.meta?.financeiro?.valor_em_aberto || 0);
        }

        function getFinalOsValue() {
            return Number(state.meta?.os?.valor_final || 0);
        }

        function getCurrentReceived() {
            return Number(state.meta?.financeiro?.valor_adiantamento || state.meta?.financeiro?.valor_recebido || 0);
        }

        function getTotalEstimatedCosts() {
            return Number(state.meta?.custos?.total || 0);
        }

        function normalizePaymentMethod(method) {
            const normalized = String(method || '').trim();
            return paymentMethodOptions.some((item) => item.value === normalized) ? normalized : 'pix';
        }

        function normalizeReceiptKind(kind) {
            const normalized = String(kind || '').trim().toLowerCase();
            return receiptKindOptions.some((item) => item.value === normalized) ? normalized : 'baixa';
        }

        function getPaymentMethodLabel(method) {
            const option = paymentMethodOptions.find((item) => item.value === method);
            return option ? option.label : 'Forma de pagamento';
        }

        function getReceiptKindLabel(kind) {
            const option = receiptKindOptions.find((item) => item.value === normalizeReceiptKind(kind));
            return option ? option.label : 'Recebimento da baixa';
        }

        function isAdvanceKind(kind) {
            const normalized = normalizeReceiptKind(kind);
            return normalized === 'adiantamento' || normalized === 'sinal';
        }

        function getReceiptValueLabel(kind) {
            return normalizeReceiptKind(kind) === 'baixa' ? 'Valor recebido' : 'Valor lançado';
        }

        function getReceiptObservationLabel(kind) {
            return normalizeReceiptKind(kind) === 'baixa' ? 'Observações do recebimento' : 'Observações do lançamento';
        }

        function getReceiptObservationPlaceholder(kind) {
            const normalized = normalizeReceiptKind(kind);
            if (normalized === 'adiantamento') {
                return 'Ex.: cliente quitou antecipadamente antes da entrega da OS.';
            }

            if (normalized === 'sinal') {
                return 'Ex.: sinal recebido na aprovação do orçamento ou na entrada do equipamento.';
            }

            return 'Ex.: cliente parcelou no crédito, recibo emitido, observação da maquininha.';
        }

        function buildEmptyPayment(prefillValue, receiptKind) {
            const value = Number(prefillValue || 0);
            return {
                classificacao_recebimento: normalizeReceiptKind(receiptKind),
                forma_pagamento: 'pix',
                valor_texto: value > 0 ? formatCurrencyInput(value) : '',
                data_pagamento: references.dataEntrega?.value || state.meta?.os?.data_entrega || new Date().toISOString().slice(0, 10),
                operadora_id: '',
                bandeira_id: '',
                parcelas: '1',
                observacoes: '',
            };
        }

        function paymentValue(payment) {
            return parseCurrency(payment?.valor_texto || '');
        }

        function normalizeCardModalidade(payment) {
            return payment?.forma_pagamento === 'cartao_debito' ? 'debito' : 'credito';
        }

        function findApplicableRate(dataset, operadoraId, modalidade, parcelas, bandeiraId) {
            const rows = Array.isArray(dataset.taxas) ? dataset.taxas.filter((row) => {
                const rowOperadoraId = Number(row.operadora_id || 0);
                if (rowOperadoraId !== operadoraId) {
                    return false;
                }

                if (String(row.modalidade || '') !== modalidade) {
                    return false;
                }

                const inicio = Math.max(1, Number(row.parcelas_inicial || 1));
                const fim = Math.max(inicio, Number(row.parcelas_final || inicio));
                if (parcelas < inicio || parcelas > fim) {
                    return false;
                }

                const rowBandeiraId = row.bandeira_id ? Number(row.bandeira_id) : null;
                if (rowBandeiraId === null) {
                    return true;
                }

                return bandeiraId !== null && rowBandeiraId === bandeiraId;
            }) : [];

            if (!rows.length) {
                return null;
            }

            rows.sort((left, right) => {
                const leftSpecific = bandeiraId !== null && left.bandeira_id ? 1 : 0;
                const rightSpecific = bandeiraId !== null && right.bandeira_id ? 1 : 0;
                if (leftSpecific !== rightSpecific) {
                    return rightSpecific - leftSpecific;
                }

                const leftRange = Math.max(1, Number(left.parcelas_final || 1)) - Math.max(1, Number(left.parcelas_inicial || 1));
                const rightRange = Math.max(1, Number(right.parcelas_final || 1)) - Math.max(1, Number(right.parcelas_inicial || 1));
                if (leftRange !== rightRange) {
                    return leftRange - rightRange;
                }

                return Number(left.id || 0) - Number(right.id || 0);
            });

            return rows[0] || null;
        }

        function simulateCardPayment(payment) {
            const value = paymentValue(payment);
            if (!isCardPayment(payment.forma_pagamento)) {
                return {
                    ok: true,
                    valor_bruto: value,
                    valor_taxa: 0,
                    valor_liquido: value,
                    taxa_percentual: 0,
                    taxa_fixa: 0,
                    parcelas: 1,
                    modalidade: '',
                    operadora: null,
                    bandeira: null,
                };
            }

            const dataset = getCardDataset();
            if (!state.meta?.cartao?.disponivel) {
                return {
                    ok: false,
                    error: 'Configure primeiro as taxas de cartão em Finanças > Cartões e taxas.',
                };
            }

            const operadoraId = Number(payment.operadora_id || 0);
            if (operadoraId <= 0) {
                return {
                    ok: false,
                    error: 'Selecione a operadora da maquininha.',
                };
            }

            const modalidade = normalizeCardModalidade(payment);
            const parcelas = modalidade === 'debito'
                ? 1
                : Math.max(1, Number(payment.parcelas || 1));
            const bandeiraId = payment.bandeira_id ? Number(payment.bandeira_id) : null;
            const taxa = findApplicableRate(dataset, operadoraId, modalidade, parcelas, bandeiraId);
            if (!taxa) {
                return {
                    ok: false,
                    error: 'Não existe taxa ativa para a combinação de operadora, bandeira e parcelas.',
                };
            }

            const operadora = (dataset.operadoras || []).find((item) => Number(item.id || 0) === operadoraId) || null;
            const bandeira = bandeiraId !== null
                ? ((dataset.bandeiras || []).find((item) => Number(item.id || 0) === bandeiraId) || null)
                : null;
            const percentual = Number(taxa.taxa_percentual || 0);
            const taxaFixa = Number(taxa.taxa_fixa || 0);
            const valorTaxa = Math.round((((value * percentual) / 100) + taxaFixa) * 100) / 100;
            const valorLiquido = Math.round((value - valorTaxa) * 100) / 100;
            const prazoDias = Number(taxa.prazo_recebimento_dias || operadora?.prazo_padrao_dias || 0);

            return {
                ok: true,
                valor_bruto: value,
                valor_taxa: valorTaxa,
                valor_liquido: valorLiquido,
                taxa_percentual: percentual,
                taxa_fixa: taxaFixa,
                parcelas: parcelas,
                modalidade: modalidade,
                modalidade_label: modalidade === 'debito' ? 'Cartão de débito' : 'Cartão de crédito',
                prazo_recebimento_dias: prazoDias,
                data_prevista_recebimento: new Date(Date.now() + Math.max(0, prazoDias) * 86400000).toISOString().slice(0, 10),
                operadora: operadora,
                bandeira: bandeira,
                taxa: taxa,
            };
        }

        function getPaymentsSummary() {
            let totalRecebido = 0;
            let totalRecebimentoBaixa = 0;
            let totalTaxas = 0;
            let totalLiquido = 0;
            const cardErrors = [];

            state.payments.forEach((payment, index) => {
                const value = paymentValue(payment);
                const receiptKind = normalizeReceiptKind(payment.classificacao_recebimento);
                totalRecebido += value;
                if (receiptKind === 'baixa') {
                    totalRecebimentoBaixa += value;
                }

                if (!isCardPayment(payment.forma_pagamento)) {
                    totalLiquido += value;
                    return;
                }

                const simulation = simulateCardPayment(payment);
                if (!simulation.ok) {
                    cardErrors.push(`Lançamento #${index + 1}: ${simulation.error}`);
                    return;
                }

                totalTaxas += Number(simulation.valor_taxa || 0);
                totalLiquido += Number(simulation.valor_liquido || 0);
            });

            totalRecebido = Math.round(totalRecebido * 100) / 100;
            totalRecebimentoBaixa = Math.round(totalRecebimentoBaixa * 100) / 100;
            totalTaxas = Math.round(totalTaxas * 100) / 100;
            totalLiquido = Math.round(totalLiquido * 100) / 100;

            const saldoAnterior = getOpenBalance();
            const saldoProjetado = Math.round(Math.max(0, saldoAnterior - totalRecebido) * 100) / 100;
            const encerramentoSelecionado = String(references.encerrarComo?.value || '');
            const hasPositivePayments = totalRecebido > 0.009;
            const shouldUpdateStatus = !hasPositivePayments || totalRecebimentoBaixa > 0.009;
            const projectedStatus = shouldUpdateStatus
                ? (saldoProjetado > 0.009 ? 'entregue_pagamento_pendente' : encerramentoSelecionado)
                : String(state.meta?.os?.status || '');
            const projectedStatusLabel = shouldUpdateStatus
                ? humanizeStatus(projectedStatus)
                : 'Sem alteração de status';
            const lucroEstimado = Math.round((getFinalOsValue() - getTotalEstimatedCosts() - totalTaxas) * 100) / 100;

            return {
                totalRecebido: totalRecebido,
                totalRecebimentoBaixa: totalRecebimentoBaixa,
                totalTaxas: totalTaxas,
                totalLiquido: totalLiquido,
                saldoAnterior: saldoAnterior,
                saldoProjetado: saldoProjetado,
                shouldUpdateStatus: shouldUpdateStatus,
                projectedStatus: projectedStatus,
                projectedStatusLabel: projectedStatusLabel,
                lucroEstimado: lucroEstimado,
                cardErrors: cardErrors,
            };
        }

        function setSubmitLoading(isLoading) {
            state.loading = Boolean(isLoading);
            const defaultHtml = references.submit?.dataset.defaultHtml || references.submit?.innerHTML || '';
            if (references.submit && !references.submit.dataset.defaultHtml) {
                references.submit.dataset.defaultHtml = defaultHtml;
            }

            if (references.submit) {
                references.submit.disabled = state.loading;
                references.submit.innerHTML = state.loading
                    ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><span>Registrando baixa...</span>'
                    : (references.submit.dataset.defaultHtml || defaultHtml);
            }

            Array.from(form.elements || []).forEach((element) => {
                if (!element || element === references.submit || element.type === 'hidden') {
                    return;
                }
                element.disabled = state.loading;
            });
        }

        function renderClosureOptions() {
            const options = ['<option value="">Selecione</option>'];
            getClosureOptions().forEach((item) => {
                const code = String(item.codigo || '');
                if (code === '') {
                    return;
                }
                options.push(
                    `<option value="${escapeHtml(code)}">${escapeHtml(String(item.nome || code))}</option>`
                );
            });
            references.encerrarComo.innerHTML = options.join('');
        }

        function renderContext() {
            const os = state.meta?.os || {};
            references.numero.textContent = os.numero_os ? `OS ${os.numero_os}` : '-';
            references.badges.innerHTML = `${os.statusBadgeHtml || ''}${os.flowBadgeHtml || ''}${os.priorityBadgeHtml || ''}`;
            references.clientName.textContent = os.cliente_nome || '-';
            references.clientPhone.textContent = `Telefone: ${os.cliente_telefone || '-'}`;
            references.clientEmail.textContent = `E-mail: ${os.cliente_email || '-'}`;
            references.equipmentName.textContent = os.equipamento_nome || '-';
            references.equipmentMeta.textContent = `Tipo: ${os.equip_tipo_label || '-'}`;
            references.equipmentSerial.textContent = `Nº de série: ${os.equip_serie || '-'}`;

            references.notify.checked = Boolean(state.meta?.encerramento?.comunicacao_cliente_disponivel);
            references.notify.disabled = !state.meta?.encerramento?.comunicacao_cliente_disponivel;
            references.notifyHelp.textContent = state.meta?.encerramento?.comunicacao_cliente_disponivel
                ? 'O cliente será avisado usando o telefone cadastrado nesta OS. A mensagem segue com o PDF consolidado da impressão (A4).'
                : 'Não existe telefone válido no cadastro do cliente para enviar a confirmação.';
            references.notifyHelp.classList.toggle('text-danger', !state.meta?.encerramento?.comunicacao_cliente_disponivel);

            references.dataEntrega.value = String(os.data_entrega || new Date().toISOString().slice(0, 10));
            references.returnDate.value = String(state.meta?.encerramento?.retorno_padrao || '');
            references.returnToggle.checked = false;
            references.observacao.value = '';
            renderClosureOptions();

            const defaultOption = getClosureOptions()[0] || null;
            references.encerrarComo.value = defaultOption ? String(defaultOption.codigo || '') : '';
            syncReturnAvailability();
        }

        function syncReturnAvailability() {
            const canReturn = String(references.encerrarComo.value || '') === 'entregue_reparado';
            references.returnToggle.disabled = !canReturn;
            references.returnDate.disabled = !canReturn || !references.returnToggle.checked;

            if (!canReturn) {
                references.returnToggle.checked = false;
            }
        }

        function renderPayments() {
            const dataset = getCardDataset();
            references.paymentsEmpty.classList.toggle('d-none', state.payments.length > 0);
            references.paymentsList.innerHTML = state.payments.map((payment, index) => {
                const receiptKind = normalizeReceiptKind(payment.classificacao_recebimento);
                const cardMethod = isCardPayment(payment.forma_pagamento);
                const simulation = cardMethod ? simulateCardPayment(payment) : null;
                const receiptKindOptionsHtml = receiptKindOptions.map((item) => (
                    `<option value="${item.value}" ${receiptKind === item.value ? 'selected' : ''}>${escapeHtml(item.label)}</option>`
                )).join('');
                const operatorOptions = ['<option value="">Operadora</option>']
                    .concat((dataset.operadoras || []).map((item) => (
                        `<option value="${item.id}" ${String(payment.operadora_id || '') === String(item.id) ? 'selected' : ''}>${escapeHtml(item.nome || '')}</option>`
                    )))
                    .join('');
                const brandOptions = ['<option value="">Bandeira (opcional)</option>']
                    .concat((dataset.bandeiras || []).map((item) => (
                        `<option value="${item.id}" ${String(payment.bandeira_id || '') === String(item.id) ? 'selected' : ''}>${escapeHtml(item.nome || '')}</option>`
                    )))
                    .join('');
                const installmentsOptions = Array.from({ length: 12 }, function (_, rawIndex) {
                    const quantity = rawIndex + 1;
                    return `<option value="${quantity}" ${String(payment.parcelas || '1') === String(quantity) ? 'selected' : ''}>${quantity}x</option>`;
                }).join('');

                let simHtml = '';
                if (cardMethod) {
                    simHtml = simulation && simulation.ok
                        ? `<div class="os-closure-card-sim mt-3">
                                Taxa estimada: <strong>${escapeHtml(formatCurrency(simulation.valor_taxa))}</strong> ·
                                Líquido: <strong>${escapeHtml(formatCurrency(simulation.valor_liquido))}</strong> ·
                                Prazo: <strong>${escapeHtml(String(simulation.prazo_recebimento_dias || 0))} dia(s)</strong>
                           </div>`
                        : `<div class="alert alert-warning py-2 px-3 mt-3 mb-0">${escapeHtml(simulation?.error || 'Configure a taxa deste cartão para continuar.')}</div>`;
                }

                return `
                    <div class="os-closure-payment-row" data-payment-index="${index}">
                        <div class="os-closure-payment-head">
                            <div>
                                <strong>Lançamento ${index + 1}</strong>
                                <small>${escapeHtml(getReceiptKindLabel(receiptKind))} · ${escapeHtml(getPaymentMethodLabel(payment.forma_pagamento))}</small>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-payment-remove="${index}">
                                <i class="bi bi-trash3 me-1"></i>Remover
                            </button>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-3">
                                <label class="form-label">Classificação</label>
                                <select class="form-select" data-payment-field="classificacao_recebimento" data-payment-index="${index}">
                                    ${receiptKindOptionsHtml}
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label class="form-label">Forma de pagamento</label>
                                <select class="form-select" data-payment-field="forma_pagamento" data-payment-index="${index}">
                                    ${paymentMethodOptions.map((item) => (
                                        `<option value="${item.value}" ${payment.forma_pagamento === item.value ? 'selected' : ''}>${escapeHtml(item.label)}</option>`
                                    )).join('')}
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label class="form-label">${escapeHtml(getReceiptValueLabel(receiptKind))}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    inputmode="decimal"
                                    data-payment-field="valor_texto"
                                    data-payment-index="${index}"
                                    value="${escapeHtml(String(payment.valor_texto || ''))}"
                                    placeholder="0,00"
                                >
                            </div>
                            <div class="col-12 col-lg-3">
                                <label class="form-label">Data do pagamento</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    data-payment-field="data_pagamento"
                                    data-payment-index="${index}"
                                    value="${escapeHtml(String(payment.data_pagamento || ''))}"
                                >
                            </div>
                        </div>
                        <div class="row g-3 mt-1 os-closure-card-fields ${cardMethod ? 'is-visible' : ''}">
                            <div class="col-12 col-lg-4">
                                <label class="form-label">Operadora</label>
                                <select class="form-select" data-payment-field="operadora_id" data-payment-index="${index}">
                                    ${operatorOptions}
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label">Bandeira</label>
                                <select class="form-select" data-payment-field="bandeira_id" data-payment-index="${index}">
                                    ${brandOptions}
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label">Parcelas</label>
                                <select class="form-select" data-payment-field="parcelas" data-payment-index="${index}" ${payment.forma_pagamento === 'cartao_debito' ? 'disabled' : ''}>
                                    ${installmentsOptions}
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label">${escapeHtml(getReceiptObservationLabel(receiptKind))}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    data-payment-field="observacoes"
                                    data-payment-index="${index}"
                                    value="${escapeHtml(String(payment.observacoes || ''))}"
                                    placeholder="${escapeHtml(getReceiptObservationPlaceholder(receiptKind))}"
                                >
                            </div>
                        </div>
                        ${simHtml}
                    </div>
                `;
            }).join('');

            syncReceiptsJson();
            renderSummary();
        }

        function syncReceiptsJson() {
            const payload = state.payments.map((payment) => ({
                classificacao_recebimento: normalizeReceiptKind(payment.classificacao_recebimento),
                forma_pagamento: payment.forma_pagamento,
                valor: paymentValue(payment),
                data_pagamento: String(payment.data_pagamento || references.dataEntrega.value || ''),
                operadora_id: payment.operadora_id ? Number(payment.operadora_id) : null,
                bandeira_id: payment.bandeira_id ? Number(payment.bandeira_id) : null,
                parcelas: Math.max(1, Number(payment.parcelas || 1)),
                modalidade: isCardPayment(payment.forma_pagamento) ? normalizeCardModalidade(payment) : '',
                observacoes: String(payment.observacoes || ''),
            })).filter((item) => item.valor > 0);

            references.receiptsJson.value = JSON.stringify(payload);
        }

        function renderSummary() {
            const summary = getPaymentsSummary();
            references.valorOs.textContent = formatCurrency(getFinalOsValue());
            references.valorRecebido.textContent = formatCurrency(getCurrentReceived());
            references.valorAberto.textContent = formatCurrency(summary.saldoAnterior);
            references.valorBaixa.textContent = formatCurrency(summary.totalRecebido);
            references.typeSummary.textContent = findClosureLabel(references.encerrarComo.value);
            references.statusSummary.textContent = summary.projectedStatusLabel;
            references.receivedSummary.textContent = formatCurrency(getCurrentReceived());
            references.actionSummary.textContent = formatCurrency(summary.totalRecebido);
            references.projectedBalanceSummary.textContent = formatCurrency(summary.saldoProjetado);
            references.costSummary.textContent = formatCurrency(getTotalEstimatedCosts());
            references.feeSummary.textContent = formatCurrency(summary.totalTaxas);
            references.netSummary.textContent = formatCurrency(summary.totalLiquido);
            references.profitSummary.textContent = formatCurrency(summary.lucroEstimado);
            references.projectedBalanceSummary.classList.toggle('text-danger', summary.saldoProjetado > 0.009);
            references.projectedBalanceSummary.classList.toggle('text-success', summary.saldoProjetado <= 0.009);
            references.profitSummary.classList.toggle('os-closure-profit-positive', summary.lucroEstimado >= 0);
            references.profitSummary.classList.toggle('os-closure-profit-negative', summary.lucroEstimado < 0);

            if (!summary.shouldUpdateStatus) {
                references.collectionsSummary.className = 'alert alert-info mt-3 mb-0';
                references.collectionsSummary.textContent = 'Pagamento antecipado detectado. O valor será lançado no Financeiro, Fluxo de Caixa e DRE, sem alterar o status da OS.';
            } else if (summary.projectedStatus === 'entregue_pagamento_pendente') {
                references.collectionsSummary.className = 'alert alert-warning mt-3 mb-0';
                references.collectionsSummary.textContent = state.meta?.encerramento?.comunicacao_cliente_disponivel
                    ? 'Saldo pendente detectado. A OS ficará concluída, mas permanecerá em aberto para cobrança automática em 1, 3 e 5 dias.'
                    : 'Saldo pendente detectado. A OS ficará concluída, mas permanecerá em aberto. Cadastre um telefone válido para a cobrança automática funcionar corretamente.';
            } else {
                references.collectionsSummary.className = 'alert alert-success mt-3 mb-0';
                references.collectionsSummary.textContent = 'Pagamento suficiente para encerramento definitivo. O saldo financeiro desta OS será considerado quitado.';
            }
        }

        function resetState() {
            state.activeOsId = null;
            state.meta = null;
            state.payments = [];
            form.reset();
            references.receiptsJson.value = '[]';
            references.badges.innerHTML = '';
            references.paymentsList.innerHTML = '';
            references.paymentsEmpty.classList.remove('d-none');
            references.submit.classList.remove('btn-outline-secondary');
            references.submit.classList.add('btn-glow');
            setSubmitLoading(false);
        }

        function primeModal(payload) {
            state.meta = payload;
            state.payments = [];
            renderContext();
            renderPayments();
        }

        function validateBeforeSubmit() {
            if (!references.encerrarComo.value) {
                throw new Error('Selecione como a OS deve ser concluída.');
            }

            if (!references.dataEntrega.value) {
                throw new Error('Informe a data da entrega.');
            }

            if (references.returnToggle.checked && !references.returnDate.value) {
                throw new Error('Informe a data do retorno agendado.');
            }

            const summary = getPaymentsSummary();
            if (summary.cardErrors.length) {
                throw new Error(summary.cardErrors[0]);
            }

            if (summary.totalRecebido > summary.saldoAnterior + 0.001) {
                throw new Error('O total lançado nesta ação não pode ultrapassar o saldo financeiro em aberto.');
            }

            return summary;
        }

        function buildConfirmationHtml(summary, whatsappAvailable) {
            const whatsappNotice = summary.shouldUpdateStatus
                ? (whatsappAvailable
                    ? '<div class="alert alert-info py-2 px-3 mb-3">Se você confirmar, a mensagem do WhatsApp seguirá com o PDF consolidado da impressão (A4) da OS.</div>'
                    : '<div class="alert alert-secondary py-2 px-3 mb-3">Não há telefone válido para envio no WhatsApp. A baixa será registrada sem mensagem.</div>')
                : '';

            return `
                <div class="text-start">
                    ${whatsappNotice}
                    <p class="mb-2"><strong>Status após salvar:</strong> ${escapeHtml(summary.projectedStatusLabel)}</p>
                    <p class="mb-2"><strong>Lançado nesta ação:</strong> ${escapeHtml(formatCurrency(summary.totalRecebido))}</p>
                    <p class="mb-2"><strong>Taxas estimadas:</strong> ${escapeHtml(formatCurrency(summary.totalTaxas))}</p>
                    <p class="mb-2"><strong>Lucro estimado:</strong> ${escapeHtml(formatCurrency(summary.lucroEstimado))}</p>
                    <p class="mb-0"><strong>Saldo remanescente:</strong> ${escapeHtml(formatCurrency(summary.saldoProjetado))}</p>
                </div>
            `;
        }

        async function openClosureModal(osId) {
            const resolvedId = Number(osId || 0);
            if (!Number.isFinite(resolvedId) || resolvedId <= 0) {
                return;
            }

            try {
                const response = await window.fetch(`${config.closureMetaUrlBase}/${resolvedId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                updateCsrfFromPayload(payload);

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Não foi possível carregar os dados da baixa da OS.');
                }

                resetState();
                state.activeOsId = resolvedId;
                primeModal(payload);
                modal.show();
            } catch (error) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao abrir a baixa',
                        text: error.message || 'Não foi possível carregar os dados da OS.',
                    });
                }
            }
        }

        function getRemainingBalanceForEntry() {
            const summary = getPaymentsSummary();
            return Math.max(0, Number(summary.saldoAnterior || 0) - Number(summary.totalRecebido || 0));
        }

        function addPayment(prefillValue, receiptKind) {
            state.payments.push(buildEmptyPayment(prefillValue, receiptKind));
            renderPayments();
        }

        function fillOpenBalance() {
            const balance = getPaymentsSummary().saldoAnterior;
            if (balance <= 0.009) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'info',
                        title: 'OS sem saldo pendente',
                        text: 'Esta OS já está quitada financeiramente. Você pode registrar a baixa sem adicionar recebimentos.',
                    });
                }
                return;
            }

            state.payments = [buildEmptyPayment(balance, 'baixa')];
            renderPayments();
        }

        async function addAdvancePayment() {
            const remainingBalance = getRemainingBalanceForEntry();
            if (remainingBalance <= 0.009) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'info',
                        title: 'OS já está toda lançada',
                        text: 'Não há saldo restante para registrar como adiantamento ou sinal nesta ação.',
                    });
                }
                return;
            }

            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                addPayment('', 'sinal');
                return;
            }

            const decision = await window.Swal.fire({
                icon: 'question',
                title: 'Como deseja lançar esse valor antecipado?',
                text: 'Adiantamento total preenche o saldo financeiro restante sem alterar o status da OS. Sinal cria um lançamento parcial antes da entrega.',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Adiantamento total',
                denyButtonText: 'Sinal',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            });

            if (decision.isConfirmed) {
                addPayment(remainingBalance, 'adiantamento');
                return;
            }

            if (decision.isDenied) {
                addPayment('', 'sinal');
            }
        }

        references.addPayment?.addEventListener('click', function () {
            addPayment('', 'baixa');
        });

        references.addAdvance?.addEventListener('click', function () {
            addAdvancePayment();
        });

        references.fillOpenBalance?.addEventListener('click', function () {
            fillOpenBalance();
        });

        references.encerrarComo?.addEventListener('change', function () {
            syncReturnAvailability();
            renderSummary();
        });

        references.returnToggle?.addEventListener('change', function () {
            references.returnDate.disabled = !this.checked || this.disabled;
        });

        references.dataEntrega?.addEventListener('change', function () {
            state.payments = state.payments.map((payment) => ({
                ...payment,
                data_pagamento: payment.data_pagamento || this.value,
            }));
            renderPayments();
        });

        references.paymentsList?.addEventListener('click', function (event) {
            const removeButton = event.target.closest('[data-payment-remove]');
            if (!removeButton) {
                return;
            }

            const index = Number(removeButton.getAttribute('data-payment-remove') || '-1');
            if (!Number.isFinite(index) || index < 0) {
                return;
            }

            state.payments.splice(index, 1);
            renderPayments();
        });

        references.paymentsList?.addEventListener('change', function (event) {
            const field = event.target.getAttribute('data-payment-field');
            const index = Number(event.target.getAttribute('data-payment-index') || '-1');
            if (!field || !Number.isFinite(index) || !state.payments[index]) {
                return;
            }

            const payment = state.payments[index];
            payment[field] = event.target.value;

            if (field === 'forma_pagamento') {
                payment.forma_pagamento = normalizePaymentMethod(event.target.value);
                if (payment.forma_pagamento === 'cartao_debito') {
                    payment.parcelas = '1';
                }
                if (!isCardPayment(payment.forma_pagamento)) {
                    payment.operadora_id = '';
                    payment.bandeira_id = '';
                    payment.parcelas = '1';
                }
            }

            renderPayments();
        });

        references.paymentsList?.addEventListener('input', function (event) {
            const field = event.target.getAttribute('data-payment-field');
            const index = Number(event.target.getAttribute('data-payment-index') || '-1');
            if (!field || !Number.isFinite(index) || !state.payments[index]) {
                return;
            }

            if (field !== 'valor_texto') {
                return;
            }

            state.payments[index][field] = event.target.value;
            syncReceiptsJson();
            renderSummary();
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (state.loading || !state.activeOsId) {
                return;
            }

            try {
                const summary = validateBeforeSubmit();
                const whatsappAvailable = summary.shouldUpdateStatus && Boolean(references.notify && !references.notify.disabled);
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    const confirmation = await window.Swal.fire({
                        icon: !summary.shouldUpdateStatus ? 'info' : (summary.projectedStatus === 'entregue_pagamento_pendente' ? 'warning' : 'question'),
                        title: !summary.shouldUpdateStatus
                            ? 'Registrar pagamento antecipado?'
                            : (whatsappAvailable ? 'Enviar WhatsApp e registrar baixa?' : 'Confirmar baixa da OS?'),
                        html: buildConfirmationHtml(summary, whatsappAvailable),
                        showCancelButton: true,
                        showDenyButton: whatsappAvailable,
                        confirmButtonText: summary.shouldUpdateStatus
                            ? (whatsappAvailable ? 'Enviar e registrar baixa' : 'Registrar baixa')
                            : 'Registrar pagamento',
                        denyButtonText: 'Somente registrar baixa',
                        cancelButtonText: 'Voltar',
                        reverseButtons: true,
                        focusDeny: whatsappAvailable,
                    });

                    if (!confirmation.isConfirmed && !confirmation.isDenied) {
                        return;
                    }

                    if (summary.shouldUpdateStatus) {
                        references.notify.checked = Boolean(confirmation.isConfirmed && whatsappAvailable);
                    }
                }

                const formData = new window.FormData(form);
                if (summary.shouldUpdateStatus) {
                    if (references.notify.checked) {
                        formData.set('comunicar_cliente', '1');
                    } else {
                        formData.delete('comunicar_cliente');
                    }
                }
                if (config.csrfTokenKey && config.csrfTokenValue) {
                    formData.append(config.csrfTokenKey, config.csrfTokenValue);
                }

                setSubmitLoading(true);

                const response = await window.fetch(`${config.closureSubmitUrlBase}/${state.activeOsId}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const payload = await response.json();
                updateCsrfFromPayload(payload);

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Não foi possível concluir a baixa da OS.');
                }

                modal.hide();
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    await window.Swal.fire({
                        icon: payload.warning ? 'warning' : 'success',
                        title: payload.status_unchanged ? 'Pagamento antecipado registrado' : (payload.status === 'entregue_pagamento_pendente' ? 'Baixa concluída com pendência' : 'Baixa concluída'),
                        html: `
                            <div class="text-start">
                                <p class="mb-2">${escapeHtml(payload.message || 'Baixa registrada com sucesso.')}</p>
                                <p class="mb-2"><strong>Status da OS:</strong> ${escapeHtml(String(payload.status_label || '-'))}</p>
                                <p class="mb-0"><strong>Lucro estimado:</strong> ${escapeHtml(formatCurrency(payload.lucro_estimado || 0))}</p>
                            </div>
                        `,
                    });
                }

                if (window.osListController && typeof window.osListController.reload === 'function') {
                    window.osListController.reload(true);
                }
            } catch (error) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao registrar a baixa',
                        text: error.message || 'Não foi possível concluir a baixa desta OS.',
                    });
                }
            } finally {
                setSubmitLoading(false);
            }
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            resetState();
        });

        window.openOsClosureModal = openClosureModal;
        window.confirmarEncerramento = function (modulo, id) {
            if (modulo === 'os') {
                openClosureModal(id);
                return;
            }

            if (typeof originalConfirmarEncerramento === 'function') {
                originalConfirmarEncerramento(modulo, id);
            }
        };
    });
})();
