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
  }

  //// template 1 - <form> body

  static function cb_equips_options_page() {
    ?>
    <div class='form-wrap'>
      <h2>equips - settings</h2>
      <form method='post' action='options.php'>
      <?php
           settings_fields( 'equips' );
           do_settings_sections( 'equips' );
      ?>
            <div class='inivs-div' style="display:none;">
                <input class='invis-input' id='drop_field' name=equips[drop] type='text'/>
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
