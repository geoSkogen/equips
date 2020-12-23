<?php

class Equips_Options_Init {

   static function equips_register_menu_page() {
      add_menu_page(
          'equips',                        // Page Title
          'equips',                       // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'equips',                    // menu Slug(page)
          array('Equips_Options_Init','cb_equips_options_page'), // CB Function cb_equips_options_page()
          'dashicons-editor-code',  // Menu Icon
          20
      );

      add_submenu_page(
        'equips',                         //parent menu
        'equips geo',                // Page Title
        'equips geo',               // Menu Title
        'manage_options',             // for Capabilities level of user with:
        'equips_geo',             // menu Slug(page)
        array('Equips_Options_Init','cb_equips_geo_page')// CB Function plugin_options_page()
      );
   }

  //// template 1 - <form> body
   static function cb_equips_geo_page() {
     self::cb_equips_admin_page('equips_geo');
   }

   static function cb_equips_options_page() {
     self::cb_equips_admin_page('equips');
   }

   static function cb_equips_admin_page($db_slug) {
     ?>
     <div class='form-wrap'>
       <h2>equips - local</h2>
       <form method='post' action='options.php' id='<?php echo $db_slug; ?>_form'>
       <?php
         settings_fields( $db_slug );
         do_settings_sections( $db_slug  );
       ?>
         <div class='inivs-div' style="display:none;">
           <input class='invis-input' id='drop_field' name=<?php echo "{$db_slug}[drop]"; ?> type='text'/>
         </div>
         <p class='submit'>
           <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e("Save Changes") ?>' />
         </p>
       </form>
     </div>
     <?php
   }

}

?>
