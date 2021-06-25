<?php

class Equips_Options{

  public function __construct() {

    add_action(
     'admin_menu',
     [$this,'equips_register_menu_page']
    );

  }

   public function equips_register_menu_page() {
      add_menu_page(
        'equips',                        // Page Title
        'equips',                       // Menu Title
        'manage_options',             // for Capabilities level of user with:
        'equips',                    // menu Slug(page)
        [$this,'cb_equips_options_page'], // CB Function cb_equips_options_page()
        'dashicons-editor-code',  // Menu Icon
        20
      );

      add_submenu_page(
       'equips',                         //parent menu
       'equips images',                // Page Title
       'equips images',               // Menu Title
       'manage_options',             // for Capabilities level of user with:
       'equips_images',             // menu Slug(page)
       [$this,'cb_equips_images_page']       // CB Function plugin_options_page()
      );

      add_submenu_page(
        'equips',                         //parent menu
        'equips geo',                // Page Title
        'equips geo',               // Menu Title
        'manage_options',             // for Capabilities level of user with:
        'equips_geo',             // menu Slug(page)
        [$this,'cb_equips_geo_page'] // CB Function plugin_options_page()
      );
   }

  //// template 1 - <form> body
   public function cb_equips_geo_page() {
     $this->cb_equips_admin_page('equips_geo');
   }

   public function cb_equips_options_page() {
     $this->cb_equips_admin_page('equips');
   }

   public function cb_equips_images_page() {
     $this->cb_equips_admin_page('equips_images');
   }

   protected function cb_equips_admin_page($db_slug) {

     $h1 = str_replace(['equips','_'],'',$db_slug)
     ?>
     <div class='form-wrap'>
       <h1>equips <?php echo $h1; ?></h1>
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
