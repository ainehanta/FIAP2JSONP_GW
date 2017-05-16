
const rollup      = require('rollup');
const buble       = require('rollup-plugin-buble');
const nodeResolve = require('rollup-plugin-node-resolve');
const commonjs    = require('rollup-plugin-commonjs');
const uglify      = require('rollup-plugin-uglify');
const minify      = require('uglify-js-harmony').minify;

let plugins = [
  buble({ exclude: 'node_modules/**' }),
  nodeResolve({ browser: true, jsnext: true }),
  commonjs(),
];

if (process.env.NODE_ENV === 'production') {
  plugins.push(uglify({}, minify));
}

let cache;

process.on('unhandledRejection', console.dir);

rollup.rollup({
  entry: 'src/fiap.js',
  cache: cache,
  plugins: plugins,
}).then(bundle => {
  bundle.write({
    format: 'iife',
    dest:   'dist/fiap.js',
    moduleName: 'FiapClient'
  });
  bundle.write({
    format: 'cjs',
    dest:   'dist/fiap.cjs.js'
  });
  bundle.write({
    format: 'es',
    dest:   'dist/fiap.es.js'
  });
});
