<?php

require("admin_row.php");

class imbanditRedirector {

  public $imbV = 'v297.4';

  private $tableLinkscanners;
  private $tablePosts;
  private $tableRedirectors;
  private $tableRegexes;
  private $tableTerms;
  private $tableTermRelationships;
  private $tableWhitelists;

  // This is the constructor for the imbr plugin object.  Its basic 
  // goal is to set 8 hooks that are executed whenever certain events happen.
  // This is fundamentally how control is passed to the plugin code.
  public function __construct($wpdb) {
    $this->wpdb = $wpdb;

    // Why this style?
    add_action('publish_post', array(&$this, 'ls_singlepost_scan'));
    //add_action('publish_post', 'ls_singlepost_scan');
    //add_action('new_to_publish', 'ls_singlepost_scan');
    //add_action('draft_to_publish', 'ls_singlepost_scan');

    add_action('admin_menu', array(&$this, 'prc_add_options_to_admin'));

    // What on Earth does this do?
    //add_action('wp_head', array(&$this, 'wp_add_red'));

    add_action('get_header', array(&$this, 'wp_imb_red_head'));
    add_action('init', array(&$this, 'enqueueBackendFiles'));

    add_action('admin_enqueue_scripts', array(&$this, 'imb_enqueue'));

    register_activation_hook(WP_PLUGIN_DIR . '/imbr/imbr.php', array(&$this, 'prc_plugin_install'));
    register_deactivation_hook(WP_PLUGIN_DIR . '/imbr/imbr.php', array(&$this, 'imb_deactivate'));

    // Now setup some table names
    $this->tableLinkscanners      = $wpdb->prefix . 'imb_linkscanner';
    $this->tablePosts             = $wpdb->prefix . 'posts';
    $this->tableRedirectors       = $wpdb->prefix . 'imb_redirector';
    $this->tableRegexes           = $wpdb->prefix . 'imb_regex';
    $this->tableTerms             = $wpdb->prefix . 'terms';
    $this->tableTermRelationships = $wpdb->prefix . 'term_relationships';
    $this->tableWhitelists        = $wpdb->prefix . 'imb_whitelist';
  }

  // called because of add_action(init...
  // we enqueue this same file in imb_enqueue.  Why do it in two places?
  public function enqueueBackendFiles() {
    wp_enqueue_script("controls.js", "/wp-includes/js/controls.js", array(), "0.0.1", true);
  }

  // called because of register_deactivation_hook
  private function imb_deactivate() {
    $options = $this->getOptions();
    $options['apiKey'] = '';
    update_option('imbandit', $options);
  }

  // called because of add_action('admin_enqueue_scripts...
  // we enqueue this same file in enqueueBackendFiles.  Why do it in two places?
  public function imb_enqueue() {
    wp_enqueue_script('control.js', "/wp-content/plugins/imbr/controls.js", array(), ".0.0.1", true);

    $s = plugins_url() . "/imbr/admin_styles.css";
    wp_register_style('admin_styles', $s);
    wp_enqueue_style('admin_styles');
  }

  // When the IMBR Admin screen "save all settings" button is pressed, this function will be invoked.
  // This function will save changes where the user has done any combination of:
  // 1. Changed anything about the existing redirector records
  // 2. Created a new redirector
  // 3. Modified the whitelist
  // 4. Modified the mn parameters
  //private function red_editor_save() {}

  // This method will save the contents of a particular IMBR admin screen row, such as a modified redirector
  // or a new one.  It will _not_ deal with the whitelist or mn parameters.
  private function redirector_save() {

    // 1. First delete everything from imb_linkscanner because we're 
    //    going to rebuild it from scratch.
    //$this->wpdb->query("delete from ".$this->tableLinkscanners);

    // 2. Deal with the existing redirectors.

    // For each redirector, there are 3 interesting fields to deal with...
    // A. category slugs + postIDs aka single_pages_categories
    // B. linkscanner urls            aka ls_regex_new
    // C. manual url targets          aka post_aff_url
    // The following sections will leap to life and update the imb_linkscanner table given some combination of these fields

    // The number of existing redirectors
    //$redirectorCnt = $_POST['redirectorCnt'];

    // 2.1 Iterate over every existing redirector
    //for ($i = 1; $i <= $redirectorCnt; $i++) {

      // 2.1.1 Determine the indexed POST parameter names
      //$url = "post_aff_url_" . $i;              // aka manual url targets
      //$rpost = "random_post_" . $i;
      //$rpage = "random_page_" . $i;
      //$spc = "single_pages_categories_" . $i;   // aka "category slubs + postIDs"
      //$mn = "mn_" . $i;
      //$id = "id_" . $i;
      //$rand = "rand_" . $i;
      //$select = "select_" . $i;
      //$ls_regex_new = "ls_regex_new_" . $i;     // aka linkscanner urls

      // 2.1.2 Update linkscanner set to within a selection of categories
      // Only trigger if category slugs + post ids has a value, and the other two fields do not.
      //if ( $_POST[$ls_regex_new] == '' && $_POST[$spc] != '' && $_POST[$url] == '') {
        //$spcs = explode(',', $_POST[$spc]);
        //foreach ($spcs as $spcItem) {
          // only send $postid _or_ $spc, never both
          //$this->linkscan($_POST[$mn], NULL, NULL,$spcItem);
        //}
      //}

      // 2.1.3 --------New LS Regexs----------------------------------------------------
      //$regexTable = $this->wpdb->prefix . 'imb_regex';
      // ls_regex_new aka linkscanner urls
      // Only trigger if linkscanner URLs has a value, regardless of the values of the other two fields
      //$regexArray = explode("\n", $_POST[$ls_regex_new]);
      //foreach ($regexArray as $newRegex) {
        //$newRegex = trim($newRegex);
        //if ($newRegex == NULL)
          //continue;

        //$encoded = base64_encode($newRegex);
        //$sql = "INSERT INTO " . $regexTable . " (regex, mn) VALUES ('$encoded','$_POST[$mn]') on DUPLICATE KEY UPDATE regex='$encoded'";
        //$this->wpdb->query($sql);
        //$spcs = explode(',', $_POST[$spc]);
        //foreach ($spcs as $spcItem) {
          // only send $postid _or_ $spc, never both
          //$this->linkscan($_POST[$mn], $newRegex, NULL,$spcItem);
        //}
      //}

      // 2.1.4 Now update the redirector record with whatever POST parameters have been sent.

      //set variables for easily reading checkboxes
      //if (isset($_POST[$rpost])) {
      //$rpost2 = 1;
      //} else {
      //$rpost2 = 0;
      //}
      //if (isset($_POST[$rpage])) {
      //$rpage2 = 1;
      //} else {
      //$rpage2 = 0;
      //}

      //$query = "update $this->tableRedirectors set url = '" . $_POST[$url] . "', random_post = '" . $rpost2 . "', random_page = '" . $rpage2 . "', single_pages_categories = '" . $_POST[$spc] . "', mn = '" . $_POST[$mn] . "', rand = '" . $_POST[$rand] . "' where id = '" . $_POST[$id] . "'";
      //$query = "update $this->tableRedirectors set url = '" . $_POST[$url]
        //. "', random_post = '" . $rpost2 
        //. "', random_page = '" . $rpage2 
        //. "', single_pages_categories = '" . $_POST[$spc]
        //. "', mn = '" . $_POST[$mn] 
        //. "', rand = '" . $_POST[$rand] 
        //. "' where id = '" . $_POST[$id] . "'";
      //$this->wpdb->query($query);
      //}
    //} // iterate over the existing redirectors

    
    // 3. Now detect if a new redirector should be created.
    //    post_aff_url aka manual url targets.
    //if (isset($_POST['post_aff_url'])) {
    if (isset($_POST['save_redirector'])) {

      // Assuming so, clean up the other parameters

      // single_pages_categories aka "category slubs + postIDs"
      if (!isset($_POST['single_pages_categories']))
        $_POST['single_pages_categories'] = NULL;

      if (!isset($_POST['post_aff_url']))
        $_POST['post_aff_url'] = NULL;

      if (isset($_POST['random_post']))
        $rand_post = 1;
      else
        $rand_post = 0;

      if (isset($_POST['random_page']))
        $rand_page = 1;
      else
        $rand_page = 0;

      // Only need $mn, not $mn_upd
      $mn = $_POST['newRedirectorMN'];

      // If _any one_ of the following 5 variables are supplied then
      // write the new record to the redirector_table.
      if(
        $_POST['post_aff_url'] != '' ||             // aka manual url targets
        $_POST['single_pages_categories'] != '' ||  // aka "category slubs + postIDs"
        $_POST['ls_regex_new'] != '' ||             // aka linkscanner urls
        $rand_post != '0'||
        $rand_page != '0'){

        // post_aff_url aka manual url targets
        // single_pages_categories aka "category slubs + postIDs"
        //$query = "INSERT INTO $this->tableRedirectors set " .
          //"url = '" . $_POST['post_aff_url'] . " " .
          //. "', single_pages_categories = '" . $_POST['single_pages_categories'] 
          //. "', mn ='" . $mn
          //. "', random_post = '" . $rand_post 
          //. "', random_page = '" . $rand_page . "' ";

        $query = "insert into $this->tableRedirectors set " .
          "random_post = -1, " .    // presently unused
          "random_page = -1, " .    // presently unused
          "url = 'N/A', " .           // presently unused
          "single_pages_categories = '" . $_POST['single_pages_categories'] . "', ". // this may be a comma delimited list
          "mn = '$mn', " . 
          "rand = -1, " .           // presently unused
          "post_identifier = 'N/A'"; // presently unused
        $this->wpdb->query($query);
      }

      // This is the mn for the new redirector.  Why a 2nd variable name?  Reuse $mn instead.
      //$mn4LS = $_POST['newRedirectorMN'];

      // post_aff_url aka manual url targets
      // single_pages_categories aka "category slubs + postIDs"
      //$_POST['post_aff_url'] = false;
      //$_POST['single_pages_categories'] = false;
      //$_POST['newRedirectorMN'] = false;
      //exit(var_dump($this->wpdb->last_query));
    } //isset($_POST['post_aff_url']) // post_aff_url aka manual url targets

    // As with existing redirectors, new redirectors have the same 3 interesting boxes to deal with and
    // imb_linkscanner will be updated or not according to the same criteria in sections 2.1 and 2.2. 

    // 3.1. --------new linkscanner set to within a selection of categories----------------------------------------------------
    // single_pages_categories aka "category slubs + postIDs"
    // This only applies to new redirectors
    $spc4OldRegex = $_POST['single_pages_categories'];
    // post_aff_url aka manual url targets
    // ls_regex_new aka linkscanner urls
    // Only trigger if category slugs + post ids has a value, and the other two do not.
    if ($_POST['ls_regex_new'] == '' && $spc4OldRegex != '' && $post_aff_url == '') {
      $i = 5/0;
      //$spcs = explode(',', $spc4OldRegex);
      //foreach ($spcs as $spcItem) {
        // only send $postid _or_ $spc, never both
        //$this->linkscan($mn4LS, NULL, NULL,$spcItem);
      //}
    }

    // 3.2. --------OLD LS Regexs----------------------------------------------------
    // This only applies to new redirectors
    // ls_regex_new may be 'set' but '' also.  If that's the case then no changes to the db occur.
    // Is this part of adding a new record?
    // ls_regex_new aka linkscanner urls
    // Only trigger if linkscanner URLs has a value, regardless of the values of the other two fields
    if (isset($_POST['ls_regex_new'])) {
      //$regexTable = $this->wpdb->prefix . 'imb_regex';
      $regexArray = explode("\n", $_POST['ls_regex_new']);
      foreach ($regexArray as $newRegex) {
        $newRegex = trim($newRegex);
        if ($newRegex == NULL)
          continue;

        // don't need to do this
        // base64_encoded version of the newRegex
        //$encoded = base64_encode($newRegex);
        //$newRegexB64 = base64_encode($newRegex);

        // $mn is already set with the value we want.  No need for a 2nd variable name.
        //$mn = $mn4LS;

        //$sql = "INSERT INTO " . $this->tableRegexes . " (regex, mn) VALUES ('$newRegex','$mn') on DUPLICATE KEY UPDATE regex='$newRegex'";
        $sql = "INSERT INTO $this->tableRegexes (regex, mn) VALUES ('$newRegex','$mn') on DUPLICATE KEY UPDATE regex ='$newRegex'";
        $this->wpdb->query($sql);

        // Temporarily remove the looping
        $spcItem = $spc4OldRegex;
        //$spcs = explode(',', $spc4OldRegex);
        //foreach ($spcs as $spcItem) {
          // only send $postid _or_ $spc, never both
          if (is_numeric($spcItem))
            $this->linkscan($mn, $newRegex, $spcItem); // this is a postid
          else {
            $this->linkscan($mn, $newRegex, NULL,$spcItem); // this is a spc
          }
        //}
      }
    }

    // 4. -----------------------insert white list ---------------------------
    // This is not relevant to any particular row and it's out of place here.
    // whitelist may be 'set' but '' also.  If that's the case then no changes to the db occur.
    // warning: this code does not check for set whitelist.  Should it ?
    // This is not part of adding a new redirector record, the whitelist applies globally 
    //$whiteListTable = $this->wpdb->prefix . 'imb_whiteList';
    //$refererRegexArray = explode("\n", $_POST['whiteList']);
    //$delWLSql = "delete from $this->tableWhitelists";
    //$this->wpdb->query($delWLSql);
    //foreach ($refererRegexArray as $refererRegex) {
      //$refererRegex = trim($refererRegex);
      //if ($refererRegex == NULL)
        //continue;
      //$i = 5/0;
      //$sql = "INSERT INTO " . $whiteListTable . " (refererRegex,status) VALUES ('$refererRegex',0) on DUPLICATE KEY UPDATE refererRegex='$refererRegex'";
      //$this->wpdb->query($sql);
    //}

    // 5. Change the mn name?
    //if($_POST['mnName'] != 'mn'){
    //$chageParaSql = "INSERT INTO " . $whiteListTable . " (refererRegex,status) VALUES ('".$_POST['mnName']."',-1)";
    //}
    //$this->wpdb->query($chageParaSql);

  }

  // This function will delete the imbr files from the db,
  // if present, and then recreate them.  This is suitable for
  // "resetting" the db back to a beginning condition or for 
  // originally initializing it as upon installtion.
  //
  // The original method used dbDelta
  // which required wp-admin/includes/upgrade.php.  However, dbDelta
  // is a finicky beast and not needed when we only choose to create
  // tables, instead of modifying them.
  private function resetDatabase() {

    // What does this do ?
    //save regexes in options
    //$jsim_url_regex = '((mailto\:|(news|(ht|f)tp(s?))\://){1}\S+)';
    //update_option('jsim_url_regex', $jsim_url_regex);

    // This is only useful for testing when we need to reset the 
    // posts to a known state.
    $this->eraseAllPosts();

    $this->resetLinkscanners();
    $this->resetRedirectors();
    $this->resetRegexes();
    $this->resetWhitelists();
  }

  // This function is the entry point for the functionality to display/manage/save
  // the IMBR options settings page.  This settings page is fairly complicated
  // so this function will call subfunctions that will generate the various elements
  // of the HTML.
  //
  // Given an ordinary GET request, this function, as well as the
  // related subfunctions, will display the existing contents of the IMBR options.
  // However, if this function is called with a POST request then other functionality
  // may be performed first, such as saving of changed options or resetting the db, before
  // proceeding onto the ordinary display of the now updated information.
  public function imb_red_editor() {

    // 1. In the event this function has been called via a POST request,
    // look for certain commands as POST parameters and execute them first,
    // and then continue with the ordinary display of the admin screen.
    // The if-else structure ensures that only one option will 
    // run and only the first option that passes, even if
    //other options might also pass.

    // 1.1 Reset the database?
    if (isset($_POST['redirector_databaseclear'])) {
      $this->resetDatabase();

    // 1.1 Delete a specific redirector
    //if (isset($_POST['delete_redirector'])) {
      //$query = "delete from $this->tableRedirectors where mn = '$_POST['delete_redirector']'";
      //$redirector = $_POST['delete_redirector'];
      //$query = "delete from $this->tableRedirectors where mn = '$redirector'";
      //$this->wpdb->query($query);

    // This parameter value cannot be set and thus this branch of code
    // is never used.
    //} elseif (isset($_POST['linkscanner_databaseclear'])) {
    //  //$this->ls_init('delete');

    // 1.2 Save the contents of a redirector record
    } else if (isset($_POST['save_redirector'])) {
      $this->redirector_save();

    //} else if (isset($_POST['save_redirector'])) {
      // If control makes it here then we surmise that the user has performed any combination of:
      // 1. Changed something about the existing redirector records
      // 2. Created a new redirector
      // 3. Modified the whitelist
      // 4. Modified the mn parameters
      // ... and is saving the changes.
      //$this->red_editor_save();
    }

    // 1. The entire IMBR admin page fits inside this div.
    echo "<div class=\"wrap\">";
    echo   $this->imb_red_editor_wrap_div_contents();
    echo "</div>";
  }

  // The wrap_div wraps a 2nd, wrapper div.  Why?
  private function imb_red_editor_wrap_div_contents() {
    echo "<div style=\"clear:both\">";
    echo   $this->imb_red_editor_2nd_wrap_div_contents();
    echo "</div>";
    echo "<div class=\"clear\"></div>";
  }

  // The 2nd_wrap_div essentially wraps a center tag.  Why?
  private function imb_red_editor_2nd_wrap_div_contents() {
    echo "<br>";
    echo "<center>";
    echo   $this->imb_red_editor_center_contents();
    echo "</center>";
  }

  // All of the IMBR admin screen is inside this 3rd layer wrapper.
  private function imb_red_editor_center_contents() {

    // single_pages_categories aka "category slubs + postIDs"
    //if (isset($_POST['single_pages_categories']))
    //$spc4OldRegex = $_POST['single_pages_categories'];
    //if (isset($_POST['apiKeyRequest'])) {
    //$this->requestApiKey($_POST['paypalemail']);
    //}

    // 1. Now display the IMBR logo and revision.
    echo "<img src=\"../wp-content/plugins/imbr/imbr.png\"><br>$this->imbV<br><br><br><br>";

    // 2. Other than the logo and revision, nothing else will work unless
    //    the API key check passes.
    //$apiKey = $this->checkApiKey();
    //if ($apiKey != TRUE) {
    //echo $this->getApiKey() . '</div></center></div>';
    //exit;
    //}

    // Is there any regex to be deleted?
    //if (isset($_GET['action']) && $_GET['action'] == 'deleteRegex') {
    //$id = $_GET['regexid'];
    //$regexTable = $this->wpdb->prefix . 'imb_regex';
    //$lsTable = $this->wpdb->prefix . 'imb_linkscanner';
    //$sql = "SELECT * FROM " . $regexTable . " WHERE id='$id'";
    //$data = $this->wpdb->get_row($sql, ARRAY_A);
    //$mn = $data['mn'];
    //$query = ("DELETE FROM $lsTable WHERE mn = '$mn'");
    //$this->wpdb->query($query);
    //$query2 = ("DELETE FROM $regexTable WHERE id = '" . $_GET['regexid'] . "'");
    //$this->wpdb->query($query2);
    //}

    // post_aff_url aka manual url targets
    // Extract a more convenient form of post_aff_url
    //if (isset($_POST['post_aff_url'])) {
    //$post_aff_url = $_POST['post_aff_url'];
    //} else {
    //$post_aff_url = '';
    //}

    //$ls_table = $this->wpdb->prefix . "imb_linkscanner";
    //$co = explode('wp-admin', $_SERVER['REQUEST_URI']); //Gets last part of URL link
    //$co = substr($co[0], 0, -1); //Cleans up link so that it's at the last letter.
    //$linkadd = 'http://' . $_SERVER['HTTP_HOST'] . $co; //Adds HTTP to the beginning of the link from above.
    //deal with magic quote crap for update_options
    //if (get_magic_quotes_gpc()) {
    //$_POST = array_map('stripslashes_deep', $_POST);
    //$_GET = array_map('stripslashes_deep', $_GET);
    //$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    //$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    //}

    //$links = '';

    // 4. Emit the Instruction Box HTML
    echo "<div id=\"instruction_box\">";

    echo   "<div class=\"info_box\">";
    echo     "<strong>A Route</strong> - Scans all Categories + individual PostIDs for any links containing /regex/. Randomly Chooses one /regex/ link to redirect to and uses the parent as the referrer.";
    echo   "</div>";

    echo   "<div class=\"info_box\">";
    echo     "<strong>B Route</strong> - Randomly chooses  post from within the categories/postids to use as the referrer, and then randomly chooses a Manual URL to redirect to.";
    echo   "</div>";

    echo   "<div class=\"info_box\">";
    echo     "<strong>C Route</strong> - Randomly selects referrers from ALL posts, ALL pages, or BOTH. And redirects to a random Manual URL.";
    echo   "</div>";

    //echo "<div></div>"; // class=\"clear\"
    echo "</div>"; // instruction_box
    echo "<br><br>";

    // The major part of the admin screen is displayed in a table.  This is the list of existing
    // redirectors as well as an additional row to contain inputs for a new redirector.
    // Emit that table now.
    $this->imb_red_editor_table_contents();

/*   
    // 8. Now output the bottom controls
    echo "<div></div>"; //  style="clear:both";
    $redirectorCnt = count($redirectors);
    echo "<input type=\"hidden\" name=\"redirectorCnt\" value=\"$redirectorCnt\"/>";
    echo "<br>";

    echo "<div id=\"thecenter\">";
    //<qqqphp
    //$regexTable = $this->wpdb->prefix . 'imb_regex';
    //$sql = "SELECT * FROM " . $regexTable . " ORDER BY id ";
    //$regexes = $this->wpdb->get_results($sql, ARRAY_A);
    //$linkscanner_url = 'http://' . $_SERVER['HTTP_HOST'] . $co . '/?mn=7';

    //echo '<div><br>';
    //echo '<div style="margin:0px 0px 0px 10px;position:relative;float:left;">';
    //if (sizeof($regexes != 0)) {
    //echo '<h3>Link Scanner [regexp]</h3>';
    //foreach ($regexes as $regexItem) {
    //$lsTable = $this->wpdb->prefix . 'imb_linkscanner';
    //$sql = "SELECT * FROM " . $lsTable . " WHERE mn = '" . $regexItem['mn'] . "' ORDER BY RAND() LIMIT 3 ";
    //$links = $this->wpdb->get_results($sql, ARRAY_A);
    //echo '<hr/><table><tr><td>' . base64_decode($regexItem['regex']) . '</td><td><input type="button" onClick="alert_url(\'http:\/\/' . $_SERVER['HTTP_HOST'] . $co . '/?mn=' . $regexItem['mn'] . '\')" value="url"/></td><td> <input type="button" id="deletebutton1" name="deletebutton1" onclick="a=confirm(\'Are you sure?\');if(a == true) top.location=\'options-general.php?action=deleteRegex&regexid=' . $regexItem['id'] . '&page=' . $_GET['page'] . '\';" value="delete"></td></tr></table>';
    //echo "Posts Found:" . count($links);
    //foreach ($links as $link) {
    //$linkText = (strlen($link['link']) >= 40) ? substr($link['link'], 0, 40) . '...' : $link['link'];
    //echo '<br/><a href="' . $link['link'] . '" target="_new">' . $linkText . '</a>  ';
    //}
    //if (isset($this->postsFound[$link['mn']]))
    //echo '<br/>';
    //if (isset($this->postsFound[$link['mn']]))
    //echo ' Posts Found: ' . $this->postsFound[$link['mn']];
    //if (isset($linksFound[$link['mn']]))
    //echo ' Links Found: ' . $this->linksFound[$link['mn']];
    //}
    //}
    // ls_regex_new aka linkscanner urls
    //echo '<hr>New Regex: <input name="ls_regex_new" id="ls_regex_new" type="text"  style="width:350px;clear:both;"></input><br>';
    //echo '</div>';
    //echo '</div>';

    echo "<div id=\"whitelis_div\">";
    echo "<strong>Referrer Whitelist</strong>";
    echo "<br>";
    echo "Regex partial matches, 1 per line. ex, /google/";
    echo "<br>";
    echo "On fail, sends to homepage.";
    echo "<br>";
    echo "Leave blank to disable.";
    echo "<br>";

    //<qqqphp
    //$wlquery = "select * from $wltable where status=0";
    //$refererRegexes = $this->wpdb->get_results($wlquery);
    //if ($refererRegexes) {
    //foreach ($refererRegexes as $refererRegex) {
    //$regex .=  $refererRegex->refererRegex . "\n";
    //}
    //}
    //
    //$linkscanner = 1;
    //QQQ>
    $regex = "";
    echo "<textarea id=\"whiteList\" type=\"text\" name=\"whiteList\">$regex</textarea>";
    echo "</div>"; // whitelis_div

    echo "<div id=\"change_mn_div\">";
    echo "<strong>Modify MN Parameter</strong>";
    echo "<br>";
    //echo "<input type=\"text\"  id=\"mnName\" name=\"mnName\" value=\"<qqqphp echo $mnName; QQQ>'>
    echo "<input type=\"text\"  id=\"mnName\" name=\"mnName\" value=\"mn\">";
    echo "</div>"; // change_mn_div

    echo "<div id=\"save_reset_div\">";
    $page = $_GET['page'];
    //echo "<form action=\"options-general.php?page=$page\" method=\"post\">";
    echo "<input class=\"c_button_link\" type=\"submit\" value=\"Save All Settings\" />";
    echo "<input type=\"hidden\" name=\"update\" value=\"yes\" />";*/
    //echo "</form>"; // end the form started a long time ago.*/

    // redirector database clear button
    echo "<div id=\"reset_database_div\">";
    $page = $_GET['page'];
    echo "<form action=\"options-general.php?page=$page\" method=\"post\">";
    echo "<input type=\"hidden\" name=\"redirector_databaseclear\" value=\"yes\" />";
    //echo "<input onclick=\"if(!confirm('Reset Database?')){ return false; };\" class=\"c_button_link\" type=\"submit\" value=\"Reset Database\" />";
    echo "<input id=\"database_reset\"class=\"c_button_link\" type=\"submit\" value=\"Reset Database\" />";
    echo "</form>";
    echo "</div>"; // reset_database_div

    // linkscanner database rebuild button
    // This is not visible on the UI and thus cannot ever be triggered.
    // Essentially unused
    //echo "<form method=\"post\" action=\"options-general.php?page=$page\">";
    //echo "<input type=\"hidden\" name=\"linkscanner_databaseclear\" value=\"yes\" />";
    //echo "<br><br>";
    //echo "</form>";
    //<qqqphp
    //echo '<input type="submit" style="position:relative;float:left;display:inline;" class="" value="Reset Linkscanner Database" /></form></div>';

    //echo "</div>"; // save_reset_div
    //echo "</div>"; // thecenter
    //echo "<div></div>"; //  class="clear"

  }

  // The major part of the admin screen is displayed in a table.  This is the list of existing
  // redirectors as well as an additional row to contain inputs for a new redirector.
  // This function will emit that table.
  private function imb_red_editor_table_contents() {

    // 1. Start the table
  	echo "<table id=\"redirectorTable\" class=\"widefat the_table\" style=\"width:1000px;\">";

    // 2. Now output the column headers in the table header.
    echo "<thead>";
    echo   "<tr>";
    echo     "<th>Custom Referrers</th>";
    echo     "<th>&nbsp;</th>";
    echo     "<th>Redirection URLs</th>";
    echo     "<th>Link Scanner Found Targets</th>";
    echo     "<th>Other Controls</th>";
    echo   "</tr>";
    echo "</thead>";

    // 3. Now output the table body
    echo "<tbody>";  // id=\"the-list\"

    //$time_difference = get_settings('gmt_offset');
    //$now = gmdate("Y-m-d H:i:s", time());

    //$request = "SELECT ID, post_title, post_excerpt, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish' ";
    //$posts = $this->wpdb->get_results($request);

    //$request_post = "SELECT id, random_post, random_page, url, single_pages_categories, mn, rand FROM $table";
    //$posts_post = $this->wpdb->get_results($request_post);
    // single_pages_categories aka "category slubs + postIDs"
    // url aka manual url targets
    $redirectorsQuery = "SELECT id, random_post, random_page, url, single_pages_categories, mn, rand FROM $this->tableRedirectors";
    $redirectors = $this->wpdb->get_results($redirectorsQuery);

    $i = 1; // index of populated rows
    $mns = ''; // the array of mn for the rows

    //if ($redirectors) { // Test for the existence of any redirectors.

    // 3.1 Iterate over all the existing redirectors, if any
    // and emit a table-row to display them.
    foreach ($redirectors as $redirector) {

      $adminRow = new AdminRow();
      // Can this be a list?
      //$aff_url = $post_post->url;
      //$aff_url = $redirector->url;
      //$single_pages_categories = $post_post->single_pages_categories;
      // single_pages_categories aka "category slubs + postIDs"
      //$single_pages_categories = $redirector->single_pages_categories;

      // Why do this? Why not just use $redirector->mn?
      $mns[$i] = $redirector->mn;

      $rpost = "";
      if ($redirector->random_post == 1)
      $rpost = 'checked="1"';

      $rpage = "";
      if ($redirector->random_page == 1)
      $rpage = 'checked="1"';

      //iterates through existing segments re-assigns MN if it already exists
      //for ($j = 0; $j < $i; $j++) {
      //if ($post_post->mn == $mns[$j] || $post_post->mn <= 9 || $post_post->mn >= 1000) {
      //$mns[$i] = rand(10, 999);
      //}
      //}

      // 3.1.1 single_pages_categories aka "category slugs + postIDs"
      $adminRow->cat_slug_ids_div = "<input value=\"$redirector->single_pages_categories\" id=\"single_pages_categories_$i\" name=\"single_pages_categories_$i\" onkeyup=\"disableCheckboxes(this.value , $i);\">";

      // 3.1.2 random referrers
      // single_pages_categories aka "category slugs + postIDs"
      // what is the relevance of that here? (look in the input tag)
      //$adminRow->random_referrers_div ="<label for=\"random_post_$i\">"
      //<input id="random_post_<qqqphp echo $i; QQQ>" name="random_post_<qqqphp echo $i; QQQ>" type="checkbox" <qqqphp echo $rpost;QQQ> <qqqphp echo $single_pages_categories != NULL ? ' disabled' : ''; QQQ> onchange="disableTextbox(<qqqphp echo $i; QQQ>);">
      //. "<input id=\"random_post_$i\" name=\"random_post_$i\" type=\"checkbox\" $rpost >"
      //. "Random Posts"
      //. "</label>"
      //. "<label for=\"random_page_$i\">"

      //<input id="random_page_<qqqphp echo $i; QQQ>" name="random_page_<qqqphp echo $i; QQQ>" type="checkbox" <qqqphp echo $rpage; QQQ> <qqqphp echo $single_pages_categories != NULL ? ' disabled' : ''; QQQ>onchange="disableTextbox(<qqqphp echo $i; QQQ>);">
      //. "<input id=\"random_page_$i\" name=\"random_page_$i\" type=\"checkbox\" $rpage >"
      //. "Random Pages"
      //. "</label>";



      // 3.1.3 ls_regex_new aka linkscanner urls (different than linkscanner_td)
      // There can only be one regex associated with a given redirector.  Find that regex now.
      $sql = "select * from $this->tableRegexes where mn = '$redirector->mn'";
      $regexRow = $this->wpdb->get_results($sql,ARRAY_A);
      ( count($regexRow) == 1) ? $regex = $regexRow[0]['regex'] : $regex = "";
      $adminRow->link_scanner_div = "<input name=\"ls_regex_new_$i\" id=\"ls_regex_new_$i\" value=\"$regex\" type=\"text\"></input>";

      // 3.1.4 manul url targets aka post_aff_url
      //<div class="manual_url_targets_div">
      //<div class="letter_label">(BC)</div>
      //<strong>Manual URL Targets</strong>
      // Plucked from a redirector table field.  Can this be a list?
      //<textarea name="post_aff_url_<qqqphp echo $i; QQQ>" type="text-area" id="post_aff_url<qqqphp echo $i; QQQ>" ><qqqphp echo $aff_url; QQQ></textarea>
      //$adminRow->manual_url_targets_div = "<textarea name=\"post_aff_url_$i\" type=\"text-area\" id=\"post_aff_url$i\" >$aff_url</textarea>";
      //</div> <!-- manual_url_targets_div -->
      //$co = explode('wp-admin', $_SERVER['REQUEST_URI']);
      //$co = substr($co[0], 0, -1); //jared modified negative offset to -1, it used to be -2
      //echo '<div><input name="" type="text" id="" value=""/>';
      //echo "</td>"; // redirection_td

      // 3.1.5 linkscanner_td (different than link_scanner_div)
      $sql = "SELECT * FROM $this->tableLinkscanners WHERE mn = '$redirector->mn'";
      $links = $this->wpdb->get_results($sql, ARRAY_A);
      $linksText = "";

      foreach ($links as $link) {
        $linkText = (strlen($link['link']) >= 38) ? substr($link['link'], 0, 38)  : $link['link'];
        //echo '<a href="' . $link['link'] . '" target="_new">' . $linkText . '</a>  ';
        //$adminRow->link_scanner_div = "<a href=" . $link['link'] . " target=\"_new\">$linkText</a>";
        $linksText .= $linkText.'&#13;';
      }

      $countLink = count($links);
      $adminRow->linkscanner_td = "<textarea id=\"linkscanner_links_$i\">$linksText</textarea>"
        . $countLink . " Posts";

      //$n = $redirector->rand;

      //$adminRow->homepage_div = "<label>"
      //. "Homepage %"
      //<input name="rand_<qqqphp echo $i; QQQ>" type="text" id="rand_<qqqphp echo $i; QQQ>" value="<qqqphp echo $post_post->rand; QQQ>" size="3"/>
      //. "<input name=\"rand_$i\" type=\"text\" id=\"rand_$i\" value=\"$n\"  size=\"3\"/>"
      //. "</label>";
      //. "</div>";      // homepage_div

      //<div class="mn_numbers_div">
      //$adminRow->mn_numbers_div = "<label>"
      //. "ID"
      //<input name="mn_<qqqphp echo $i; QQQ>" type="text" id="mn_<qqqphp echo $i; QQQ>" value="<qqqphp echo $mns[$i]; QQQ>" size="3"/>
      //. "<input name=\"mn_$i\" type=\"text\" id=\"mn_$i\" value=\"$mns[$i]\" size=\"3\"/>"
      //. "</label>"
      //. "<input name=\"id_$i\" type=\"hidden\" id=\"id_$i\" value=\"$redirector->id\"/>";
      //<qqqphp
      //$wltable = $this->wpdb->prefix . 'imb_whiteList';
      //$mnNameQuery = "select * from $wltable where status=-1";
      //$mnNames = $this->wpdb->get_results($mnNameQuery);
      //if ($mnNames) {
      //foreach ($mnNames as $mnNameDB) {
      //$mnName =  $mnNameDB->refererRegex;
      //}
      //}
      //if( $mnName == '' || $mnName == null){
      //$mnName = 'mn';
      //}
      //<div class="other_url_div">
      //<input class="c_button_link" type="button" onClick="alert_url('http:\/\/<qqqphp echo $_SERVER['HTTP_HOST'] . $co . '/?'.$mnName.'=' . $mns[$i]; QQQ>')" value="url" />
      //$http_host = $_SERVER['HTTP_HOST'];
      // todo fix $mnName and $co
      //$adminRow->other_url_div = "<input class=\"c_button_link\" type=\"button\" onclick=\"alert_url('http:\/\/$http_host/?mn=$mns[$i]')\" value=\"url\" />";
      //</div> <!-- other_url_div -->
      //<div class="other_delete_div">
      //<button class="c_button_link" onclick="if(!confirm('Delete This Entry?')){ return false; };" name="select_<qqqphp echo $i; QQQ>" id="select_<qqqphp echo $i; QQQ>" value="delete">delete</button>
      //$adminRow->other_delete_div = "<button class=\"c_button_link\" onclick=\"if(!confirm('Delete This Entry?')){ return false; };\" name=\"select_$i\" id=\"select_$i\" value=\"delete\">delete</button>";
      //$adminRow->other_delete_div = "<button "
      //. "class=\"c_button_link\" "
      //. "onclick=\"if(!confirm('Delete This Entry?')){ return false; };\" "
      //. "name=\"delete_redirector\" "
      //. "id=\"select_$i\" "
      //. "value=\"$mns[$i]\">delete</button>";
      //</div> <!-- other_delete_div -->
      //$r .= "</td>"; // other_controls_td

      //$adminRow->other_save_div = "<button "
      //  . "class=\"c_button_link\" "
      //  . "name=\"save_redirector\" "
      //  . "id=\"select_$i\" "
      //  . "value=\"$mns[$i]\"
      //  . ">delete</button>";
      // 3.2.6 save submit button
      $adminRow->other_save_div = "<input name=\"save_redirector_$i\" class=\"c_button_link\" type=\"submit\" value=\"Save\" />";

      echo $adminRow->getHTML();
      //$tbody .= $adminRow->getHTML();
      //	echo $n;*/
      //$i++;
    } // $redirectors as $redirector

    //} // for$redirectors

    // 3.2 Now emit a table row to contain the controls for a new redirector.
    //$last = '';
    $adminRow = new AdminRow();
    $adminRow->populated = false; // This is the unpopulated data-entry row

    // 3.2.1 category slugs + post ids, aka single_pages_categories
    //$adminRow->cat_slug_ids_div = "<input name=\"single_pages_categories\" id=\"single_pages_categories\" type=\"text\" onkeyup=\"disableCheckboxesNew(this.value)\">";
    $adminRow->cat_slug_ids_div = "<input name=\"single_pages_categories\" id=\"single_pages_categories\" type=\"text\" >";

    // 3.2.2 random referrers
    //$adminRow->random_referrers_div = "<label for=\"random_post\">"
    //. "<input name=\"random_post\" id=\"random_post\" type=\"checkbox\" onchange=\"disableTextboxNew()\">"
    //. "Random Posts"
    //. "</label>"
    //. "<label for=\"random_page\">"
    //. "<input id=\"random_page\" name=\"random_page\" type=\"checkbox\" onchange=\"disableTextboxNew()\">"
    //. "Random Pages"
    //. "</label>";
    //echo "</div>"; // random_referrers_div

    // 3.2.3 ls_regex_new aka linkscanner urls (different than linkscanner_td)
    $adminRow->link_scanner_div = "<input type=\"text\" id=\"ls_regex_new\" name=\"ls_regex_new\">";

    // 3.2.4 manul url targets aka post_aff_url
    //$adminRow->manual_url_targets_div = "<textarea name=\"post_aff_url\" type=\"text\" id=\"post_aff_url\"></textarea>";

    // 3.2.5 linkscanner_td (different than link_scanner_div)
    $newRedirectorMN = rand(10, 999); // new redirector mn number
    //<input name="mn_upd" type="hidden" id="mn_upd" value="<qqqphp echo $newRedirectorMN; QQQ>" size="10" readonly="readonly"/>
    $adminRow->linkscanner_td = "<input name=\"newRedirectorMN\" type=\"hidden\" id=\"newRedirectorMN\" value=\"$newRedirectorMN\" />";

    // 3.2.6 other_controls_td
    //$r .= "<td>"; // 4. class="other_controls_td"
    //$other_controls = "";
    //$adminRow->other_controls_td = "<div>" // class="homepage_div"

    // 3.2.6.1 homepage_div
    //$adminRow->homepage_div = "<label>"
    //. "Homepage %"
    //. "<input type=\"text\" size=\"3\" value=\"0\" id=\"rand_1\" name=\"rand_1\">";
    //. "</div>";  // homepage_div
    //$r .= "</td>"; // other_controls_td
    //echo $r;
    //echo "</tr>";

    // 3.2.6.2 save submit button
    $adminRow->other_save_div = "<input name=\"save_redirector\" class=\"c_button_link\" type=\"submit\" value=\"Save\" />";

    echo $adminRow->getHTML();
    echo "</tbody>";
    echo "</table>";
  }

  // This function will find the linkscanner links associated with the given redirector.
  // It will return an array of links.
  private function getLinkscannerLinks() {
    // Now populate the linkscanner URLs aka ls_regex from the regex table
    //add where mn
    //$sql = "SELECT * FROM " . $regexTable
    //. " where mn=" . $mns[$i];
    //$sql = "select * from $this->tableRegexes where mn = \"$redirector->mn\" ";
    //" ORDER BY id "; // what diff does the order make?
    //$regexes = $this->wpdb->get_results($sql, ARRAY_A);

    // Substantial duplication within these two branches...
    // The duplicated code computes $showAllLinkText and $countLink
    // for subsequent use.
    //$countLink = 0;
    //$showAllLinkText = '';
    $retVal = array();

    //if (count($regexes) == 0) {
      // This branch looks useless, when we we have a redirector w/o a regex?
      $i = 5/0;
      // DUP1 - Find all records in linkscanner table where the spc is
      // included in the redirector table, the mn is the same, and regex = ''
      //$lsTable = $this->wpdb->prefix . 'imb_linkscanner';
      // single_pages_categories aka "category slubs + postIDs"
      //$spcStr = str_replace(",","','",$single_pages_categories);

      //$sql = "SELECT * FROM " . $lsTable
      //  . " WHERE mn = '" . $mns[$i]
      //  . "' and single_pages_categories in ('".$spcStr."') and regex='' ORDER BY RAND()  ";
      //$links = $this->wpdb->get_results($sql, ARRAY_A);
      // /DUP1

    // DUP2 - Examine all links found and build a concatenated
    //        string of them, suitably truncated to fit.  Also
    //        count them.
    //foreach ($links as $link) {
      //$linkText = (strlen($link['link']) >= 38) ? substr($link['link'], 0, 38)  : $link['link'];
      //echo '<a href="' . $link['link'] . '" target="_new">' . $linkText . '</a>  ';
      //$adminRow->link_scanner_div = "<a href=" . $link['link'] . " target=\"_new\">$linkText</a>";
      //$showAllLinkText .= $linkText.'&#13;';
      //$countLink++;
    //}
    // /DUP2

      // ls_regex_new aka linkscanner urls
      //echo '<input name="ls_regex_new_' . $i . '" id="ls_regex_new_' . $i . '"  type="text" ></input>';
    //} else {

      // More useless.  We will never have multiple regexes, only one.
      //foreach ($regexes as $regexItem) {

        // DUP1 - Find all records in linkscanner table where the spc is
        // included in the redirector table, the mn is the same, and regex is the same.
        //$lsTable = $this->wpdb->prefix . 'imb_linkscanner';
        // single_pages_categories aka "category slubs + postIDs"
        //$spcStr = str_replace(",","','",$single_pages_categories);
        //link scanner sql
        //$regexItemB64 = base64_decode($regexItem['regex']);
        $sql = "SELECT * FROM " . $this->tableLinkscanners;
        //. " WHERE mn = '" . $regexItem['mn']
        //. "' and single_pages_categories in ('".$spcStr."') and "
        //. "regex='".$regexItemB64."' ORDER BY RAND()  ";
        //$links = $this->wpdb->get_results($sql, ARRAY_A);
        // /DUP1

        //if ($regexItemB64) {
        // ls_regex_new aka linkscanner urls
        // echo '<input name="ls_regex_new_' . $i . '" id="ls_regex_new_' . $i . '" value="' . base64_decode($regexItem['regex']) . '" type="text"></input>';
        // Warning! This is repeatedly setting the displayed regex to the most recently found regex.
        // This only works correctly if there is only one regex.  Is this what is intented?
        // The adminRow is set after this if block
        //$regex = $regexItem['regex'];
        //$adminRow->link_scanner_div = "<input name=\"ls_regex_new_$i\" id=\"ls_regex_new_$i\" value=\"$regex\" type=\"text\"></input>";
        //} else {
        // ls_regex_new aka linkscanner urls
        //echo '<input name="ls_regex_new_' . $i . '" id="ls_regex_new_' . $i . '"  type="text" ></input>';
        //$adminRow->link_scanner_div = "<input name=\"ls_regex_new_$i\" id=\"ls_regex_new_\$i\"  type=\"text\" ></input>";
        //}

        // DUP2
        //foreach ($links as $link) {
        //$linkText = (strlen($link['link']) >= 38) ? substr($link['link'], 0, 38)  : $link['link'];
        //echo '<a href="' . $link['link'] . '" target="_new">' . $linkText . '</a>  ';
        //$showAllLinkText .= $linkText.'&#13;';
        //$showAllLinkText = "";
        //$countLink++;
        //}
        // /DUP2

        // unused
        //if (isset($this->postsFound[$link['mn']]))
        //echo '<br/>';
        //if (isset($this->postsFound[$link['mn']]))
        //echo ' Posts Found: ' . $this->postsFound[$link['mn']];
        //if (isset($linksFound[$link['mn']]))
        //echo ' Links Found: ' . $this->linksFound[$link['mn']];
      //} // for each regex item
    //} // count($regexes) == 0
  }

  // This method is called whenever a new post is published,
  // due to the add_action(publish_post... hook.
  public function ls_singlepost_scan($post_id) {
    //$ls_table = $this->wpdb->prefix . 'imb_linkscanner';
    //$jsim_url_regex = get_option('jsim_url_regex');
    //global $jsim_url_regex;
    //last published post

    //$posts = $this->wpdb->prefix . 'posts';
    //$sql = "SELECT * FROM " . $posts . " WHERE post_parent=0 ORDER by id desc";
  	//$last_post = $this->wpdb->get_row($sql, ARRAY_A);
    //$postid = $last_post['ID'];
    //$newPost = $this->wpdb->get_post($post_id);
    //$newPost = get_post($post_id);
    
    // This section will read the entire regex table and 
    // compare the new post to each record.
    //$regexTable = $this->wpdb->prefix . 'imb_regex';
    //$sql = "SELECT * FROM " . $regexTable . " ORDER BY id ";
    $sql = "SELECT * FROM $this->tableRegexes"; // who cares about the order?
    $regexes = $this->wpdb->get_results($sql, ARRAY_A);
    //$handle=fopen('/var/www/wp2/log.txt' , 'w');
    foreach ($regexes as $regex) {
      //fputs($handle, $regex['mn'].$regex['regex']);

      // only send $postid _or_ $spc, never both.
      // In this case, only a single $post_id is relevant, therefore only send that
      //$this->linkscan($regex['mn'], base64_decode($regex['regex']), $postid);
      $this->linkscan($regex['mn'], $regex['regex'], $post_id);
    }
    // fclose($handle);
  }

  // called because of add_action(admin_menu...
  public function prc_add_options_to_admin() {
    add_options_page('IMBR', 'IMBR', 'manage_options', __FILE__, array(&$this, 'imb_red_editor'));
  }

  // called because of register_activation_hook
  private function prc_plugin_install() {
    $i = 5/0;
    //include("phpclient.php");
    //$server_url = "http://imbandit.com/app/server/licenseserver.php";
    //$license_array = processLicense($server_url);
    //if ($license_array[6] != 'active')
    //die('Product not properly licensed. Please obtain a legal license from <a href="http://imbandit.com">Imbandit Website</a>');
    //run imbandit init
    //$this->imb_init();
    //run linkscanner init
    //$this->ls_init();
    //run whiteList init
    //$this->wl_init();
    //move controls.js
    ///* $f1 = $this->im_get_wp_root()."wp-content/plugins/imbr/controls.js";
    //$f1contents = file_get_contents($f1);
    //file_put_contents($this->im_get_wp_root()."wp-includes/js/controls.js",$f1contents); */
  }

  // called because of add_action(wp_head...
  // What does this do?
  //public function wp_add_red($unused) {
    //echo "<qqqphp if (private function_exists('wp_jdis()()')) if (wp_jdis()()) exit(); QQQ>";
  //}

  // called because of add_action(get_header...
  public function wp_imb_red_head() {
    if ($this->wp_jdis())
      exit();
    //	thesis_html_framework();
  }

  //private function getOptions() {
    //Don't forget to set up the default options
    //if (!$options = get_option('imbandit')) {
    //$options = array('default' => 'options');
    //update_option('imbandit', $options);
    //}
    //unset($options['apiKey']); update_option('imbandit', $options);exit;
    //return $options;
  //}

  //private function checkApiKey() {
    //$options = $this->getOptions();
    //if (!isset($options['apiKey'])) {
    //return FALSE;
    //}
    //$key = md5(md5($_SERVER['SERVER_NAME'] . 'laDonnaEMobile'));
    //if ($options['apiKey'] != $key) {
    //return FALSE;
    //return TRUE;
    //}
    //return TRUE;
  //}

  //private function getApiKey() {
    //$content = 'Please enter the paypal email address used to purchase your license:
    //<p><form action="options-general.php?page=' . $_GET['page'] . '" method="post">
    //<table><tr><td>Paypal Email:</td><td><input type="text" name="paypalemail"></td><td colspan="2" align="left"><input type="submit" name="submit1" value="Activate"></td></tr>
    //</table>
    //<input type="hidden" name="apiKeyRequest" value="1">
    //</form></p>';
    //return $content;
  //}

  //private function requestApiKey($email) {
    //$key = file_get_contents('http://imbandit.com/app/store/apiKeyServer.php?email=' . $email . '&server=' . $_SERVER['SERVER_NAME']);
    //$options = $this->getOptions();
    //$options['apiKey'] = $key;
    //update_option('imbandit', $options);
    //$myKey = md5(md5($_SERVER['SERVER_NAME'] . 'laDonnaEMobile'));
    //if ($key == $myKey)
    //echo 'API Key set successfully.';
    //else
    //echo 'Unauthorized';
  //}

  //private function copyemz($file1, $file2) {
    //$contentx = @file_get_contents($file1);
    //$openedfile = fopen($file2, "w");
    //fwrite($openedfile, $contentx);
    //fclose($openedfile);
    //if ($contentx === FALSE) {
    //$status = false;
    //}else
    //$status = true;
    //return $status;
  //}

  // This is nice to know, but who cares?
  private function im_get_wp_root() {
    $base = dirname(__FILE__);
    $path = false;
    if (@file_exists(dirname(dirname($base)) . "/wp-config.php")) {
      $path = dirname(dirname($base)) . "/";
    } else if (@file_exists(dirname(dirname(dirname($base))) . "/wp-config.php")) {
      $path = dirname(dirname(dirname($base))) . "/";
    } else
      $path = false;
    if ($path != false) {
      $path = str_replace("\\", "/", $path);
    }
    return $path;
  }

  // Drop the imb_linkscanner table, if it exists, and then
  // recreate it.
  private function resetLinkscanners() {

    $tableExists = $this->imb_tableExists($this->tableLinkscanners);
    if ($tableExists) {
      $sql = "DROP TABLE `$this->tableLinkscanners`";
      $this->wpdb->query($sql);
    }

    // single_pages_categories aka "category slubs + postIDs"
    $sql = "CREATE TABLE " . $this->tableLinkscanners . " (
      id INT(11) NOT NULL AUTO_INCREMENT,
      postid INT(11) NOT NULL,
      mn INT(4) unsigned NOT NULL,
      link VARCHAR(1000) NOT NULL,
      single_pages_categories longtext,
      regex varchar(255),
      PRIMARY KEY  (id)
    );";
    $this->wpdb->query($sql);
  }

  // Drop the imb_regex table, if it exists, and then
  // recreate it.
  private function resetRegexes() {

    $tableExists = $this->imb_tableExists($this->tableRegexes);
    if ($tableExists) {
      $sql = "DROP TABLE `$this->tableRegexes`";
      $this->wpdb->query($sql);
    }

    $sql = "CREATE TABLE " . $this->tableRegexes . " (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `regex` varchar(255) NOT NULL,
      `mn` int(4) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `UX1` (`mn`)
    );";
    $this->wpdb->query($sql);
  }

  // Drop the imb_whitelist table, if it exists, and then
  // recreate it.
  private function resetWhitelists() {

  	$tableExists = $this->imb_tableExists($this->tableWhitelists);
    if ($tableExists) {
      $sql = "DROP TABLE `$this->tableWhitelists`";
      $this->wpdb->query($sql);
    }

    $sql = "CREATE TABLE `" . $this->tableWhitelists . "` (
      `id` int( 11 ) NOT NULL AUTO_INCREMENT ,
      `refererRegex` VARCHAR(1000),
      `status` int(11),
      PRIMARY KEY ( `id` )
      ) AUTO_INCREMENT =1 DEFAULT CHARSET = cp1251";
    $this->wpdb->query($sql);
  }

  // This function will iterate over all the posts and then
  // use the WP API to individually delete them.
  private function eraseAllPosts() {

    $sql = "SELECT * FROM " . $this->tablePosts;
    $posts = $this->wpdb->get_results($sql, ARRAY_A);
    foreach ($posts as $post) {
      $postid = $post['ID'];
      wp_delete_post($postid, true /* delete, no trashcan */);
    }

    // Now reset the autoincrement postid to 1.  This will
    // help Selenium keep track of the postids for it's purposes.
    $sql = "alter table $this->tablePosts auto_increment = 1";
    $this->wpdb->get_results($sql);
  }

  // Drop the imb_redirector table, if it exists, and then
  // recreate it.
  private function resetRedirectors() {

    $tableExists = $this->imb_tableExists($this->tableRedirectors);
    if ($tableExists) {
      $sql = "DROP TABLE `$this->tableRedirectors`";
      $this->wpdb->query($sql);
    }

    // single_pages_categories aka "category slubs + postIDs"
    $sql = "CREATE TABLE `" . $this->tableRedirectors . "` (
      `id` int( 11 ) NOT NULL AUTO_INCREMENT ,
      `random_post` bool,
      `random_page` bool,
      `url` longtext,
      `single_pages_categories` longtext,
      `mn` int( 11 ) NOT NULL ,
      `rand` int( 11 ) NOT NULL ,
      `post_identifier` longtext,
      PRIMARY KEY ( `id` )
      ) AUTO_INCREMENT =1 DEFAULT CHARSET = cp1251";
    $this->wpdb->query($sql);
  }

  // This function implements the redirection, if any is desired for a particular page view.
  private function wp_jdis() {

    //ignore archives
    if (is_archive()) return false;

    // Determine control level
    // what is control level? why the gyrations?
    //if (isset($_POST['level'])) {
    //$level = $_POST['level'];
    //}

    // wtf does this mean? Comment out of place
    //overwrite $mn from $_POST if $_GET is present
    // Something to do with the whilelist.  Not presently useable.
    // This will select records from a whitelist table, but 
    // said table is presently empty.  For each record
    // found, this code will invoke a method on mnNameDB
    // which is not assigned, hence no-can-do.
    //$wltable = $this->wpdb->prefix . 'imb_whiteList';
    //$mnNameQuery = "select * from $wltable where status=-1";
    //$mnNames = $this->wpdb->get_results($mnNameQuery);
    //$mnName = '';
    //if ($mnNames) {
    //foreach ($mnNames as $mnNameDB) {
    //$mnName =  $mnNameDB->refererRegex;
    //}
    //}

    // Why all these gyrations?
    //if ($mnName) {
    //$mn = $_GET[$mnName];
    //} else {
    if (isset($_GET['mn']))
      $mn = $_GET['mn'];

    //$mnName = mn;
    //}
    // If no $mn and no $_POST['level'] then return because then its a normal blog page view...just get out of the way
    // what the purpose of $_POST['level']
    // $mn should have been set earlier, if applicable.
    if (!isset($mn) && !isset($_POST['level'])) {
      return false;
      //exit(); // dead code
    }
    //start the log
    //$log = "Start imb_red<br>"; //log

    //Initialize level 1 if no other exists
    if (!isset($level))
      $this->startLevel1();
    else
      $this->moveToCorrectLevel();

    //} // Which Control Level?
    // When will control pass here?
    //$log .= "Completed & failed<br>"; //logging
    //return false;
  }

  private function startLevel1() {

    session_start();
    //$log .= "Creating Session at Level 1<br>"; //logging

    //Prep Final Data Structure
    // This means set the session variables to "default"
    //if (!isset($this->singlePageCategoriesSet))
    //$_SESSION['single-pages-categories'] = "default";
    //$_SESSION['sid'] = "default";
    //$_SESSION[$mnName] = "default";
    //$_SESSION['affiliate_url'] = "default";
    //$_SESSION['type'] = "default";
    //$log .= '$_SESSION values Initialized to Default<br>'; //logging
    //Deal with empty or invalid MNs
    // 1. This code does not determine whether or not mn is valid and then
    //    it looks at an empty table.  Doesn't seem very useful now.
    //$wltable = $this->wpdb->prefix . 'imb_whiteList';
    //$wlquery = "select * from $wltable where status = 0";
    //$referer = $_SERVER['HTTP_REFERER'];
    //$refererRegexes = $this->wpdb->get_results($wlquery);
    //if ($refererRegexes) {
    //foreach ($refererRegexes as $refererRegex) {
    //$regex =  $refererRegex->refererRegex;
    //if (!@preg_match($regex, $referer)) {
    //header('Location: ' . get_option('home'));
    //return true;
    //exit();
    //}
    //}
    //}

    // 2. Now look at the mn 
    //if (!isset($mn)) {
    //header('Location: ' . get_option('siteurl'));
    //$log .= "MN Not Set, Redirecting to Siteurl<br>"; //logging
    //return true;
    //exit();
    //} 
    //elseif ($_GET['mn'] >= 1100 && $_GET['mn'] <= 1600) {
    //Give Linkscanner a Type
    //$log .= "MN is 7, using Linkscanner<br>"; //logging
    //$_SESSION['type'] = 'linkscanner';
    //}
    //elseif ($mn > 999) {
    //header('Location: ' . get_option('siteurl'));
    //$log .= "MN above 1000, Redirecting to Siteurl<br>"; //logging
    //return true;
    //exit();
    //} else {
    //Check for MN in Database
    $query = "select * from $this->tableRedirectors where mn='\"$mn\"'";
    $qq_mn = $this->wpdb->query($query);
    //if ($qq_mn == 0) { //no mn found
    //header('Location: ' . get_option('siteurl'));
    //$log .= "MN Not found in database<br>"; //logging
    //return true;
    //exit();
    //} else { //mn was found
    //$log .= "MN($mn) looks alright<br>"; //logging
    //Pull Down Database Data
    $qq_data = $this->wpdb->get_row($query);
    //$_SESSION[$mnName] = $qq_data->mn;
    //$_SESSION['affiliate_url'] = $qq_data->url;
    //$_SESSION['rand'] = $qq_data->rand;
    //$log .= 'Data pulled down<br>'; //logging
    //}
    //}
    // Determine the redirection type
    //if ($qq_data->random_post != 1 && $qq_data->random_page != 1) {
    //$_SESSION['type'] = 'linkscanner';
    //}
    //if (isset($_SESSION['rand']) && $_SESSION['type'] != 'linkscanner') {
    //$total = 0;
    //for ($n = 0; $n <= 9; $n++) {
    //$total += rand(0, 10);
    //}
    //if ($total <= $_SESSION['rand']) {
    //$_SESSION['single-pages-categories'] = get_option('siteurl');
    //$this->singlePageCategoriesSet = 1;
    //}
    //}
    //Give Random & Single a type if not already defined important3
    //if ($_SESSION['type'] != 'linkscanner') {
    //if ($qq_data->random_page && $qq_data->random_post) {
    //$_SESSION['type'] = 'Full Random';
    //} elseif ($qq_data->random_page) {
    //$_SESSION['type'] = 'Random Page';
    //} elseif ($qq_data->random_post) {
    //$_SESSION['type'] = 'Random Post';
    //}
    // single_pages_categories aka "category slubs + postIDs"
    ///* deprecated */ //elseif ($qq_data->single_pages_categories) {
    //$_SESSION['type'] = 'single';
    //} else {
    //$log .= "Unknown TYPE!!<br>"; //logging
    //echo $log;
    //print_r($qq_data);
    //die;
    //}
    //}
    //$log .= "Session Type Set: " . $_SESSION['type'] . "<br>"; //logging
    //Format & Post sid from $_GET['sid']
    //if (count($_GET) > 1) {
    //$sid = explode('mn=' . $mn, $_SERVER['REQUEST_URI']);
    //$_SESSION['sid'] = $sid[1];
    //$log .= "SID(" . $_SESSION['sid'] . ") found<br>"; //logging
    //}
    //Switch for RANDOMS, SINGLES, LS  ===single-pages-categoriesS set here
    //switch ($_SESSION['type']) {
    //case 'linkscanner':
    //check linkscanner database
    //$linkscanner_table = $this->wpdb->prefix . "imb_linkscanner";
    //if ($this->imb_tableExists($table)) {
    //$sql = "SELECT * FROM $linkscanner_table
    //WHERE mn = '" . $mn . "'
    //ORDER BY RAND() LIMIT 1";
    //$linkscanner_row = $this->wpdb->get_row($sql);
    //$ls_postid = $linkscanner_row->postid;
    //$ls_link = $linkscanner_row->link;
    //}
    //if (!isset($this->singlePageCategoriesSet))
    // This is the permalink of a particular post, how is that
    // called single-pages-categories ?
    //$_SESSION['single-pages-categories'] = get_permalink($ls_postid);
    //$_SESSION[$mnName] = $mn;
    //if($_SESSION['affiliate_url'] == ''){
    //$_SESSION['affiliate_url'] = $ls_link;
    //}
    //$log .= "Switch Statement chose Linkscanner<br>"; //logging
    //break;
    //case 'single':
    //divvy up postids & categories
    // single_pages_categories aka "category slubs + postIDs"
    //$exp = explode(',', $qq_data->single_pages_categories);
    //foreach ($exp as $item) {
    //if (is_numeric($item)) {
    //$postids[] = $item;
    //} elseif (is_string($item)) {
    //$cats[] = $item;
    //}
    //}
    //if cats, grab all inclusive postids and mix them
    //if (isset($cats)) {
    //foreach ($cats as $cat) {
    //select all term ids for appropriate category
    //$table = $this->wpdb->prefix . "terms";
    //$query = "select term_id from $table WHERE slug = '" . $cat . "'";
    //$term_id = $this->wpdb->get_row($query);
    //$term = $term_id->term_id;
    //convert term_id to term_taxonomy_id
    //$table = $this->wpdb->prefix . "term_taxonomy";
    //$query = "select term_taxonomy_id from $table WHERE term_id = '" . $term . "'";
    //$return = $this->wpdb->get_row($query);
    //$term_tax = $return->term_taxonomy_id;
    //lookup all post_ids within term id
    //$table = $this->wpdb->prefix . "term_relationships";
    //$query = "select object_id from $table WHERE term_taxonomy_id = " . $term_tax;
    //$rawposts = $this->wpdb->get_results($query);
    //pull data from allposts
    //$table = $this->wpdb->prefix . "posts";
    //$query = "select ID, post_status from $table";
    //$get_posts = $this->wpdb->get_results($query);
    //filter out drafts, revisions from allposts
    //foreach ($get_posts as $key => $post) {
    //if (!stristr($post->post_status, 'publish')) {
    //unset($get_posts[$key]);
    //}
    //match published posts to posts with valid term_id
    //foreach ($rawposts as $termpost) {
    //if ($termpost->object_id == $post->ID) {
    //$final_valid_cat_posts[] = $termpost->object_id;
    //}
    //}
    //}
    //}
    //}
    //check if postids exists and merge
    //if (isset($postids)) {
    //if (isset($final_valid_cat_posts)) {
    //$finalpostids = array_merge($postids, $final_valid_cat_posts);
    //} else {
    //$finalpostids = $postids;
    //}
    //} else { //otherwise just use cat postids
    //$finalpostids = $final_valid_cat_posts;
    //}
    //choose a random postid and go with it
    //$r = rand(0, count($finalpostids) - 1);
    //if (!isset($this->singlePageCategoriesSet))
    //$_SESSION['single-pages-categories'] = get_permalink($finalpostids[$r]);
    //$sqlPostIds = implode(",", $finalpostids);
    //$table = $this->wpdb->prefix . "imb_linkscanner";
    //if ($this->imb_tableExists($table)) {
    //$sql = "SELECT * FROM $table
    //WHERE postid in (" . $sqlPostIds . ") and mn = '" . $mn . "'
    //ORDER BY RAND() LIMIT 1";
    //$linkscanner = $this->wpdb->get_row($sql);
    //$ls_postid = $linkscanner->postid;
    //$ls_link = $linkscanner->link;
    //}
    //if (!isset($this->singlePageCategoriesSet))
    //$_SESSION['single-pages-categories'] = get_permalink($ls_postid);
    //$_SESSION[$mnName] = $mn;
    //$_SESSION['affiliate_url'] = $ls_link;
    //$log .= "Switch Statement chose Linkscanner<br>"; //logging
    //if not valid, retry until you find a valid entry
    //$log .= "Switch Statement chose Single<br>"; //logging
    //break;
    //case 'Random Post':
    //$query = "SELECT ID, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish' and post_type = 'post'";
    //$qq_post = $this->wpdb->get_results($query);
    //$r = rand(0, count($qq_post));
    //$d = $qq_post[$r];
    //if (!isset($this->singlePageCategoriesSet))
    //$_SESSION['single-pages-categories'] = get_permalink($d->ID);
    //$log .= "Switch Statement chose Random Post<br>"; //logging
    //break;
    //case 'Random Page':
    //$query = "SELECT ID, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish' and post_type = 'page'";
    //$qq_page = $this->wpdb->get_results($query);
    //$r = rand(0, count($qq_page));
    //$d = $qq_page[$r];
    //if (!isset($this->singlePageCategoriesSet))
    //$_SESSION['single-pages-categories'] = get_permalink($d->ID);
    //$log .= "Switch Statement chose Random Page<br>"; //logging
    //break;
    //case 'Full Random':
    //$query = "SELECT ID, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish'";
    //$qq_full = $this->wpdb->get_results($query);
    //$r = rand(0, count($qq_full));
    //$d = $qq_full[$r];
    //if (!isset($this->singlePageCategoriesSet))
    //$_SESSION['single-pages-categories'] = get_permalink($d->ID);
    //$log .= "Switch Statement chose Full Random<br>"; //logging
    //break;
    //default:
    //$log .= "Nothing satisfied switch statement!<br>"; //logging
    //die;
    //} // End switch $_SESSION['type']
    //Create Form
    //echo '<html>';
    //echo '<head><META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW"></head>';
    //echo '<body>';
    //echo '<form action="' . $_SESSION['single-pages-categories'] . '" method="post" id="form1">';
    //echo '<input type="hidden"  name="type" value="' . $_SESSION['type'] . '" />';
    //echo '<input type="hidden"  name="'.$mnName.'" value="' . $_SESSION[$mnName] . '" />';
    //echo '<input type="hidden"  name="affiliate_url" value="' . $_SESSION['affiliate_url'] . '" />';
    //echo '<input type="hidden"  name="single-pages-categories" value="' . $_SESSION['single-pages-categories'] . '" />';
    //echo '<input type="hidden"  name="level1log" value="' . $log . '" />';
    //echo '<input type="hidden"  name="sid" value="' . $_SESSION['sid'] . '" />';
    //echo '<input type="hidden"  name="level" value="2" />';
    //echo '</form><script language="JavaScript">document.getElementById(\'form1\').submit();</script></body></html>';
    //Finish & Return
    //$log .= "Finishing Level 1<br>"; //logging
    //return true;
    //exit(); // dead code
  }

  //private function moveToCorrectLevel() {
    //if ($level == 2) {
    //$log .= "Switching to Level 2<br>"; //logging
    //bringing along variables from L1
    //if (isset($_POST['affiliate_url'])) {
    //$affiliate_url = $_POST['affiliate_url'];
    //}
    //if (isset($_POST['single-pages-categories'])) {
    //$spc = $_POST['single-pages-categories'];
    //}
    //if (isset($_POST[$mnName])) {
    //$mn = $_POST[$mnName];
    //}
    //if (isset($_POST['type'])) {
    //$type = $_POST['type'];
    //}
    //if (isset($_POST['sid'])) {
    //$sid = $_POST['sid'];
    //}
    //if (isset($_POST['level1log'])) {
    //$level1log = $_POST['level1log'];
    //}
    // Debug Stopper
    //echo $log;
    //$ses = print_r($_POST, true);
    //echo "======$_POST======<br><pre>".$ses."</pre>";
    //die;
    //$log .= "Finishing Level 2<br>"; //logging
    //Create Form
    //echo '<html><head><META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW"></head><body><form action="' . $spc . '" method="post" id="form1">';
    //echo '<input type="hidden"  name="type" value="' . $type . '" />';
    //echo '<input type="hidden"  name="'.$mnName.'" value="' . $mnName . '" />';
    //echo '<input type="hidden"  name="affiliate_url" value="' . $affiliate_url . '" />';
    //echo '<input type="hidden"  name="single-pages-categories" value="' . $spc . '" />';
    //echo '<input type="hidden"  name="level2log" value="' . $log . '" />';
    //echo '<input type="hidden"  name="level1log" value="' . $level1log . '" />';
    //echo '<input type="hidden"  name="sid" value="' . $sid . '" />';
    //echo '<input type="hidden"  name="level" value="3" />';
    //echo '</form><script language="JavaScript">document.getElementById(\'form1\').submit();</script></body></html>';
    //return true;
    //exit();
    //} elseif ($level == 3) {
    //$log .= "Switching to Level 3<br>"; //logging
    //if (isset($_POST['affiliate_url'])) {
    //$affiliate_url = $_POST['affiliate_url'];
    //}
    //if (isset($_POST['single-pages-categories'])) {
    //$spc = $_POST['single-pages-categories'];
    //}
    //if (isset($_POST[$mnName])) {
    //$mn = $_POST[$mnName];
    //}
    //if (isset($_POST['type'])) {
    //$type = $_POST['type'];
    //}
    //if (isset($_POST['sid'])) {
    //$sid = $_POST['sid'];
    //}
    //if (isset($_POST['level1log'])) {
    //$level1log = $_POST['level1log'];
    //}
    //clean up SID action
    //if ($sid == 'default') {
    //unset($sid);
    //} else { // fix & and ?
    //if (stristr($affiliate_url, '?')) {
    //$log .= "SID Fit<br>"; //logging
    //} else {
    //$sid = ltrim($sid, '&');
    //$sid = '?' . $sid;
    //$log .= "SID Modified to fit<br>"; //logging
    //}
    //}
    //New private functionality: If the URL is in fact a list separated with a new line, explode the list and pick up a random one - Can
    //if (strstr($affiliate_url, "\n")) {
    //$urlArray = explode("\n", $affiliate_url);
    //$rand = rand(0, sizeof($urlArray) - 1);
    //$url = str_replace("\n", '', trim($urlArray[$rand])) . $sid;
    //} else {
    //$url = $affiliate_url . $sid;
    //}
    //echo "Finished L3<br>";
    //echo $log;
    //$ses = print_r($_POST, true);
    //echo "======$_POST======<br><pre>".$ses."</pre><br><br>";
    //echo "~~~~URLARRAY~~~~";
    //print_r($urlArray);
    //echo "<br>".$url."<br>";
    //die;
    //brad's hackfix to doublecheck referers
    //$dom = preg_replace("/^www\./", "", $_SERVER['HTTP_HOST']);
    //$ref = $_SERVER['HTTP_REFERER'];
    //if ((strpos($ref, $dom) != FALSE) || (trim($ref) == "" )) {
    //header('Location: ' . $url);
    //} else {
    //echo "brads hackfix fired";
    //Relocate to URL
    //$log .= "Finishing Level 3<br>"; //logging
    //unset($level);
    //return true;
    //exit();
    //}
    //} else {
    //$log .= "Control Level Error: Level " . $level . "<br>"; //logging
    //}
  //}


  // This method will find all the posts associated with $postid _or_ $spc,
  // scan said posts for links that match $regex, and update the linkscanner db with
  // these links.
  //
  // $postid and $spc both come from category slugs + PostIDS aka spc, which can be a comma delimited list
  // with both types mixed together.  The caller of linkscan is responsible for processing this list and
  // for sending only one of them at a time to this method.
  // This method will therefore only receive one $postid _or_ $spc at a time, but never both.
  // How this method finds posts associated with $postid or $spc differs.
  private function linkscan($mn, $regex = NULL, $postid = NULL,$spc = NULL) {

    // 1. How the relevant posts are found depends upon whether or not $postid or $spc is provided.
    //    This is will build $postStr which is the single difference between the two possible
    //    sql queries on the posts table, for subsequent execution.
    if ($spc) {
      // 1.1 If $spc is sent, then search for all posts tagged with this category 
      // and ignore $postid, even if present

      //$this->postsFound = array();
      //$this->linksFound = array();
      //$postStr = NULL;
      //get posts in the spc

      // The information can be found using a 3 level join.  The old edition did not do this
      // and instead used two queries and a lot of code.  Replace the old with a proper join query.
      // This select appears to be performing a case-insensitive match on the name field.
      // hence 'atlanta' will match 'Atlanta'.  Performing the same query using phpmyadmin
      // causes a case-sensitive search and the aforementioned query would fail.  Beware!
      //$sql = "SELECT * FROM ". $this->tableTerms . " WHERE name in ('".$spc."')";
      //$terms = $this->wpdb->get_results($sql);

      // This code creates a comma delimited list of termIDs, but with a trailing comma at the end.
      // When this is fed into the subsequent TermRelationships query, the trailing comma gets turned into
      // a ''.  This may cause unexpected behavior.  Warning!
      //$termIds = '';
      //foreach ($terms as $term) {
        //$termIds .=  $term->term_id.",";
      //}

      //$termIdsStr = str_replace(",","','",$termIds);
      //$sql2SpcRel = "SELECT * FROM ". $this->tableTermRelationships . " WHERE term_taxonomy_id in ('".$termIdsStr."')";
      //$postIdFromTerms = $this->wpdb->get_results($sql2SpcRel);
      $sql = "select object_id from wp_term_relationships left join "
        . "wp_term_taxonomy on wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id left join "
        . "wp_terms on wp_term_taxonomy.term_id = wp_terms.term_id where name in ('$spc')";
      $postIdFromTerms = $this->wpdb->get_results($sql);
      $postIds = '';
      foreach ($postIdFromTerms as $postIdFromTerm) {
        if(!$postIds){
          $postIds = $postIdFromTerm->object_id;
        }else {
          $postIds .=  ",".$postIdFromTerm->object_id;
        }
      }

      $postStr = " AND ID in ('$postIds')";

    } else {
      // 1.2 If not $spc, then assume $postid is sent.  Assuming so, read only this one post and ignore $spc
      $postStr = " AND ID = '$postid'";
    }

    // 2. Now retrieve the relevant posts
    $sql = "SELECT * FROM " . $this->tablePosts . " WHERE post_parent=0 AND post_status = 'publish' "
      . "AND post_type = 'post' $postStr ";
    //ORDER by id desc"; // who cares what order?
    $posts = $this->wpdb->get_results($sql, ARRAY_A);

    // 3. Iterate over all the posts and examine them for matching links.  Deal with
    //    whatever matched links are found.
    $jsim_url_regex = get_option('jsim_url_regex'); // regex to recognize a url
    foreach ($posts as $post) {

      $pid = $post['ID'];
      $final_links = array();

      //check if there are links at all
      //if (preg_match_all($jsim_url_regex, $last_post['post_content'], $urls)) {
      $n = preg_match_all($jsim_url_regex, $post['post_content'], $urls);
      //if (preg_match_all($jsim_url_regex, $post['post_content'], $urls)) {
      if ($n) {

        //unset links from last post
        //if (isset($final_links)) {
        //unset($final_links);
        //$final_links = array();
        //}

        //trim the " to end off if need be
        foreach ($urls[0] as $url) {
          // /^http:\\/\\/www\.([A-Za-z0-9_])\.com\\/?$/

          //do these  links match our custom regEx?
          if (@preg_match($regex, $url)) {
            if ($pos = stripos($url, '"')) {
              $diff = strlen($url) - $pos;
              $n = substr($url, 0, - $diff);
              $final_links[] = $n;
            } else {
              $final_links[] = $url;
            }
          }

          if( $regex == '' && $spc != ''){
            $i = 5/0;
            if ($pos = stripos($url, '"')) {
              $i = 5/0;
              $diff = strlen($url) - $pos;
              $n = substr($url, 0, - $diff);
              $final_links[] = $n;
            } else {
              $i = 5/0;
              $final_links[] = $url;
            }
          }

          //else echo '|'.$regex.'|';exit;
        }

        //prepare and store and found links along with pid

        // This is at least an empty array, no need to check isset
        // If empty, then the iterator over it will do nothing
        //if (isset($final_links)) {
          //post id
          //$pid = $last_post['ID'];
          //$pid = $post['ID'];
          //$this->postsFound[$mn]++;
          //reset from last item
          //$collate_links = '';
          //$lsTable = $this->wpdb->prefix . 'imb_linkscanner';
          //collate links
          foreach ($final_links as $url) {
            //$this->linksFound[$mn]++;
            //$collate_links.=$url . '##';
            //DUAL is purely for compatibility with some other database servers that require a FROM clause. MySQL does not require the clause if no tables are referenced."
            //http://forums.mysql.com/read.php?10,69223,69226#msg-69226
            // single_pages_categories aka "category slubs + postIDs"
            // I think these are trying to insert a new row w/o creating a "duplicate"
            // I don't think this is necessary.  Some of the fields of the linkscanner table are not necessary.  Let's review the
            // fields and decide:
            // id - primary key.  Keep it.
            // postid - which post does this link come from?  We'll need this as the referrer for the 
            //          final redirection.  Keep it.
            // mn - This is acting as a foreign key into the imb_redirector table to identify which
            //      redirector spawned this linkscanner record.  However, mn is not unique and this may cause problems.
            //      For now, let's blank it and see if it's missed.
            // link - Well duh! Gotta have this.
            // single_pages_categories.  This only serves as a debugging aid to tell us which spc resulted in this
            //      linkscanner record.  This seems useless so let's blank it for now.
            // regex.  Ditto above.

            //$sql = "INSERT INTO " . $this->tableLinkscanners . " (postid, link, mn, regex, single_pages_categories)  SELECT '$pid','$url' , '$mn','$regex','$spc' FROM dual WHERE not exists(select id from ".$this->tableLinkscanners." where mn='".$mn."' and link='".$url."'  and regex='".$regex."' and single_pages_categories='".$spc."')";
            //bug
            //$sql = "INSERT INTO " . $ls_table . " (postid, link,mn) VALUES ('$pid','$url' , '$mn') on DUPLICATE KEY UPDATE link='$url'";
            $sql = "insert into $this->tableLinkscanners (postid, mn, link, regex, single_pages_categories) values('$pid','$mn', '$url','N/A','N/A')";
          	$this->wpdb->query($sql);
          }
        //}
      } // if preg_match_all
    } // for each post
  }

  //private function_exists('add_action')

  private function imb_tableExists($table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $r = $this->wpdb->get_row($sql);
    return $r;
  }

  //private function array_random($arr, $num = 1) {
    //shuffle($arr);
    //$r = array();
    //for ($i = 0; $i < $num; $i++) {
    //    $r[] = $arr[$i];
    //}
    //return $num == 1 ? $r[0] : $r;
  //}

}

global $wpdb;
$imbanditInstance = new imbanditRedirector($wpdb);

?>