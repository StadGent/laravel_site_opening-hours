// import stringToHM from 'stringToHM'

var stringToHM = require('./stringToHM')

const cases = {
  0: '00:00',
  1: '01:00',
  2: '02:00',
  9: '09:00',
  10: '10:00',
  11: '11:00',
  12: '12:00',
  13: '13:00',
  23: '23:00',
  24: '00:00',
  25: '02:50',
  26: '02:06',
  29: '02:09',
  30: '03:00',
  31: '03:10',
  90: '09:00',
  91: '09:10',
  99: '09:09',
  100: '10:00',
  101: '10:10',
  110: '11:00',
  111: '11:10',
  235: '23:50',
  236: '23:06',
  240: '00:00',
  245: '00:50',
  246: '00:06',
  250: '02:50',
  260: '02:06',
  300: '03:00',
  360: '03:06',
  1000: '10:00',
  2359: '23:59',
  2360: '23:06',
  2400: '00:00',
  2410: '00:10',
  2460: '00:06',
  2510: '02:51',
  '01': '01:00',
  '001': '00:10',
  '0001': '00:01',
  ':': '00:00',
  '1:': '01:00',
  ':1': '00:10',
  ':6': '00:06',
  '0:0': '00:00',
  ':01': '00:01',
  '10:01': '10:01',
  '22:22': '22:22',
  '-': '00:00',
  '-1': '00:10',
}

const error = [], success = []

for (const input in cases) {
  const output = stringToHM(input)
  if (output === cases[input]) {
    success.push(input)
  } else {
    error.push({ input, output })
    console.log(input + '\t' + output + '\t' + cases[input])
  }
}
console.log('fail', error.length)
console.log('success', success.length)
console.log('total', Object.keys(cases).length)
