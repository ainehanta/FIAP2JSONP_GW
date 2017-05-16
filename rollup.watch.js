
const rollup      = require('rollup');
const watch       = require('rollup-watch');
const buble       = require('rollup-plugin-buble');
const nodeResolve = require('rollup-plugin-node-resolve');
const commonjs    = require('rollup-plugin-commonjs');
const livereload  = require('rollup-plugin-livereload');
const serve       = require('rollup-plugin-serve');
const uglify      = require('rollup-plugin-uglify');
const minify      = require('uglify-js-harmony').minify;

let buildPlugins = [
  buble({ exclude: 'node_modules/**' }),
  nodeResolve({ browser: true, jsnext: true }),
  commonjs(),
];

let config = function(format, dest) {
  return {
    entry:   'src/fiap.js',
    plugins: buildPlugins,
    format:  format,
    dest:    dest,
    moduleName: 'FiapClient'
  };
};

if (process.env.NODE_ENV === 'production') {
  buildPlugins.push(uglify({}, minify));
}

if (process.env.SERVE === 'true') {
  buildPlugins.push(livereload());
  buildPlugins.push(serve({
    open: true,
    contentBase: ['./examples', './dist']
  }));
}

const stderr = console.error.bind(console);

const eventHandler = (event, filename) => {
  switch (event.code) {
    case 'STARTING':
      stderr('checking rollup-watch version...');
      break;
    case 'BUILD_START':
      stderr(`bundling ${filename}...`);
      break;
    case 'BUILD_END':
      stderr(`${filename} bundled in ${event.duration}ms. Watching for changes...`);
      break;
    case 'ERROR':
      stderr(`error: ${event.error}`);
      break;
    default:
      stderr(`unknown event: ${event}`);
  }
}

let watcher = watch(rollup, config('iife', './dist/fiap.js'));
watcher.on('event', (event) => eventHandler(event, './dist/fiap.js'));
