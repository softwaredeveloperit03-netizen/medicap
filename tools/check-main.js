const http = require('http');

function get(url) {
  return new Promise((resolve, reject) => {
    http.get(url, (res) => {
      let body = '';
      res.on('data', (c) => (body += c));
      res.on('end', () => resolve({ status: res.statusCode, body }));
    }).on('error', reject);
  });
}

(async () => {
  const html = await get('http://192.168.1.114:2222/');
  const scripts = [...html.body.matchAll(/<script[^>]+src="([^"]+)"/g)].map((m) => m[1]);
  console.log('scripts:', scripts.join(', '));
  const mainName = scripts.find((s) => s.includes('main')) || 'main.js';
  const main = await get(`http://192.168.1.114:2222/${mainName}`);
  console.log('main status:', main.status, 'len:', main.body.length);

  for (const name of [
    'AppComponent_Factory',
    'LoginComponent_Factory',
    'LanguageService_Factory',
    'DataAccessService_Factory',
    'ɵɵinject',
    'invalidFactoryDep',
  ]) {
    console.log(name + ':', main.body.includes(name));
  }

  const markers = [
    'AppComponent = class',
    'LoginComponent = class',
    'LanguageService = class',
    'DataAccessService = class',
  ];
  for (const marker of markers) {
    const idx = main.body.indexOf(marker);
    if (idx === -1) {
      console.log('\nNOT FOUND:', marker);
      continue;
    }
    console.log('\n---', marker, '---');
    console.log(main.body.slice(idx, idx + 900));
  }
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
