const today = new Date().toJSON().slice(0, 10)
const DAY_IN_MS = 24 * 60 * 60 * 1000

/** Service functions **/

export function hasChannels(s) {
  return s && s.channels || []
}
// Calendars in the first OH of the channels of a service
function countCals(s) {
  return hasChannels(s).map(ch => hasCal(ch).length).reduce((a, b) => a + b, 0)
}

/** Channel functions **/

export function hasOh(ch) {
  return ch && ch.openinghours || []
}

export function hasCal(ch) {
  return ch && ch.openinghours && ch.openinghours[0] && ch.openinghours[0].calendars || []
}

// Get active OH of a channel
export function hasActiveOh(ch) {
  return ch && ch.openinghours && ch.openinghours.filter(x => x.active) || []
}

// Get active expiring OH of a channel
// export function hasExpiringOh(ch) {
//   return ch && ch.openinghours && (ch.openinghours.find(x => x.active) || {}).calendars || []
// }

export function toChannelStatus(ch, includeLabel) {
  const oh = hasActiveOh(ch)
  includeLabel = includeLabel ? ch.label : ''

  let end_date = expiresOn(oh)
  if (!end_date || Date.parse(end_date) < Date.now()) {
    return 'Kanaal ' + includeLabel + ' is verlopen'
  }
  if (end_date === -1) {
    return 'Kanaal ' + includeLabel + ' verloopt nooit'
  }
  return 'Kanaal ' + includeLabel + ' verloopt op ' + end_date
}

// Returns true if this channel expires within 90 days
export function toChannelAlert(ch) {
  const oh = hasActiveOh(ch)
  let end_date = expiresOn(oh)
  if (end_date) {
    return Date.parse(end_date) < Date.now() + 90 * DAY_IN_MS
  }
}

/** OH functions **/

export function isInUseOn(oh, date) {
  return (oh.start_date ? oh.start_date < date : true) && (oh.end_date ? oh.end_date > date : true)
}

// Get expiry date of array of oh
export function expiresOn(oh) {
  let end_date = today
  let count = oh.length

  //
  for (var i = 0; i < count; i++) {
    let nextIndex = oh.findIndex(x => isInUseOn(x, end_date))
    let nextOh = oh.splice(nextIndex, 1).pop()
    if (!nextOh) {
      break
    } else if (!nextOh.end_date) {
      return -1
    } else {
      end_date = nextDateString(nextOh.end_date || end_date)
    }
  }
  return end_date === today ? null : end_date
}

/** Date functions **/

export function nextDateString (dateString) {
  return new Date(Date.parse(dateString) + DAY_IN_MS).toJSON().slice(0, 10)
}
export function toTime(d) {
  if (!d) {
    return '00:00'
  }
  if (d.getHours) {
    return toTime(d.toJSON())
  }
  return d.slice(11, 16)
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

// Create a date object 1 day after the param
export function dateAfter (date, ms) {
  return new Date(date.valueOf() + (ms || DAY_IN_MS))
}

/** Sorting **/
function compareArray(a, b, c) {
  return a[c] && b[c] ? a[c].length - b[c].length : 0
}

const sortMapping = {
  services(a, b) {
    return b.services.length - a.services.length
  },
  '-services' (a, b) {
    return a.services.length - b.services.length
  },
  active(a, b) {
    return compareArray(a, b, 'activeUsers')
  },
  '-active' (a, b) {
    return compareArray(b, a, 'activeUsers')
  },
  ghosts(a, b) {
    return compareArray(a, b, 'ghostUsers')
  },
  '-ghosts' (a, b) {
    return compareArray(b, a, 'ghostUsers')
  },
  status(a, b) {
    return compareArray(a, b, 'status')
  },
  '-status' (a, b) {
    return compareArray(b, a, 'status')
  }
}

export function orderBy(order) {
  if (sortMapping[order]) {
    return sortMapping[order]
  }
  if (order) {
    if (order.startsWith('-')) {
      order = order.slice(1)
      return function(a, b) {
        return a[order] < b[order] }
    }
    return (a, b) => a[order] > b[order]
  }

  // No sorting
  return () => 0
}

// Event hub
export const Hub = new Vue()


// Extended version of php empty()
function empty(a) {
  if (!a || typeof a !== 'object') {
    return !a
  }
  for (var key in a) {
    if (!key.startsWith('@') && !empty(a[key])) {
      return false
    }
  }
  if (Object.keys(a).length === 1 && a['@id']) {
    return false
  }
  return true
}

// Removes empty properties from an object
export function cleanEmpty(x) {
  for (var key in x) {
    if (empty(x[key])) {
      delete x[key]
    } else if (Array.isArray(x[key])) {
      x[key] = x[key].map(cleanEmpty)
    } else {
      for (var j in x[key]) {
        if (empty(x[key][j])) {
          delete x[key][j]
        }
      }
    }
  }
  return x
}

// HTTP

export function fetchError (response) {
  if (response && response.body && response.body.message) {
    alert(response.body.message)
  }
  console.warn(response)
}

// Returns a function, that, when invoked, will only be triggered at most once
// during a given window of time. Normally, the throttled function will run
// as much as it can, without ever going more than once per `wait` duration;
// but if you'd like to disable the execution on the leading edge, pass
// `{leading: false}`. To disable execution on the trailing edge, ditto.
export function _throttle(func, wait, options) {
  var context, args, result;
  var timeout = null;
  var previous = 0;
  if (!options) options = {};
  var later = function() {
    previous = options.leading === false ? 0 : Date.now();
    timeout = null;
    result = func.apply(context, args);
    if (!timeout) context = args = null;
  };
  return function() {
    var now = Date.now();
    if (!previous && options.leading === false) previous = now;
    var remaining = wait - (now - previous);
    context = this;
    args = arguments;
    if (remaining <= 0 || remaining > wait) {
      if (timeout) {
        clearTimeout(timeout);
        timeout = null;
      }
      previous = now;
      result = func.apply(context, args);
      if (!timeout) context = args = null;
    } else if (!timeout && options.trailing !== false) {
      timeout = setTimeout(later, remaining);
    }
    return result;
  };
};

/* Chunk loader */
const fetched = {}
const loaded = {}

export function loadScript (lib, cb) {
  if (window[lib]) {
    return typeof cb === 'function' ? cb() : window[lib]
  }

  // Fetch the script
  if (!fetched[lib]) {
    fetched[lib] = true
    var first, s
    s = document.createElement('script')

    // Callback after script was loaded
    s.onreadystatechange = s.onload = function() {
      if (!loaded[lib] && cb) {
        cb()
      }
      loaded[lib] = true
    }
    s.src = '/js/chunks/' + lib + '.js'
    s.type = 'text/javascript'
    s.async = true
    first = document.getElementsByTagName('script')[0]
    first.parentNode.insertBefore(s, first)
    console.log('Lazy loading', lib)
  } else if (loaded[lib]) {
    cb()
  }
}
