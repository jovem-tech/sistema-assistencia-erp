<?php
$branding = $branding ?? [];
$empresaNome = trim((string) ($branding['empresa_nome'] ?? ''));
$empresaCnpj = trim((string) ($branding['empresa_cnpj'] ?? ''));
$empresaTelefone = trim((string) ($branding['empresa_telefone'] ?? ''));
$empresaEmail = trim((string) ($branding['empresa_email'] ?? ''));
$empresaEndereco = trim((string) ($branding['empresa_endereco'] ?? ''));
$headerLogo = trim((string) ($branding['header_logo_data_uri'] ?? ''));
$watermarkLogo = trim((string) ($branding['watermark_logo_data_uri'] ?? ''));
$tituloDocumento = trim((string) ($tituloDocumento ?? 'Documento'));
$documentoReferencia = trim((string) ($documentoReferencia ?? ''));

$metaParts = array_values(array_filter([
    $empresaCnpj !== '' ? 'CNPJ: ' . $empresaCnpj : '',
    $empresaTelefone !== '' ? 'Telefone: ' . $empresaTelefone : '',
    $empresaEmail !== '' ? 'Email: ' . $empresaEmail : '',
]));
?>
<?php if ($watermarkLogo !== ''): ?>
    <div class="pdf-watermark">
        <img src="<?= esc($watermarkLogo) ?>" alt="Marca d'agua">
    </div>
<?php endif; ?>

<div class="pdf-branding">
    <table class="pdf-branding-main">
        <tr>
            <?php if ($headerLogo !== ''): ?>
                <td class="pdf-branding-logo-cell">
                    <img src="<?= esc($headerLogo) ?>" alt="Logo" class="pdf-branding-logo">
                </td>
            <?php endif; ?>
            <td class="pdf-branding-copy-cell">
                <div class="pdf-branding-company"><?= esc($empresaNome !== '' ? $empresaNome : 'Assistencia Tecnica') ?></div>
                <div class="pdf-branding-title"><?= esc($tituloDocumento) ?></div>
                <?php if ($documentoReferencia !== ''): ?>
                    <div class="pdf-branding-reference">Referencia: <?= esc($documentoReferencia) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <?php if (!empty($metaParts) || $empresaEndereco !== ''): ?>
        <div class="pdf-branding-meta">
            <?php if (!empty($metaParts)): ?>
                <div><?= esc(implode(' | ', $metaParts)) ?></div>
            <?php endif; ?>
            <?php if ($empresaEndereco !== ''): ?>
                <div><?= esc($empresaEndereco) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
