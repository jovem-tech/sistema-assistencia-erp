<?php
$photoGroups = array_values((array) ($photoGroups ?? []));
?>
<?php if ($photoGroups !== []): ?>
<div class="section-title">Fotos anexadas ao comprovante</div>
<?php foreach ($photoGroups as $group): ?>
    <?php $groupPhotos = array_values((array) ($group['photos'] ?? [])); ?>
    <?php if ($groupPhotos === []): ?>
        <?php continue; ?>
    <?php endif; ?>
    <div class="photo-annex-group">
        <div class="photo-annex-group-title"><?= esc((string) ($group['label'] ?? 'Fotos')) ?></div>
        <table class="photo-annex-table">
            <?php foreach (array_chunk($groupPhotos, 2) as $rowPhotos): ?>
                <tr>
                    <?php foreach ($rowPhotos as $photo): ?>
                        <td style="width: 50%;">
                            <div class="photo-annex-card">
                                <img src="<?= esc((string) ($photo['url'] ?? '')) ?>" alt="<?= esc((string) ($photo['label'] ?? 'Foto da OS')) ?>">
                                <div class="photo-annex-label"><?= esc((string) ($photo['label'] ?? 'Foto da OS')) ?></div>
                            </div>
                        </td>
                    <?php endforeach; ?>
                    <?php while (count($rowPhotos) < 2): $rowPhotos[] = null; ?>
                        <td style="width: 50%;"></td>
                    <?php endwhile; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endforeach; ?>
<?php endif; ?>
