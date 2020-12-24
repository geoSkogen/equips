window.addEventListener('load', () => {
  console.log('utm injector, here');
  var utm_types = ['source','medium','campaign','content'];

  var query_vars = ['gclid','msclkid']
  var media_vals = ['google','bing']

  var pos = window.location.href.indexOf('?')
  var query_str = (pos > 1) ? window.location.href.slice(pos+1) : ''
  var query_arr = (query_str) ? query_str.split('&') : []
  var locale_val = ''

  query_arr.forEach( (e) => {
    var par, input, utm_param
    var pair = e.split('=')
    if (pair[0].indexOf('utm_')===0) {
      utm_param = pair[0].replace('utm_','')
      if (utm_types.indexOf(utm_param) > -1)  {
        console.log(' found utm ' + utm_param)
        par = document.querySelector('.utm_' + utm_param + '_container')
        input = (par) ? par.querySelector('input') : {value:''}
        locale_val = pair[1]
        //console.log(locale_val)
        input.value = locale_val
      }
    } else if (query_vars.indexOf(pair[0])>-1) {
      console.log('found query var ' + pair[0])
      par = document.querySelector('.query_var_' + pair[0] + '_container')
      input = (par) ? par.querySelector('input') : {value:''}
      locale_val = media_vals[query_vars.indexOf(pair[0])]
      //console.log(locale_val)
      input.value = locale_val
    } else {
      console.log('not found query var ' + pair[0])
    }
  })

  query_vars.forEach( (query) => {
    var this_in = ( document.querySelector('.query_var_' + query + '_container') ) ?
      document.querySelector('.query_var_' + query + '_container').querySelector('input') : null
    if (this_in) {
      if (this_in.value && document.querySelector('.query_var_container')) {
        document.querySelector('.query_var_container').querySelector('input').value = this_in.value
      }

      if (this_in.value && document.querySelector('.utm_source_container')) {
        document.querySelector('.utm_source_container').querySelector('input').value = this_in.value
      }

      if (this_in.value && document.querySelector('.utm_medium_container')) {
        document.querySelector('.utm_medium_container').querySelector('input').value = 'cpc'
      }
    }

  })
})
