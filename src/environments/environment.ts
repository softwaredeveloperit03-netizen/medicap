// Local `ng serve` uses proxy.conf.json → live PHP at aurenyxgmp.com (same DB as production).
// Absolute apiServerUrl is kept for tools/health; DataAccessService uses /php/... proxy on localhost.
export const environment = {
  production: false,
  defaultApiMode: 'server' as 'local' | 'server',
  apiLocalPath: '/Medicap/php/phpdevlop/phpmedicap/',
  apiServerUrl: 'https://aurenyxgmp.com/php/phpdevlop/phpmedicap/',
  apiDevProxyPath: '/php/phpdevlop/phpmedicap/',
  adminApiUrl: 'https://aurenyxgmp.com/admin/api/clients/client_data_without_token.php',
  deployEnv: 'live' as 'live' | 'trial' | 'dev',
  clientCode: 'GMP22052',
  defaultPlantId: '1126',
};
