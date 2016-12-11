
/* Localstorage helper */

function ls(key, value) {
  if (typeof key === 'undefined') {
    return window.localStorage
  }
  if (typeof value === 'undefined') {
    return window.localStorage[key] && JSON.parse(window.localStorage[key])
  }
  window.localStorage[key] = JSON.stringify(value)
}

function lsDefault(key, value) {
  if (!key || typeof value === 'undefined') {
    return console.warn('lsDefault: key & value expected')
  }
  if (!ls(key)) {
    ls(key, value)
  }
}

function inert(a) {
  return JSON.parse(JSON.stringify(a))
}
