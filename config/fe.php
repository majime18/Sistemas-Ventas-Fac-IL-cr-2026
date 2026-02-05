<?php
function fe_endpoints(string $ambiente): array {
  if($ambiente==='PROD'){
    return [
      'token_url' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token',
      'client_id' => 'api-prod',
      'recepcion_url' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/recepcion',
      'estado_url' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/recepcion/'
    ];
  }
  return [
    'token_url' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token',
    'client_id' => 'api-stag',
    'recepcion_url' => 'https://api.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/recepcion',
    'estado_url' => 'https://api.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/recepcion/'
  ];
}
