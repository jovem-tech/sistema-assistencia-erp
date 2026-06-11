#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { TextDecoder } = require('util');

const WINDOWS_1252_DECODER = new TextDecoder('windows-1252');

const TEXT_EXTENSIONS = new Set([
    '.css',
    '.csv',
    '.env',
    '.htm',
    '.html',
    '.ini',
    '.js',
    '.json',
    '.md',
    '.php',
    '.sql',
    '.svg',
    '.txt',
    '.xml',
    '.yaml',
    '.yml',
]);

const EXCLUDED_PREFIXES = [
    '.tools/',
    'documentacao/12-comercial/scripts/node_modules/',
    'public/assets/agents/',
    'public/assets/vendor/',
    'public/uploads/',
    'tools/ImageMagick/',
    'tools/ffmpeg/bin/',
    'tools/gs/',
    'tools/tesseract/',
    'vendor/',
    'whatsapp-api/.wwebjs_auth/',
    'writable/',
];

const EXCLUDED_FILES = new Set([
    'dashboard.json',
    'login_out.json',
    'output.json',
    'temp_orcamento_panel_snapshot.txt',
    'usuarios_err.json',
]);

const WINDOWS_1252_UNICODE_MAP = new Map([
    [0x80, 0x20AC],
    [0x82, 0x201A],
    [0x83, 0x0192],
    [0x84, 0x201E],
    [0x85, 0x2026],
    [0x86, 0x2020],
    [0x87, 0x2021],
    [0x88, 0x02C6],
    [0x89, 0x2030],
    [0x8A, 0x0160],
    [0x8B, 0x2039],
    [0x8C, 0x0152],
    [0x8E, 0x017D],
    [0x91, 0x2018],
    [0x92, 0x2019],
    [0x93, 0x201C],
    [0x94, 0x201D],
    [0x95, 0x2022],
    [0x96, 0x2013],
    [0x97, 0x2014],
    [0x98, 0x02DC],
    [0x99, 0x2122],
    [0x9A, 0x0161],
    [0x9B, 0x203A],
    [0x9C, 0x0153],
    [0x9E, 0x017E],
    [0x9F, 0x0178],
]);

const MOJIBAKE_PATTERNS = [
    /\u00C3[^A-Za-z0-9\s]/g,
    /\u00C2[^A-Za-z0-9\s]/g,
    /\u00E2\u20AC\u2122/g,
    /\u00E2\u20AC\u0153/g,
    /\u00E2\u20AC\u009D/g,
    /\u00E2\u20AC\u201C/g,
    /\u00E2\u20AC\u201D/g,
    /\u00E2\u20AC\u00A2/g,
    /\u00E2\u20AC\u00A6/g,
    /\u00E2\u201E\u00A2/g,
    /\u00E2\u201A\u00AC/g,
    /\u00E2\u20AC/g,
    /\uFFFD/g,
];

const QUESTION_INSIDE_WORD_REGEX = /(?<=[A-Za-zÀ-ÖØ-öø-ÿ])\?(?=[A-Za-zÀ-ÖØ-öø-ÿ])/gu;

function getTrackedFiles() {
    const output = execFileSync('git', ['ls-files'], { encoding: 'utf8' });
    return output.split(/\r?\n/).filter(Boolean);
}

function isCandidateFile(filePath) {
    if (EXCLUDED_FILES.has(filePath)) {
        return false;
    }

    if (EXCLUDED_PREFIXES.some((prefix) => filePath.startsWith(prefix))) {
        return false;
    }

    const extension = path.extname(filePath).toLowerCase();
    return TEXT_EXTENSIONS.has(extension);
}

function splitBufferWithLineEndings(buffer) {
    const lineBuffers = [];
    const lineEndings = [];
    let cursor = 0;

    for (let index = 0; index < buffer.length; index++) {
        if (buffer[index] !== 0x0A) {
            continue;
        }

        let lineEnd = index;
        let ending = '\n';
        if (lineEnd > cursor && buffer[lineEnd - 1] === 0x0D) {
            lineEnd--;
            ending = '\r\n';
        }

        lineBuffers.push(buffer.slice(cursor, lineEnd));
        lineEndings.push(ending);
        cursor = index + 1;
    }

    lineBuffers.push(buffer.slice(cursor));
    lineEndings.push('');

    return { lineBuffers, lineEndings };
}

function encodeWindows1252(text) {
    const bytes = [];

    for (let index = 0; index < text.length; index++) {
        const codePoint = text.codePointAt(index);
        if (codePoint > 0xFFFF) {
            index++;
        }

        if (codePoint <= 0xFF) {
            bytes.push(codePoint);
            continue;
        }

        let mappedByte = 0x3F;
        for (const [byte, unicodePoint] of WINDOWS_1252_UNICODE_MAP.entries()) {
            if (unicodePoint === codePoint) {
                mappedByte = byte;
                break;
            }
        }

        bytes.push(mappedByte);
    }

    return Buffer.from(bytes);
}

function fixOnce(text) {
    return encodeWindows1252(text).toString('utf8');
}

function scoreText(text) {
    let badCount = 0;
    for (const pattern of MOJIBAKE_PATTERNS) {
        badCount += (text.match(pattern) || []).length;
    }

    return {
        badCount,
        questionInsideWordCount: (text.match(QUESTION_INSIDE_WORD_REGEX) || []).length,
    };
}

function isBetterCandidate(candidate, currentBest) {
    if (candidate.badCount !== currentBest.badCount) {
        return candidate.badCount < currentBest.badCount;
    }

    if (candidate.questionInsideWordCount !== currentBest.questionInsideWordCount) {
        return candidate.questionInsideWordCount < currentBest.questionInsideWordCount;
    }

    if (candidate.passCount !== currentBest.passCount) {
        return candidate.passCount < currentBest.passCount;
    }

    return candidate.baseEncoding === 'utf8' && currentBest.baseEncoding === 'windows-1252';
}

function chooseBestLine(lineBuffer) {
    const baseCandidates = [
        { baseEncoding: 'utf8', text: lineBuffer.toString('utf8') },
        { baseEncoding: 'windows-1252', text: WINDOWS_1252_DECODER.decode(lineBuffer) },
    ];

    let bestCandidate = null;

    for (const baseCandidate of baseCandidates) {
        let currentText = baseCandidate.text;

        for (let passCount = 0; passCount <= 4; passCount++) {
            const candidate = {
                baseEncoding: baseCandidate.baseEncoding,
                passCount,
                text: currentText,
                ...scoreText(currentText),
            };

            if (!bestCandidate || isBetterCandidate(candidate, bestCandidate)) {
                bestCandidate = candidate;
            }

            currentText = fixOnce(currentText);
        }
    }

    return bestCandidate;
}

function applyLiteralReplacements(text, replacements) {
    let nextText = text;

    for (const [source, target] of replacements) {
        nextText = nextText.split(source).join(target);
    }

    return nextText;
}

function buildBrowserNormalizerBlock(functionName, fallbackPairs) {
    const fallbackLines = fallbackPairs
        .map((pair) => `        ${JSON.stringify(pair)},`)
        .join('\n');

    return `    const WINDOWS_1252_PAIRS = [
        [0x80, 0x20AC], [0x82, 0x201A], [0x83, 0x0192], [0x84, 0x201E], [0x85, 0x2026],
        [0x86, 0x2020], [0x87, 0x2021], [0x88, 0x02C6], [0x89, 0x2030], [0x8A, 0x0160],
        [0x8B, 0x2039], [0x8C, 0x0152], [0x8E, 0x017D], [0x91, 0x2018], [0x92, 0x2019],
        [0x93, 0x201C], [0x94, 0x201D], [0x95, 0x2022], [0x96, 0x2013], [0x97, 0x2014],
        [0x98, 0x02DC], [0x99, 0x2122], [0x9A, 0x0161], [0x9B, 0x203A], [0x9C, 0x0153],
        [0x9E, 0x017E], [0x9F, 0x0178],
    ];
    const WINDOWS_1252_REVERSE_MAP = new Map(WINDOWS_1252_PAIRS.map(([byte, codePoint]) => [codePoint, byte]));
    const UTF8_DECODER = new TextDecoder('utf-8');
    const MOJIBAKE_PATTERNS = [
        /\\u00C3[^A-Za-z0-9\\s]/g,
        /\\u00C2[^A-Za-z0-9\\s]/g,
        /\\u00E2\\u20AC\\u2122/g,
        /\\u00E2\\u20AC\\u0153/g,
        /\\u00E2\\u20AC\\u009D/g,
        /\\u00E2\\u20AC\\u201C/g,
        /\\u00E2\\u20AC\\u201D/g,
        /\\u00E2\\u20AC\\u00A2/g,
        /\\u00E2\\u20AC\\u00A6/g,
        /\\u00E2\\u201E\\u00A2/g,
        /\\u00E2\\u201A\\u00AC/g,
        /\\u00E2\\u20AC/g,
        /\\uFFFD/g,
    ];
    const MOJIBAKE_FALLBACKS = [
${fallbackLines}
    ];

    function encodeWindows1252(value) {
        const bytes = [];

        for (let index = 0; index < value.length; index++) {
            const codePoint = value.codePointAt(index);
            if (codePoint > 0xFFFF) {
                index++;
            }

            if (codePoint <= 0xFF) {
                bytes.push(codePoint);
                continue;
            }

            bytes.push(WINDOWS_1252_REVERSE_MAP.get(codePoint) ?? 0x3F);
        }

        return new Uint8Array(bytes);
    }

    function countMojibake(value) {
        return MOJIBAKE_PATTERNS.reduce((total, pattern) => total + ((value.match(pattern) || []).length), 0);
    }

    function fixMojibakePass(value) {
        try {
            return UTF8_DECODER.decode(encodeWindows1252(value));
        } catch (error) {
            return value;
        }
    }

    const ${functionName} = (value) => {
        if (typeof value !== 'string' || value === '') {
            return value;
        }

        let normalized = value;
        let best = normalized;
        let bestScore = countMojibake(normalized);

        for (let pass = 0; pass < 4; pass++) {
            normalized = fixMojibakePass(normalized);
            const currentScore = countMojibake(normalized);
            if (currentScore < bestScore) {
                best = normalized;
                bestScore = currentScore;
            }
        }

        MOJIBAKE_FALLBACKS.forEach(([source, target]) => {
            best = best.split(source).join(target);
        });

        return best;
    };`;
}

function postProcess(filePath, text) {
    if (filePath === 'app/Controllers/Os.php') {
        return applyLiteralReplacements(text, [
            ['Assist?ncia T?cnica', 'Assistência Técnica'],
            ['Ol? ', 'Olá '],
            ['assist?ncia', 'assistência'],
            ['análise t?cnica', 'análise técnica'],
            ['preco_m?nimo', 'preco_minimo'],
            ['m?dulo OS', 'módulo OS'],
            ['acess?rio', 'acessório'],
        ]);
    }

    if (filePath === 'app/Views/os/form.php') {
        return applyLiteralReplacements(text, [
            ['CONTE?DO', 'CONTEÚDO'],
            ['?REA PRINCIPAL DO FORMUL?RIO', 'ÁREA PRINCIPAL DO FORMULÁRIO'],
            ['Transfer?ncia', 'Transferência'],
            ['C?MERA', 'CÂMERA'],
            ['inst?ncia', 'instância'],
            ['unica', 'única'],
            ['?ltimos 6 d?gitos do chip', 'Últimos 6 dígitos do chip'],
            ['autom?tico', 'automático'],
            ['formul?rio', 'formulário'],
            ['m?ximo', 'máximo'],
            ['R?pido', 'Rápido'],
            ['confer?ncia', 'conferência'],
            ['Petr?leo', 'Petróleo'],
            ['Lil?s', 'Lilás'],
            ['Discrep?ncia', 'Discrepância'],
            ['H?brido', 'Híbrido'],
            ['EDI?O', 'EDIÇÃO'],
            ['pr?prio', 'próprio'],
        ]);
    }

    if (filePath === 'app/Views/equipamentos/form.php') {
        return applyLiteralReplacements(text, [
            ['L?GICA DE DETEC??O DE COR INTELIGENTE NA IMAGEM', 'LÓGICA DE DETECÇÃO DE COR INTELIGENTE NA IMAGEM'],
        ]);
    }

    if (filePath === 'documentacao/08-correcoes/2026-03-20-correcao-codificacao-utf8-telas.md') {
        return [
            '# Correção Global de Codificação de Caracteres (UTF-8)',
            '',
            '**Data:** 20/03/2026',
            '**Módulo:** Global (Views, Controllers, Documentação)',
            '**Tipo:** Correção de Bug (Encoding)',
            '',
            '## Problema Relatado',
            'O usuário relatou que páginas do sistema, como o painel de Métricas do WhatsApp ("Métricas da Central"), incluindo botões ("aplicar período") e formulários, estavam exibindo caracteres estranhos no lugar de acentos.',
            '',
            'A análise identificou que o problema foi causado por uma **dupla codificação UTF-8** no código-fonte. Isso ocorreu porque arquivos nativamente salvos em UTF-8 foram interpretados temporariamente como ISO-8859-1 (ou Windows-1252) ao serem manipulados por alguma ferramenta de edição ou script, e salvos novamente como UTF-8, o que gerou fragmentos de mojibake em vez do texto canônico.',
            '',
            '## Solução Implementada',
            'Para resolver a raiz do problema de forma cirúrgica e limpa, foi criado e executado um utilitário temporário que percorreu as pastas-chave do projeto e reverteu os trechos corrompidos para o UTF-8 canônico.',
            '',
            '### Ações Técnicas',
            '1. **Varredura recursiva:**',
            '   O sistema escaneou os seguintes diretórios base procurando ativamente por padrões clássicos de corrupção de encoding:',
            '   - `app/Views/`',
            '   - `app/Controllers/`',
            '   - `app/Models/`',
            '   - `app/Helpers/`',
            '   - `documentacao/`',
            '2. **Correção direta binária:**',
            '   Para cada arquivo afetado, o utilitário reverteu os dados da string para o mapa de bytes original antes de reconstruir o texto em UTF-8 limpo.',
            '3. **Limpeza do ambiente:**',
            '   O script corretivo executado em back-end temporário (CLI) foi removido após o uso, preservando a integridade dos diretórios de produção.',
            '4. **Impacto:**',
            '   O painel de Métricas do WhatsApp (`atendimento-whatsapp/metricas`) e as abas das Configurações (`FAQ`, `Respostas Rápidas`, `Fluxos`) voltaram a ser renderizados com ortografia limpa e profissional no layout do sistema.',
            '',
            '## Arquivos Afetados Modificados',
            'Um conjunto de views, controllers e documentos afetados por dupla codificação foi corrigido integralmente. Exemplos representativos:',
            '- `app/Views/central_mensagens/metricas.php`',
            '- `app/Views/central_mensagens/respostas_rapidas.php`',
            '- `app/Views/central_mensagens/faq.php`',
            '- `app/Views/central_mensagens/index.php`',
            '- `app/Views/layouts/sidebar.php`',
            '- `documentacao/08-correcoes/2026-03-correcao-fotos-e-caracteres.md`',
            '- templates de emissão PDF e relatórios correlatos.',
            '',
            '## Próximos Passos',
            'O layout e a leitura textual do sistema ERP e do CRM foram normalizados, deixando o ambiente apto para evolução funcional sem ruído visual causado por encoding.',
            '',
        ].join('\n');
    }

    if (filePath === 'documentacao/08-correcoes/2026-03-21-correcao-encoding-ui.md') {
        return [
            '# Correção de Encoding na Interface (Mojibake)',
            '',
            '**Data:** 21/03/2026',
            '**Status:** Concluído',
            '',
            '## Problema',
            'Foram identificados textos com caracteres corrompidos na interface (exemplos: `Serviços`, `Relatórios`, `autenticação`, `Permissões`), afetando títulos, labels, mensagens e comentários de organização de rotas.',
            '',
            '## Causa',
            'Arquivos com conteúdo em UTF-8 passaram por histórico de codificação inconsistente em pontos específicos, gerando mojibake em strings visíveis ao usuário.',
            '',
            '## Ação aplicada',
            '1. Varredura de arquivos de interface (`app/Views`, `public/assets/js`) por padrões clássicos de mojibake.',
            '2. Correção controlada de conteúdo com reinterpretação dos trechos corrompidos.',
            '3. Ajustes manuais finais em labels pontuais que ainda continham caractere de substituição.',
            '4. Normalização dos comentários de seção em rotas para manter a legibilidade do código.',
            '',
            '## Arquivos atualizados',
            '- `app/Config/Routes.php`',
            '- `app/Views/equipamentos/form.php`',
            '- `app/Views/estoque/movimentacoes.php`',
            '- `app/Views/grupos/form.php`',
            '- `app/Views/grupos/index.php`',
            '- `app/Views/grupos/permissoes.php`',
            '- `app/Views/layouts/sidebar.php`',
            '- `app/Views/os/form.php`',
            '- `app/Views/relatorios/view_estoque.php`',
            '- `app/Views/relatorios/view_financeiro.php`',
            '- `app/Views/relatorios/view_os.php`',
            '- `app/Views/servicos/form.php`',
            '- `app/Views/servicos/index.php`',
            '- `public/assets/js/scripts.js`',
            '',
            '## Validação',
            '- Verificação de sintaxe PHP (`php -l`) nos principais arquivos alterados.',
            '- Verificação de sintaxe JS (`node --check`) em `public/assets/js/scripts.js`.',
            '- Nova varredura por padrões de mojibake em `app/` e `public/assets/` sem ocorrências residuais críticas.',
            '',
            '## Impacto',
            '- Nenhuma regra de negócio foi alterada.',
            '- Ajuste exclusivamente textual/encoding para estabilizar a exibição de caracteres na UI.',
            '',
        ].join('\n');
    }

    if (filePath === 'documentacao/5_sistema_de_permissoes.md') {
        let nextText = applyLiteralReplacements(text, [
            ['> **Versão:** 2.0 ? implementado em março/2026', '> **Versão:** 2.0 - implementado em março/2026'],
            ['> **Arquitetura:** Role-Based Access Control (RBAC) ? CodeIgniter 4', '> **Arquitetura:** Role-Based Access Control (RBAC) - CodeIgniter 4'],
            ['### 4.2 Funções RBAC ? `app/Helpers/sistema_helper.php`', '### 4.2 Funções RBAC - `app/Helpers/sistema_helper.php`'],
            ['**Chave única:** `(grupo_id, modulo_id, permissao_id)` ? sem permissões duplicadas.', '**Chave única:** `(grupo_id, modulo_id, permissao_id)` - sem permissões duplicadas.'],
        ]);

        nextText = nextText.replace(
            /### 4\.1 Fluxo completo de uma requisição[\s\S]*?---\n\n### 4\.2 Funções RBAC/m,
            [
                '### 4.1 Fluxo completo de uma requisição',
                '',
                '```text',
                'Browser/Cliente',
                '    |',
                '    | GET /financeiro',
                '    v',
                'CI4 Router (Routes.php)',
                "  - ['filter' => 'auth']",
                "  - ['filter' => 'permission:financeiro:visualizar']",
                '    |',
                '    v',
                'AuthFilter (Filters/AuthFilter.php)',
                "  - session->get('logged_in')",
                '  - verifica timeout de 30 min',
                '  - atualiza last_activity',
                '    |',
                '    v autenticado',
                'PermissionFilter (Filters/PermissionFilter.php)',
                "  - extrai 'financeiro:visualizar'",
                "  - chama can('financeiro', 'visualizar')",
                '  - loadUserPermissions()',
                '    - reaproveita cache em sessão quando disponível',
                '    - consulta `grupo_permissoes` quando necessário',
                '  - verifica o mapa final de permissões',
                '',
                'Sem permissão:',
                '  - AJAX -> HTTP 403 JSON',
                "  - Browser -> redirect /dashboard + flashdata('error') + LogModel::registrar()",
                '',
                'Com permissão:',
                '  - Controller::action()',
                '  - View com botões protegidos por `can()`',
                '```',
                '',
                '---',
                '',
                '### 4.2 Funções RBAC',
            ].join('\n')
        );

        return nextText;
    }

    if (filePath === 'public/assets/js/central-mensagens.js') {
        return text.replace(
            /    const (?:WINDOWS_1252_PAIRS = \[|normalizeMojibake = \(value\) => \{)[\s\S]*?\n    const swal = \(options\) => \{/m,
            buildBrowserNormalizerBlock('normalizeMojibake', [
                ['N?o', 'Não'],
                ['n?o', 'não'],
                ['V?deo', 'Vídeo'],
                ['v?deo', 'vídeo'],
                ['Respons?vel', 'Responsável'],
                ['respons?vel', 'responsável'],
                ['c?mera', 'câmera'],
                ['permiss?es', 'permissões'],
                ['for?ado', 'forçado'],
                ['a??es', 'ações'],
                ['op??es', 'opções'],
                ['Pr?xima', 'Próxima'],
                ['Visualiza??o', 'Visualização'],
                ['r?pidas', 'rápidas'],
                ['Or?amento', 'Orçamento'],
                ['â€¢', '|'],
            ]) + '\n\n    const swal = (options) => {'
        );
    }

    if (filePath === 'public/assets/js/os-list-filters.js') {
        return text.replace(
            /    const (?:WINDOWS_1252_PAIRS = \[|PTBR_MOJIBAKE_REPLACEMENTS = \[)[\s\S]*?\n    function normalizeRenderedText\(root\) \{/m,
            buildBrowserNormalizerBlock('normalizePtBrText', [
                ['N?o', 'Não'],
                ['n?o', 'não'],
                ['Transfer?ncia', 'Transferência'],
                ['?ltimos', 'Últimos'],
                ['Discrep?ncia', 'Discrepância'],
                ['Petr?leo', 'Petróleo'],
                ['Lil?s', 'Lilás'],
                ['Acess?rios', 'Acessórios'],
            ]) + '\n\n    function normalizeRenderedText(root) {'
        );
    }

    return text;
}

function normalizeFile(filePath) {
    const fileBuffer = fs.readFileSync(filePath);
    const originalText = fileBuffer.toString('utf8');
    const { lineBuffers, lineEndings } = splitBufferWithLineEndings(fileBuffer);

    let changedLineCount = 0;
    let badCountAfter = 0;
    let questionInsideWordCountAfter = 0;
    let normalizedText = '';

    for (let index = 0; index < lineBuffers.length; index++) {
        const chosenLine = chooseBestLine(lineBuffers[index]);
        const originalLine = lineBuffers[index].toString('utf8');

        if (chosenLine.text !== originalLine) {
            changedLineCount++;
        }

        badCountAfter += chosenLine.badCount;
        questionInsideWordCountAfter += chosenLine.questionInsideWordCount;
        normalizedText += chosenLine.text + lineEndings[index];
    }

    normalizedText = postProcess(filePath, normalizedText);

    return {
        filePath,
        changed: normalizedText !== originalText,
        changedLineCount,
        badCountAfter,
        questionInsideWordCountAfter,
        normalizedText,
    };
}

function writeFileUtf8(filePath, text) {
    fs.writeFileSync(filePath, text, { encoding: 'utf8' });
}

function main() {
    const shouldWrite = process.argv.includes('--write');
    const candidates = getTrackedFiles().filter(isCandidateFile);
    const changedFiles = [];

    for (const filePath of candidates) {
        const result = normalizeFile(filePath);
        if (!result.changed) {
            continue;
        }

        changedFiles.push(result);
        if (shouldWrite) {
            writeFileUtf8(filePath, result.normalizedText);
        }
    }

    console.log(`files_analyzed=${candidates.length}`);
    console.log(`files_changed=${changedFiles.length}`);

    changedFiles
        .sort((left, right) => right.changedLineCount - left.changedLineCount || left.filePath.localeCompare(right.filePath))
        .forEach((result) => {
            console.log(
                `${result.filePath}|changed_lines=${result.changedLineCount}|bad_after=${result.badCountAfter}|qword_after=${result.questionInsideWordCountAfter}`
            );
        });
}

main();
