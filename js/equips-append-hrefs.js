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
  var place_props = []
  var utm_keys = {'content':'location',
                  'location':'content'}
  // NOTE: place_props array index follows the:
  // *order of the shortcode/fallback form fields on the equips geo form*
  // so as we iterate geo_fallbacks by index, we can swap them for the associated data column
  // this has nothing to do with the order in which they appear in the DOM -
  // BUT, the DOM elements do require:
  // *a class name matching the place_prop's shortcode in order to swap on the frontend*
  var place_props = ['phone','locale','region','service_area']
  //see equips_local_monster lines 48--56 for full schema - most of it's not in use.
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

    //console.log('query')
    //console.log(e)
    pair = e.split('=')
    var param_index = equips_settings_obj.params.indexOf(utm_keys[pair[0].replace('utm_','')])
    if ( param_index  > -1 ) {
      //query-variable/url-parameter is in use by this plugin or it's a UTM parameter
      //add it to the record string
      //console.log('whitelisted query')
      //console.log(pair[1])
      /* EQUIPS SWAP SUBROUTINE FOR UTMS */
      if (pair[0].indexOf('utm_')===0) {
        //console.log('got utm param');
        utm_param = pair[0].replace('utm_','');

        //console.log(Object.keys(equips_settings_obj.loc_assoc))
        // --disable this portion if your server-side PHP allows sessions and preserves
        // a query string property in the $_SERVER superglobal array
        // frontend swap is intened to handle instances where the aforementioned would
        // 'break' a hosting CDN's ability to serve a cached version of the page -
        // Hosts optimized for WordPress incorpoarate 'whitelisted' query vars like
        // 'location'

        if (equips_settings_obj.params.indexOf(utm_keys[utm_param]) > -1) {
          //console.log('got registered utm param');
          switch(utm_param) {
            case 'content' :
            case 'location' :
              if (equips_settings_obj.loc_assoc[pair[1]]) {
                //console.log('param lookup')
                //console.log(pair[1])
                place = equips_settings_obj.loc_assoc[pair[1]]
                //place_props = Object.keys(place)
                console.log(place)

                //console.log(equips_settings_obj.shortcodes[i])
                var swap_els = document.querySelectorAll('.'+equips_settings_obj.shortcodes[param_index])

                swap_els.forEach( function (swap_el) {
                  var swap_text = swap_el.innerText.replace(equips_settings_obj.fallbacks[param_index],place['city_name'])
                //console.log('swap_el');
                //console.log(swap_el)
                //console.log('swap_target')
                //console.log(equips_settings_obj.fallbacks[i])
                  swap_el.innerText = swap_text
                })
                for (var i = 0; i < equips_settings_obj.geo_shortcodes.length; i++) {
                  //console.log(equips_settings_obj.shortcodes[i])
                  var swap_els = document.querySelectorAll('.'+equips_settings_obj.geo_shortcodes[i])
                  //console.log('swap el current class :')
                  //console.log(equips_settings_obj.geo_shortcodes[i])
                  //console.log('swap el current text target :')
                  //console.log(equips_settings_obj.geo_fallbacks[i])
                  //console.log('swap el current place prop:')
                  //console.log(place_props[i])

                  swap_els.forEach( function (swap_el) {
                    var swap_text = swap_el.innerText.replace(equips_settings_obj.geo_fallbacks[i],place[place_props[i]])
                    if (swap_el.innerText.indexOf(equips_settings_obj.geo_fallbacks[i])>-1) {
                      //console.log('swapped value')
                      //console.log(equips_settings_obj.geo_fallbacks[i])
                      //console.log('for')
                      //console.log(place[place_props[i]])
                    }
                    //console.log('swap_el');
                    //console.log(swap_el)
                    //console.log('swap_target')
                    //console.log(equips_settings_obj.fallbacks[i])
                    swap_el.innerText = swap_text
                  })
                }

              } else {
                console.log('geo content target not found');
              }
              break;
            default :
          }
        }
      }

      /* EQUIPS DECORATED URL PERSISTENCE SUBROUTINE */

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
        // NOTE: Revisit this rule - do we need this? How do we control
        // WordPress's unexpected handling of query vars like 'location'
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
