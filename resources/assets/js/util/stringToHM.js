export function stringToHM (n) {
  let hour, minute, h1, h2, h, m, slice12, slice13, slice23, slice24
  const str = n.toString().replace(/[^0-9]+/, ':')
  const sep = str.indexOf(':')
  if (sep > -1) {
    return stringToHM(leftpad(str.slice(0, sep)) + str.slice(sep + 1, sep + 3))
  }
  h1 = parseInt(str.slice(0, 1))
  h2 = parseInt(str.slice(0, 2))
  slice12 = (str.slice(1, 2))
  slice13 = (str.slice(1, 3))
  slice23 = (str.slice(2, 3))
  slice24 = (str.slice(2, 4))
  if (h2 === 24) {
    h2 = 0
  }
  if (h2 > 23 || h1 > 2) {
    if (slice13 < 6 && str.length < 3) {
      return hm(h1, 10 * slice13)
    }
    if (slice13 < 60) {
      return hm(h1, slice13)
    }
    return hm(h1, slice12)
  }
  if (slice24 < 6 && str.length < 4) {
    return hm(h2, 10 * slice24)
  }
  if (slice24 < 60) {
    return hm(h2, slice24)
  }
  return hm(h2, slice23)
}

function leftpad (n) {
  return n ? (n.toString().length === 1 ? '0' : '') + n : '00'
}

function hm (h, m) {
  return leftpad(h || 0) + ':' + leftpad(m || 0)
}
