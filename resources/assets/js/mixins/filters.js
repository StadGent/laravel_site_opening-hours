window.Vue.filter('date', date)

const MONTHS = 'jan,feb,maart,apr,mei,juni,juli,aug,sept,okt,nov,dec'.split(',')

function date(d) {
  if (!d) {
    return 'nooit'
  }
  d = new Date(d)
  if (!d) {
    console.error('Report invalid updated_at')
    return 'invalid'
  }
  d = new Date(+d + 6e4 * d.getTimezoneOffset())
  var diff = new Date() - d
  if (diff > 1000 * 60 * 60 * 24) {
    return d.getHours() + ':' + d.getSeconds() + ' ' + d.getDate() + ' ' + MONTHS[d.getMonth()]
  }
  if (diff > 1000 * 60 * 60) {
    console.log((diff / 36e5))
    return Math.round(diff / 36e5) + ' uur geleden'
  }
  if (diff > 1000 * 60) {
    return Math.round(diff / 6e4) + ' min. geleden'
  }
  return Math.round(diff / 1000) + ' s. geleden'
}
