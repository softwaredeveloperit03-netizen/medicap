const path = require('path');
const { spawnSync } = require('child_process');

const root = path.join(__dirname, '..');
process.env.NODE_PATH = path.join(root, 'node_modules');
require('module').Module._initPaths();

const ngBin = path.join(root, 'node_modules', '@angular', 'cli', 'bin', 'ng');
const args = process.argv.slice(2);

const result = spawnSync(
  process.execPath,
  ['--max_old_space_size=16384', ngBin, ...args],
  { stdio: 'inherit', cwd: root, env: process.env, shell: false }
);

process.exit(typeof result.status === 'number' ? result.status : 1);
