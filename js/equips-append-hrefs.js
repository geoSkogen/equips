jQuery(document).ready( function($) {
  console.log(equips_settings_obj);
  var pair = []
  var pair_string = '?';
  var utm_string = '?';
  var links = document.querySelectorAll('a')
  var ps = document.querySelectorAll('p')
  var pos = window.location.href.indexOf('?');
  var query_str = (pos) ? window.location.href.slice(pos+1) : '';
  var query_arr = query_str.split('&');

  query_arr.forEach( (e) => {
    pair = e.split('=')
    if (equips_settings_obj.params.indexOf(pair[0]) > -1 || pair[0].indexOf('utm_')===0) {
      //query-variable/url-parameter is in use by this plugin or it's a UTM parameter
      //add it to the record string
      pair_string += [pair[0]] + '=' + pair[1] + '&'
      if (pair[0].indexOf('utm_')===0) {
        //keep a separate string that's UTM params only
        utm_string += [pair[0]] + '=' + pair[1] + '&'
      }
    }
  })

  pair_string = pair_string.slice(0,pair_string.length-1);
  utm_string = utm_string.slice(0,utm_string.length-1);

  links.forEach( (e) => {
    if (e.href.indexOf(window.location.origin) === 0 &&
        e.href.indexOf('wp-admin') === -1 &&
        window.location.href.indexOf('wp-admin') === -1) {
      // link is self-referring and not wp-admin; current page is not wp-admin;
      if (e.href != equips_settings_obj.site_url &&
          e.href != equips_settings_obj.site_url + '/') {
        // link is not to the homepage; append all parameters;
        e.href = e.href + pair_string
      } else {
        // link is to the homepage; append utms only
        e.href = e.href + utm_string
      }
    } else {
    // link is external or to an admin page; do nothing
    }
  })
});
