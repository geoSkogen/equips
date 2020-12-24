jQuery(document).ready( function($) {
  console.log('append-hrefs-there');
  var pair = []
  var pair_string = '?';
  var utm_string = '?';
  var links = document.querySelectorAll('a')
  var pos = window.location.href.indexOf('?');
  var query_str = (pos) ? window.location.href.slice(pos+1) : '';
  var query_arr = query_str.split('&');
  var utm_param = '';
  var str = '';
  var place = {}

  function geo_pipe(str) {
    new_str = '';
    new_arr = str.split(',');
    for (var i = 0; i < new_arr.length; i++) {
      new_str += new_arr[i];
      new_str += (i < new_arr.length-1) ? ' | ' : '';
    }
    return new_str;
  }
  query_arr.forEach( (e) => {
    pair = e.split('=')
    if (equips_settings_obj.params.indexOf(pair[0]) > -1 || pair[0].indexOf('utm_')===0) {
      //query-variable/url-parameter is in use by this plugin or it's a UTM parameter
      //add it to the record string

      /* EQUIPS SWAP SUBROUTINE FOR UTMS */
      if (pair[0].indexOf('utm_')===0) {
        //console.log('got utm param');
        utm_param = pair[0].replace('utm_','');
        if (equips_settings_obj.params.indexOf(utm_param) > -1) {
          //console.log('got registered utm param');
          switch(utm_param) {
            case 'content' :
              if (equips_settings_obj.loc_assoc[pair[1]]) {
                place = equips_settings_obj.loc_assoc[pair[1]]
                for (var i = 0; i < equips_settings_obj.shortcodes.length; i++) {
                  var swap_els = document.querySelectorAll('.'+equips_settings_obj.shortcodes[i])
                  swap_els.forEach( function (swap_el) {
                    if (swap_el.innerText.indexOf(equips_settings_obj.fallbacks[i])>-1) {
                      swap_el.innerText.replace(equips_settings_obj.fallbacks[i],place['city_name'])
                    }
                  })
                }
              } else {
                //console.log('geo swap target not found');
              }
              break;
            default :
          }
        }
      }
      /* EQUIPS SWAP SUBROUTINE FOR UTMS */

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
