console.log('utm injector, here');
window.addEventListener('load', () => {
  var utm_types = ['source','medium','campaign','content'];

  var pos = window.location.href.indexOf('?')
  var query_str = (pos) ? window.location.href.slice(pos+1) : ''
  var query_arr = query_str.split('&')
  var locale_val = ''
  query_arr.forEach( (e) => {
    var par, input
    pair = e.split('=')
    if (pair[0].indexOf('utm_')===0) {
      utm_param = pair[0].replace('utm_','')
      if (utm_types.indexOf(utm_param) > -1)  {
        par = document.querySelector('.utm_' + utm_param + '_container')
        input = (par) ? par.querySelector('input') : {value:''}
        locale_val = pair[1]
        //console.log(locale_val)
        input.value = locale_val
      }
    }
  })
})
