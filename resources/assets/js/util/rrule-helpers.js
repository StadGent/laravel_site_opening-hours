// RRule can be expensive to calculate
const rruleCache = {}
export function rruleToStarts (rule) {
  if (rule.indexOf('undefined') > 0 || rule.indexOf('BYMINUTE') > 0 || rule.indexOf('BYSECOND') > 0) {
    return console.error('Bad rules!', rule) || []
  }
  const cache = rruleCache[rule]
  return cache /* || console.debug('miss', rule) */ || (rruleCache[rule] = rrulestr(rule).all())
}

// Add dtstart and until
export function keepRuleWithin (e) {
  let rule = e.rrule
  if (rule.indexOf('DTSTART=') === -1) {
    rule += ';DTSTART=' + toIcsDatetime(e.start_date)
  }
  if (rule.indexOf('UNTIL=') === -1) {
    rule += ';UNTIL=' + toIcsDatetime(e.until)
  }
  return rule
}


// Human friendly duration
function toDuration (n) {
  n /= 1000
  if (n < 100) {
    return n + ' seconds'
  }
  n /= 60
  if (n < 100) {
    return n + ' minutes'
  }
  n /= 60
  if (n < 100) {
    return n + ' hours'
  }
  n /= 24
  if (n < 100) {
    return n + ' days'
  }
}

// Cast strings, ints and dates to date only
export function toDate (str) {
  if (str && str.toJSON) {
    return toDate(str.toJSON())
  }
  if (typeof str === 'number') {
    return new Date(str)
  }
  if (typeof str !== 'string') {
    console.warn('Unexpected type in toDate', typeof str)
    return str
  }
  return new Date(str.slice(0, 4), parseInt(str.slice(5, 7), 10) - 1, parseInt(str.slice(8, 10), 10))
}

// Cast strings, ints and dates to datetime
export function toDatetime (str) {
  if (str && str.toJSON) {
    return str
  }
  if (typeof str === 'number') {
    return new Date(str)
  }
  if (typeof str !== 'string') {
    console.warn('Unexpected type in toDatetime', typeof str)
    return str
  }
  return new Date(Date.parse(str))
}

// Transform a date to ICS format
export function toIcsDate (str) {
  let obj = toDate(str)
  if (!obj) {
    obj = new Date()
  }
  str = obj.toJSON()
  return str.slice(0, 4) + str.slice(5, 7) + str.slice(8, 10) + 'T020000Z'
}

// Transform a datetime to ICS format
export function toIcsDatetime (str) {
  let obj = toDate(str)
  if (!obj) {
    obj = new Date()
  }
  str = obj.toJSON()
  return str.slice(0, 4) + str.slice(5, 7) + str.slice(8, 13) + str.slice(14, 16) + str.slice(17, 19) + 'Z'
}
