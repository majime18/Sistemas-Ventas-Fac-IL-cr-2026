FAC-IL-CR - Facturación Electrónica REAL (SANDBOX)

Este paquete agrega:
- fe_config.php (pantalla para guardar credenciales por empresa)
- config/fe.php (endpoints SANDBOX/PROD)
- fe_lib.php (token, firma, envío, consulta)
- facturacion_enviar_real.php (envío real a Hacienda, reemplaza el simulado)
- facturacion_estado.php (consulta estado por clave)

DEPENDENCIA (root):
  composer require edwinjuarezpe/cr-mh-sdk

SQL (si hace falta):
  CREATE TABLE IF NOT EXISTS fe_config (... ver instrucciones en este archivo) ;
  ALTER TABLE fe_documentos ADD xml_firmado, token_usado, ambiente;

NOTA: guarde el .p12 fuera del webroot y registre la ruta absoluta.
