// Sorting
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
