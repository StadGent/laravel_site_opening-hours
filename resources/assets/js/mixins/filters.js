window.Vue.filter('date', date);
window.Vue.filter('simpleDate', simpleDate);

export const MONTHS = 'jan,feb,maart,apr,mei,juni,juli,aug,sept,okt,nov,dec'.split(',')

function simpleDate(d) {
  if (!d) {
    return d
  }
  if (typeof d === 'string') {
    d = d.replace(' ', 'T')
  }
  d = new Date(d);
  if (!d) {
    return 'invalid'
  }
  return d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear()
}

function date(d) {
  if (!d) {
    return 'nooit'
  }
  if (typeof d === 'string') {
    d = d.replace(' ', 'T')
  }
  d = new Date(d)
  if (!d) {
    console.error('Report invalid updated_at')
    return 'invalid'
  }
  var diff = new Date().valueOf() - d
  if (diff < 0 || diff > 1000 * 60 * 60 * 24) {
    return d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear()
  }
  if (diff > 1000 * 60 * 60) {
    return Math.round(diff / 36e5) + ' uur geleden'
  }
  if (diff > 1000 * 60) {
    return Math.round(diff / 6e4) + ' min. geleden'
  }
  return Math.round(diff / 1000) + ' s. geleden'
}

function pad (t) {
  return t < 10 ? '0' + t : t
}
