import vue2 from 'rollup-plugin-vue2'
import buble from 'rollup-plugin-buble'
import livereload from 'rollup-plugin-livereload'
// import nodeResolve from 'rollup-plugin-node-resolve'
// import commonjs from 'rollup-plugin-commonjs'

export default {
  entry: 'resources/assets/js/app.js',
  dest: 'public/js/bundle.js',
  sourceMap: true,
  plugins: [
    vue2(),
    buble(),
    process.argv.indexOf('--live') > 1 && livereload(),
    // nodeResolve(),
    // commonjs({
    //   include: 'node_modules/**'
    // })
  ]
}
