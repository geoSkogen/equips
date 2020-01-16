jQuery(document).ready( function($) {
  console.log(equips_settings_obj);
  var pair = []
  var pair_string = '?';
  var links = document.querySelectorAll('a')
  var ps = document.querySelectorAll('p')
  var pos = window.location.href.indexOf('?');
  var query_str = (pos) ? window.location.href.slice(pos+1) : '';
  var query_arr = query_str.split('&');

  query_arr.forEach( (e) => {
    pair = e.split('=')
    if (equips_settings_obj.params.indexOf(pair[0]) > -1) {
      pair_string += [pair[0]] + '=' + pair[1] + '&'
    }
  })

  pair_string = pair_string.slice(0,pair_string.length-1);

  links.forEach( (e) => {
    if (e.href.indexOf(window.location.origin) === 0 &&
        e.href.indexOf('wp-admin') === -1 &&
        e.href != equips_settings_obj.site_url &&
        e.href != equips_settings_obj.site_url + '/') {
      e.href = e.href + pair_string
    } else {

    }
  })
})
