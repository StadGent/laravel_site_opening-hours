const today = new Date().toJSON().slice(0, 10)

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

export function toChannelStatus(ch) {
  const oh = hasActiveOh(ch)
  let end_date = expiresOn(oh)
  return end_date
}

/** OH functions **/

function isInUseOn(oh, date) {
  console.log(oh.start_date, oh.end_date, date, (oh.start_date ? oh.start_date < date : true) && (oh.end_date ? oh.end_date > date : true))
  return (oh.start_date ? oh.start_date < date : true) && (oh.end_date ? oh.end_date > date : true)
}

// Get expiry date of array of oh
function expiresOn(oh) {
  let end_date = today
  let count = oh.length

  //
  for (var i = 0; i < count; i++) {
    let nextIndex = oh.findIndex(x => isInUseOn(x, end_date))
    let nextOh = oh.splice(nextIndex, 1).pop()
    if (!nextOh) {
      break
    } else if (!nextOh.end_date) {
      return 'infinite'
    } else {
      end_date = nextDateString(nextOh.end_date || end_date)
    }
  }
  return end_date === today ? 'Verlopen' : end_date
}

/** Date functions **/

function nextDateString (dateString) {
  return new Date(Date.parse(dateString) + 36e5 * 24).toJSON().slice(0, 10)
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
