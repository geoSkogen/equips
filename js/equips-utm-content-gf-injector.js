console.log('utm injector, here');
var par = document.querySelector('.utm_content_container')
var input = (par) ? par.querySelector('input') : {value:''}
var pos = window.location.href.indexOf('?')
var query_str = (pos) ? window.location.href.slice(pos+1) : ''
var query_arr = query_str.split('&')
var locale_val = ''
query_arr.forEach( (e) => {
  pair = e.split('=')
  if (pair[0].indexOf('utm_')===0) {
    utm_param = pair[0].replace('utm_','')
    if (utm_param === 'content') {
      locale_val = pair[1]
      console.log(locale_val)
      input.value = locale_val
    }
  }
})
