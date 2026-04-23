<?php

$directory = __DIR__ . '/../app/';

$replacements = [
    'Servio' => 'Serviço',
    'Servios' => 'Serviços',
    'Pea' => 'Peça',
    'Peas' => 'Peças',
    'Gesto' => 'Gestão',
    'Configuraes' => 'Configurações',
    'Configuraes' => 'Configurações',
    'Usurio' => 'Usuário',
    'Usurios' => 'Usuários',
    'Mtricas' => 'Métricas',
    'Avanado' => 'Avançado',
    'Histrico' => 'Histórico',
    'Documentao' => 'Documentação',
    'Manuteno' => 'Manutenção',
    'Relatrio' => 'Relatório',
    'Relatrios' => 'Relatórios',
    'Endereo' => 'Endereço',
    'Informaes' => 'Informações',
    'informaes' => 'informações',
    'Aes' => 'Ações',
    'Ao' => 'Ação',
    'Operaes' => 'Operações',
    'Padro' => 'Padrão',
    'Opes' => 'Opções',
    'Opes' => 'Opções',
    'Adicionar Pea' => 'Adicionar Peça',
    'Equipamento/Pea' => 'Equipamento/Peça',
    'Conexo' => 'Conexão',
    'Integrao' => 'Integração',
    'Integraes' => 'Integrações',
    'Ateno' => 'Atenção',
    'Observaes' => 'Observações',
    'Atualizao' => 'Atualização',
    'Descrio' => 'Descrição',
    'Voc' => 'Você',
    'voc' => 'você',
    'Fsico' => 'Físico',
    'fsico' => 'físico',
    'Aps' => 'Após',
    'Atrs' => 'Atrás',
    'No' => 'Não',
    'no' => 'não',
    'So' => 'São',
    'so' => 'são',
    'Vlido' => 'Válido',
    'vlido' => 'válido',
    'Incio' => 'Início',
    'Ms' => 'Mês',
    'ms' => 'mês',
    'Automtico' => 'Automático',
    'automtico' => 'automático',
    'Mdulo' => 'Módulo',
    'mdulo' => 'módulo',
    'Pginas' => 'Páginas',
    'pginas' => 'páginas',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
);

$count = 0;

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'php') continue;

    $path = $file->getRealPath();
    $content = file_get_contents($path);
    $original = $content;

    foreach ($replacements as $broken => $fixed) {
        $content = str_replace($broken, $fixed, $content);
    }

    if ($original !== $content) {
        file_put_contents($path, $content);
        echo "Fixed: " . $path . "\n";
        $count++;
    }
}

echo "Total files fixed: " . $count . "\n";
