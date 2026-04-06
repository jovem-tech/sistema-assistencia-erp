# Push Notifications

## Base tecnica

O app usa Web Push com subscriptions registradas no backend do ERP.

## Requisitos obrigatorios

- chaves VAPID configuradas;
- service worker ativo;
- permissao concedida pelo usuario;
- subscription salva na base;
- endpoint de envio funcional.

## Checklist de ativacao

1. validar HTTPS;
2. validar manifest e service worker;
3. validar chave publica no frontend;
4. validar chave privada no backend;
5. testar notificacao local;
6. testar evento real inbound.

## Suporte

Em falhas, registrar:

- navegador e versao;
- sistema operacional;
- tipo de bloqueio encontrado;
- status da subscription no backend.

