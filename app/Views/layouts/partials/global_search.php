<?php
$searchIdSuffix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($searchIdSuffix ?? 'navbar'));
$searchWrapperClass = trim('navbar-search-wrapper ' . (string) ($searchWrapperClass ?? ''));
$showFilterLabel = (bool) ($showFilterLabel ?? false);
$filterLabelClass = $showFilterLabel ? 'filter-label' : 'filter-label d-none d-lg-inline';
?>
<div class="<?= esc($searchWrapperClass) ?>" data-global-search-instance="<?= esc($searchIdSuffix) ?>">
    <div class="search-input-group">
        <div class="dropdown h-100">
            <button class="btn btn-link search-filter-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                <span class="<?= esc($filterLabelClass) ?>">Tudo</span>
                <i class="bi bi-funnel d-lg-none"></i>
            </button>
            <ul class="dropdown-menu search-filter-menu p-2">
                <li>
                    <a class="dropdown-item filter-all active" href="javascript:void(0)" data-filter="all">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="all" id="filter-all-<?= esc($searchIdSuffix) ?>" checked>
                            <label class="form-check-label w-100 cursor-pointer" for="filter-all-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-grid-fill me-2"></i>Tudo
                            </label>
                        </div>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-filter="os">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="os" id="filter-os-<?= esc($searchIdSuffix) ?>">
                            <label class="form-check-label w-100 cursor-pointer" for="filter-os-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-file-earmark-text me-2"></i>OS
                            </label>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-filter="os_legado">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="os_legado" id="filter-os-legado-<?= esc($searchIdSuffix) ?>">
                            <label class="form-check-label w-100 cursor-pointer" for="filter-os-legado-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-clock-history me-2"></i>OS Legado (n&uacute;mero antigo)
                            </label>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-filter="clientes">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="clientes" id="filter-clientes-<?= esc($searchIdSuffix) ?>">
                            <label class="form-check-label w-100 cursor-pointer" for="filter-clientes-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-people me-2"></i>Clientes
                            </label>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-filter="whatsapp">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="whatsapp" id="filter-whatsapp-<?= esc($searchIdSuffix) ?>">
                            <label class="form-check-label w-100 cursor-pointer" for="filter-whatsapp-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-whatsapp me-2"></i>WhatsApp
                            </label>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-filter="equipamentos">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="equipamentos" id="filter-equipamentos-<?= esc($searchIdSuffix) ?>">
                            <label class="form-check-label w-100 cursor-pointer" for="filter-equipamentos-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-laptop me-2"></i>Equipamentos
                            </label>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-filter="servicos">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="servicos" id="filter-servicos-<?= esc($searchIdSuffix) ?>">
                            <label class="form-check-label w-100 cursor-pointer" for="filter-servicos-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-tools me-2"></i>Servi&ccedil;os
                            </label>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-filter="pecas">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="pecas" id="filter-pecas-<?= esc($searchIdSuffix) ?>">
                            <label class="form-check-label w-100 cursor-pointer" for="filter-pecas-<?= esc($searchIdSuffix) ?>">
                                <i class="bi bi-box-seam me-2"></i>Pe&ccedil;as
                            </label>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
        <i class="bi bi-search search-icon ms-2"></i>
        <input type="text" class="search-input" placeholder="O que voc&ecirc; procura? (inclui OS legado)" autocomplete="off">
        <div class="search-results-container shadow-lg">
            <div class="search-loading-state d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Buscando...</span>
                </div>
            </div>
            <div class="search-empty-state d-none">
                <i class="bi bi-search"></i>
                <p>Nenhum resultado encontrado.</p>
            </div>
            <div class="search-results-list"></div>
        </div>
    </div>
</div>
