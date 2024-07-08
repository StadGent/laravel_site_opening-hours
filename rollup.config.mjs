import vue2 from 'rollup-plugin-vue2'
import buble from 'rollup-plugin-buble'
import nodeResolve from '@rollup/plugin-node-resolve'
import commonjs from '@rollup/plugin-commonjs';
import css from 'rollup-plugin-css-only'

export default {
  input: 'resources/assets/js/app.js',
  output: {
    file: 'public/js/bundle.js',
    format: 'es',
    sourcemap: true
  },
  watch: {
    chokidar: false,
    include: 'resources/assets/*/**'
  },
  plugins: [
    css(),
    vue2(),
    buble(),
    nodeResolve({
      browser: true,
    }),
    commonjs({
      include: 'node_modules/**'
    })
  ]
}
