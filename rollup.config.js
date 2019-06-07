import vue2 from 'rollup-plugin-vue2'
import buble from 'rollup-plugin-buble'
// import nodeResolve from 'rollup-plugin-node-resolve'
// import commonjs from 'rollup-plugin-commonjs'

export default {
  input: 'resources/assets/js/app.js',
  output: {
      file: 'public/js/bundle.js',
      format: 'es',
      sourcemap: true
  },
  plugins: [
    vue2(),
    buble(),
    // nodeResolve(),
    // commonjs({
    //   include: 'node_modules/**'
    // })
  ]
}
