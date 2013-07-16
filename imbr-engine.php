<?php

require("admin_row.php");

class imbanditRedirector {

  public $imbV = 'v297.2';

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
    add_action('publish_post', array(&$this, 'ls_singlepost_scan'));
    add_action('admin_menu', array(&$this, 'prc_add_options_to_admin'));
    add_action('wp_head', array(&$this, 'wp_add_red'));
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
  private function red_editor_save() {

    // 1. First delete everything from imb_linkscanner because we're 
    //    going to rebuild it from scratch.
    $this->wpdb->query("delete from ".$this->tableLinkscanners);

    // 2. Deal with the existing redirectors.

    // For each redirector, there are 3 interesting fields to deal with...
    // A. category slugs + postIDs aka single_pages_categories
    // B. linkscanner urls            aka ls_regex_new
    // C. manual url targets          aka post_aff_url
    // The following sections will leap to life and update the imb_linkscanner table given some combination of these fields

    // The number of existing redirectors
    $redirectorCnt = $_POST['redirectorCnt'];

    // 2.1 Iterate over every existing redirector
    for ($i = 1; $i <= $redirectorCnt; $i++) {

      // 2.1.1 Determine the indexed POST parameter names
    	$url = "post_aff_url_" . $i;              // aka manual url targets
      $rpost = "random_post_" . $i;
      $rpage = "random_page_" . $i;
      $spc = "single_pages_categories_" . $i;   // aka "category slubs + postIDs"
      $mn = "mn_" . $i;
      $id = "id_" . $i;
      $rand = "rand_" . $i;
      //$select = "select_" . $i;
      $ls_regex_new = "ls_regex_new_" . $i;     // aka linkscanner urls

      // 2.1.2 Update linkscanner set to within a selection of categories
      // Only trigger if category slugs + post ids has a value, and the other two fields do not.
      if ( $_POST[$ls_regex_new] == '' && $_POST[$spc] != '' && $_POST[$url] == '') {
        $spcs = explode(',', $_POST[$spc]);
        foreach ($spcs as $spcItem) {
          $this->ls_scan_all($_POST[$mn], NULL, NULL,$spcItem);
        }
      }

      // 2.1.3 --------New LS Regexs----------------------------------------------------
      //$regexTable = $this->wpdb->prefix . 'imb_regex';
      // ls_regex_new aka linkscanner urls
      // Only trigger if linkscanner URLs has a value, regardless of the values of the other two fields
      $regexArray = explode("\n", $_POST[$ls_regex_new]);
      foreach ($regexArray as $newRegex) {
        $newRegex = trim($newRegex);
        if ($newRegex == NULL)
          continue;

        //$encoded = base64_encode($newRegex);
        //$sql = "INSERT INTO " . $regexTable . " (regex, mn) VALUES ('$encoded','$_POST[$mn]') on DUPLICATE KEY UPDATE regex='$encoded'";
        //$this->wpdb->query($sql);
        $spcs = explode(',', $_POST[$spc]);
        foreach ($spcs as $spcItem) {
          $this->ls_scan_all($_POST[$mn], $newRegex, NULL,$spcItem);
        }
      }

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
      $query = "update $this->tableRedirectors set url = '" . $_POST[$url]
        //. "', random_post = '" . $rpost2 
        //. "', random_page = '" . $rpage2 
        . "', single_pages_categories = '" . $_POST[$spc]
        . "', mn = '" . $_POST[$mn] 
        //. "', rand = '" . $_POST[$rand] 
        . "' where id = '" . $_POST[$id] . "'";
      $this->wpdb->query($query);
      //}
    } // iterate over the existing redirectors

    
    // 3. Now detect if a new redirector should be created.
    //    post_aff_url aka manual url targets.
    if (isset($_POST['post_aff_url'])) {

      // Assuming so, clean up the other parameters
      // single_pages_categories aka "category slubs + postIDs"
      if (!isset($_POST['single_pages_categories']))
        $_POST['single_pages_categories'] = NULL;

      if (isset($_POST['random_post']))
        $rand_post = 1;
      else
        $rand_post = 0;

      if (isset($_POST['random_page']))
        $rand_page = 1;
      else
        $rand_page = 0;

      // Only need $mn, not $mn_upd
      $mn = $_POST['mn_upd'];

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
        $query = "INSERT INTO $this->tableRedirectors set url = '"  . $_POST['post_aff_url'] 
          . "', single_pages_categories = '" . $_POST['single_pages_categories'] 
          . "', mn ='" . $mn
          . "', random_post = '" . $rand_post 
          . "', random_page = '" . $rand_page . "' ";
        $this->wpdb->query($query);
      }

      // This is the mn for the new redirector.  Why a 2nd variable name?  Reuse $mn instead.
      $mn4LS = $_POST['mn_upd'];

      // post_aff_url aka manual url targets
      // single_pages_categories aka "category slubs + postIDs"
      //$_POST['post_aff_url'] = false;
      //$_POST['single_pages_categories'] = false;
      //$_POST['mn_upd'] = false;
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
      //$this->ls_scan_all($mn4LS, NULL, NULL,$spcItem);
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

        // base64_encoded version of the newRegex
        //$encoded = base64_encode($newRegex);
        $newRegexB64 = base64_encode($newRegex);

        // $mn is already set with the value we want.  No need for a 2nd variable name.
        //$mn = $mn4LS;

        $sql = "INSERT INTO " . $this->tableRegexes . " (regex, mn) VALUES ('$newRegexB64','$mn') on DUPLICATE KEY UPDATE regex='$newRegexB64'";
        $this->wpdb->query($sql);

        $spcs = explode(',', $spc4OldRegex);
        foreach ($spcs as $spcItem) {
          $this->ls_scan_all($mn, $newRegex, NULL,$spcItem);
        }
      }
    }

    // 4. -----------------------insert white list ---------------------------
    // This is not relevant to any particular row.  But perhaps it's out of place here?
    // whitelist may be 'set' but '' also.  If that's the case then no changes to the db occur.
    // warning: this code does not check for set whitelist.  Should it ?
    // This is not part of adding a new redirector record, the whitelist applies globally 
    //$whiteListTable = $this->wpdb->prefix . 'imb_whiteList';
    $refererRegexArray = explode("\n", $_POST['whiteList']);
    $delWLSql = "delete from $this->tableWhitelists";
    $this->wpdb->query($delWLSql);
    foreach ($refererRegexArray as $refererRegex) {
      $refererRegex = trim($refererRegex);
      if ($refererRegex == NULL)
        continue;
      $i = 5/0;
      //$sql = "INSERT INTO " . $whiteListTable . " (refererRegex,status) VALUES ('$refererRegex',0) on DUPLICATE KEY UPDATE refererRegex='$refererRegex'";
      //$this->wpdb->query($sql);
    }

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

    // In the event this function has been called via a POST request,
    // look for certain commands as POST parameters and execute them first,
    // and then continue with the ordinary display of the admin screen.
    // The if-else structure ensures that only one option will 
    // run and only the first option that passes, even if
    //other options might also pass.

    // Delete a specific redirector
    if (isset($_POST['delete_redirector'])) {
      //$query = "delete from $this->tableRedirectors where mn = '$_POST['delete_redirector']'";
      $redirector = $_POST['delete_redirector'];
      $query = "delete from $this->tableRedirectors where mn = '$redirector'";
    	$this->wpdb->query($query);

    } else if (isset($_POST['redirector_databaseclear'])) {
      $this->resetDatabase();

    // This parameter value cannot be set and thus this branch of code
    // is never used.
    //} elseif (isset($_POST['linkscanner_databaseclear'])) {
    //  //$this->ls_init('delete');

    } else if (isset($_POST['update'])) {
      // If control makes it here then we surmise that the user has performed any combination of:
      // 1. Changed something about the existing redirector records
      // 2. Created a new redirector
      // 3. Modified the whitelist
      // 4. Modified the mn parameters
      // ... and is saving the changes.
      $this->red_editor_save();
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

  private function imb_red_editor_center_contents() {

    // single_pages_categories aka "category slubs + postIDs"
    //if (isset($_POST['single_pages_categories']))
    //$spc4OldRegex = $_POST['single_pages_categories'];
    //if (isset($_POST['apiKeyRequest'])) {
    //$this->requestApiKey($_POST['paypalemail']);
    //}

    // is this a method of tracing?
    //echo $this->im_get_wp_root();
    //$n = $this->im_get_wp_root();
    //echo $n;

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

    // 3. Determine the table name for imb_redirector
    //$table = $this->wpdb->prefix . 'imb_redirector';

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

    // This is a method to store information about column headers so that
    // they may be subsequently enumerated and emitted as html.  It looks
    // like it might be some method of selecting which columns
    // should display, at runtime.  This is not used.
    //-------------------------Prepare Column Headers for Table-----------------------
    //$posts_columns = array(
    //'URL' => __('Affiliate URLs (One per line)'),
    //'post URL' => __('Random Referrers OR Specific and Category Referrers'),
    //    'Random MP' => __('Homepage %'),
    //'MN' => __('MN'),
    //'url' => __('url'),
    //'DEL' => __('Delete')
    //);
    //$posts_columns = apply_filters('manage_posts_columns', $posts_columns);

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

    // 5. The bulk of the admin section is displayed in a table.
    //    Start this table now.
    echo "<table class=\"widefat the_table\" style=\"width:1000px;\">";

    // This form will contain all the inputs for the table, as well
    // as the submit button in thecenter div.  This distribution 
    // is admittedly tedious, but it's what I have to work with now.
    //echo "<form action=\"options-general.php?page=<qqqphp echo $_GET['page']; QQQ>\" method=\"post\">";
    $page = $_GET['page'];
    echo "<form action=\"options-general.php?page=$page\" method=\"post\">";

    // 6. Now output the column headers in the table header.  Just hardwire the headers, don't bother
    //    enumerating over the elements of a data structure.  That's too comlicated!
    //-------------------------Output Column Headers--------------------------------
    echo "<thead>";
    echo   "<tr>";
    echo     "<th>Custom Referrers</th>";
    echo     "<th>&nbsp;</th>";
    echo     "<th>Redirection URLs</th>";
    echo     "<th>Link Scanner Found Targets</th>";
    echo     "<th>Other Controls</th>";

    // This is the obsolete/never completed "iterate over the headers" method.
    //foreach ($posts_columns as $column_display_name) {
    //  echo '<th>';
    //  echo $column_display_name . '</th>';
    //} //$posts_columns as $column_display_name
    echo   "</tr>";
    echo "</thead>";

    // 7. Now output the table body
    //echo "<tbody>";  // id=\"the-list\"
    //echo "<tbody>";  // id=\"the-list\"
    $tbody = "<tbody>";

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

    //exit(var_dump($this->wpdb->last_query));
    $i = 1; // index of populated rows
    $mns = '';

    // 7.1 ----------------Load Existing Redirectors---------------------------------------------
    //if ($posts_post) {
    if ($redirectors) { // Test for the existence of any redirectors.

      // If there are redirectors, then iterate over them all
      // and emit a table-row to display them.
      //foreach ($posts_post as $post_post) {
      foreach ($redirectors as $redirector) {

        $adminRow = new AdminRow();
        //echo "<tr>";

        // Can this be a list?
        //$aff_url = $post_post->url;
        $aff_url = $redirector->url;

        //$single_pages_categories = $post_post->single_pages_categories;
        // single_pages_categories aka "category slubs + postIDs"
        $single_pages_categories = $redirector->single_pages_categories;

        //$mns[$i] = $post_post->mn;
        $mns[$i] = $redirector->mn;

        $rpost = "";
        //if ($post_post->random_post == 1) {
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

        // Plucked from redirector table field.
        // single_pages_categories aka "category slubs + postIDs"
        $adminRow->cat_slug_ids_div = "<input value=\"$single_pages_categories\" id=\"single_pages_categories_$i\" name=\"single_pages_categories_$i\" onkeyup=\"disableCheckboxes(this.value , $i);\">";
        //echo "</div>"; // cat_slug_ids_div

        //echo "<div>"; // div ???
        //echo "<div>"; // class="random_referrers_div"
        // echo "<div>(C)</div>" // class="letter_label" 
        //  . "<strong>Random Referrers</strong>";

        // Plucked from redirector table field.
        // single_pages_categories aka "category slubs + postIDs"
        // what is the relevance of that here?
        $adminRow->random_referrers_div ="<label for=\"random_post_$i\">"
          //<input id="random_post_<qqqphp echo $i; QQQ>" name="random_post_<qqqphp echo $i; QQQ>" type="checkbox" <qqqphp echo $rpost;QQQ> <qqqphp echo $single_pages_categories != NULL ? ' disabled' : ''; QQQ> onchange="disableTextbox(<qqqphp echo $i; QQQ>);">
          . "<input id=\"random_post_$i\" name=\"random_post_$i\" type=\"checkbox\" $rpost >"
          . "Random Posts"
          . "</label>"

          . "<label for=\"random_page_$i\">"
          //<input id="random_page_<qqqphp echo $i; QQQ>" name="random_page_<qqqphp echo $i; QQQ>" type="checkbox" <qqqphp echo $rpage; QQQ> <qqqphp echo $single_pages_categories != NULL ? ' disabled' : ''; QQQ>onchange="disableTextbox(<qqqphp echo $i; QQQ>);">
          . "<input id=\"random_page_$i\" name=\"random_page_$i\" type=\"checkbox\" $rpage >"
          . "Random Pages"
          . "</label>";
        //echo "</div>"; // random_referrers_div
        //echo "</div>"; // div ???

        //echo "</td>"; // custom_ref_td

        //echo "<td>"; // 2. class="redirection_td"
        //$adminRow->redirection_td = "<div class=\"link_scanner_div\">"
        //  . "<div class=\"letter_label\">(A)</div>"
        //  . "<strong>Linkscanner URLs</strong>"
        //  . "<br>"
        //  . "<small>/Regex/ Scans for existing links</small>"
        //  . "<br>"

        // Deal with regexTable
        $regexTable = $this->wpdb->prefix . 'imb_regex';
        //add where mn
        $sql = "SELECT * FROM " . $regexTable
          . " where mn=" . $mns[$i];
          //" ORDER BY id ";
        $regexes = $this->wpdb->get_results($sql, ARRAY_A);
        $countLink = 0;
        $showAllLinkText = '';

          // Substantial duplication within these two branches...
          // The duplicated code computes $linkText and $countLink 
          // for subsequent use.  It's somewhat out of place here.
          if (count($regexes) == 0) {

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
          } else {
            foreach ($regexes as $regexItem) {

              // DUP1 - Find all records in linkscanner table where the spc is
              // included in the redirector table, the mn is the same, and regex is
              // the same.
              $lsTable = $this->wpdb->prefix . 'imb_linkscanner';
              // single_pages_categories aka "category slubs + postIDs"
              $spcStr = str_replace(",","','",$single_pages_categories);

              //link scanner sql
              $regexItemB64 = base64_decode($regexItem['regex']);
              $sql = "SELECT * FROM " . $lsTable 
                . " WHERE mn = '" . $regexItem['mn'] 
                . "' and single_pages_categories in ('".$spcStr."') and "
                . "regex='".$regexItemB64."' ORDER BY RAND()  ";
              $links = $this->wpdb->get_results($sql, ARRAY_A);
              // /DUP1

              if ($regexItemB64) {
                // ls_regex_new aka linkscanner urls
                //echo '<input name="ls_regex_new_' . $i . '" id="ls_regex_new_' . $i . '" value="' . base64_decode($regexItem['regex']) . '" type="text"></input>';
                $adminRow->link_scanner_div = "<input name=\"ls_regex_new_$i\" id=\"ls_regex_new_\$i\" value=\"$regexItemB64\" type=\"text\"></input>";
              } else {
                // ls_regex_new aka linkscanner urls
              	//echo '<input name="ls_regex_new_' . $i . '" id="ls_regex_new_' . $i . '"  type="text" ></input>';
                $adminRow->link_scanner_div = "<input name=\"ls_regex_new_$i\" id=\"ls_regex_new_\$i\"  type=\"text\" ></input>";
              }

              // unused
              //echo '<table><tr><td>' . base64_decode($regexItem['regex']) . '</td><td><input type="button" onClick="alert_url(\'http:\/\/' . $_SERVER['HTTP_HOST'] . $co . '/?mn=' . $regexItem['mn'] . '\')" value="url"/></td><td> <input type="button" id="deletebutton1" name="deletebutton1" onclick="a=confirm(\'Are you sure?\');if(a == true) top.location=\'options-general.php?action=deleteRegex&regexid=' . $regexItem['id'] . '&page=' . $_GET['page'] . '\';" value="delete"></td></tr></table>';
              //echo "Posts Found:" . count($links);

              // DUP2
              foreach ($links as $link) {
                $linkText = (strlen($link['link']) >= 38) ? substr($link['link'], 0, 38)  : $link['link'];
                //echo '<a href="' . $link['link'] . '" target="_new">' . $linkText . '</a>  ';
                $showAllLinkText .= $linkText.'&#13;';
                $countLink++;
              }
              // /DUP2

              // unused
              //if (isset($this->postsFound[$link['mn']]))
              //echo '<br/>';
              //if (isset($this->postsFound[$link['mn']]))
              //echo ' Posts Found: ' . $this->postsFound[$link['mn']];
              //if (isset($linksFound[$link['mn']]))
              //echo ' Links Found: ' . $this->linksFound[$link['mn']];

            } // for each regex item
          } // count($regexes) == 0
          // end regexTable stuff

          //</div> <!-- link_scanner_div -->
          //<div class="manual_url_targets_div">
          //<div class="letter_label">(BC)</div>
          //<strong>Manual URL Targets</strong>

          // Plucked from a redirector table field.  Can this be a list?
          //<textarea name="post_aff_url_<qqqphp echo $i; QQQ>" type="text-area" id="post_aff_url<qqqphp echo $i; QQQ>" ><qqqphp echo $aff_url; QQQ></textarea>
          $adminRow->manual_url_targets_div = "<textarea name=\"post_aff_url_$i\" type=\"text-area\" id=\"post_aff_url$i\" >$aff_url</textarea>";
          //</div> <!-- manual_url_targets_div -->

          //$co = explode('wp-admin', $_SERVER['REQUEST_URI']);
          //$co = substr($co[0], 0, -1); //jared modified negative offset to -1, it used to be -2
          //echo '<div><input name="" type="text" id="" value=""/>';
          //echo "</td>"; // redirection_td

          //echo "<td>"; // 3. class="linkscanner_td"
          //echo "<div>(A)</div>" //  class="letter_label"
          // Computed earlier
          $adminRow->linkscanner_td = "<textarea>$showAllLinkText</textarea>"
            . $countLink . " Posts";
          //echo "</td>"; // linkscanner_td

          //$r = "";
          //$r .= "<td>"; // 4. class="other_controls_td"

          // echo "<div>" // class="homepage_div"
          $n = $redirector->rand;
          $adminRow->homepage_div = "<label>"
            . "Homepage %"
            //<input name="rand_<qqqphp echo $i; QQQ>" type="text" id="rand_<qqqphp echo $i; QQQ>" value="<qqqphp echo $post_post->rand; QQQ>" size="3"/>
            . "<input name=\"rand_$i\" type=\"text\" id=\"rand_$i\" value=\"$n\"  size=\"3\"/>"
            . "</label>";
          //. "</div>";  // homepage_div

          //<div class="mn_numbers_div">
          $adminRow->mn_numbers_div = "<label>"
            . "ID"
            //<input name="mn_<qqqphp echo $i; QQQ>" type="text" id="mn_<qqqphp echo $i; QQQ>" value="<qqqphp echo $mns[$i]; QQQ>" size="3"/>
            . "<input name=\"mn_$i\" type=\"text\" id=\"mn_$i\" value=\"$mns[$i]\" size=\"3\"/>"
            . "</label>"
            . "<input name=\"id_$i\" type=\"hidden\" id=\"id_$i\" value=\"$redirector->id\"/>";

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

          $http_host = $_SERVER['HTTP_HOST'];
          // todo fix $mnName and $co
          $adminRow->other_url_div = "<input class=\"c_button_link\" type=\"button\" onclick=\"alert_url('http:\/\/$http_host/?mn=$mns[$i]')\" value=\"url\" />";
          //</div> <!-- other_url_div -->

          //<div class="other_delete_div">
          //<button class="c_button_link" onclick="if(!confirm('Delete This Entry?')){ return false; };" name="select_<qqqphp echo $i; QQQ>" id="select_<qqqphp echo $i; QQQ>" value="delete">delete</button>
          //$adminRow->other_delete_div = "<button class=\"c_button_link\" onclick=\"if(!confirm('Delete This Entry?')){ return false; };\" name=\"select_$i\" id=\"select_$i\" value=\"delete\">delete</button>";
          $adminRow->other_delete_div = "<button "
            . "class=\"c_button_link\" "
            . "onclick=\"if(!confirm('Delete This Entry?')){ return false; };\" "
            . "name=\"delete_redirector\" "
            //. "id=\"select_$i\" "
            . "value=\"$mns[$i]\">delete</button>";
          //</div> <!-- other_delete_div -->

          //$r .= "</td>"; // other_controls_td
          //echo $adminRow->getHTML();
          $tbody .= $adminRow->getHTML();
          //	echo $n;
          //echo "</tr>";
          $i++;
          //unset($rpost);
          //unset($rpage);

      //} //$posts_post as $post_post
      } // $redirectors as $redirector

    //} //$posts_post
    } // $redirectors

    // 7.2 -------------------------Create New Redirector---------------------------------------------
    //$last = '';
    $mnss = rand(10, 999); // new redirector mn number

    $adminRow = new AdminRow();
    $adminRow->populated = false; // This is the unpopulated data-entry row

    //echo "<tr>";
    //echo "<td>"; // 1. class="custom_ref_td"
    //echo "<div>"; // class="cat_slug_ids_div"
    //echo "<div>(AB)</div>"; // class="letter_label"
    //"<strong>Category Slugs + PostIDs</strong>"
    //. "<br />"
    //. "<small>Comma delimited, mix and match</small>";
    $adminRow->cat_slug_ids_div = "<input name=\"single_pages_categories\" id=\"single_pages_categories\" type=\"text\" onkeyup=\"disableCheckboxesNew(this.value)\">";
    //echo "</div>"; // cat_slug_ids_div

    //echo "<div>";
    //echo "<div>"; // class="random_referrers_div"
    //echo "<div>(C)</div>" // class="letter_label"
    //. "<strong>Random Referrers</strong>";
    $adminRow->random_referrers_div = "<label for=\"random_post\">"
      . "<input name=\"random_post\" id=\"random_post\" type=\"checkbox\" onchange=\"disableTextboxNew()\">"
      . "Random Posts"
      . "</label>"
      . "<label for=\"random_page\">"
      . "<input id=\"random_page\" name=\"random_page\" type=\"checkbox\" onchange=\"disableTextboxNew()\">"
      . "Random Pages"
      . "</label>";
    //echo "</div>"; // random_referrers_div
    //echo "</div>"; // div ???
    //echo "</td>"; // custom_ref_td
    //echo "<td>"; // 2. class="redirection_td"

    // why is this here?
    //<input type="hidden" name="update" value="yes" />
    //<div class="link_scanner_div">
    //<div class="letter_label">(A)</div>
    //<strong>Linkscanner URLs</strong><br />
    //<small>/Regex/ Scans for existing links</small><br />
    // ls_regex_new aka linkscanner urls
    $adminRow->link_scanner_div = "<input type=\"text\" id=\"ls_regex_new\" name=\"ls_regex_new\">";
    //</div> <!-- link_scanner_div -->

    //<div class="manual_url_targets_div">
    //<div class="letter_label">(BC)</div>
    //<strong>Manual URL Targets</strong>
    $adminRow->manual_url_targets_div = "<textarea name=\"post_aff_url\" type=\"text\" id=\"post_aff_url\"></textarea>";
    //</div> <!-- manual_url_targets_div -->
    //echo "</td>"; // redirection_td

    //echo "<td>"; // 3.
    //<input name="mn_upd" type="hidden" id="mn_upd" value="<qqqphp echo $mnss; QQQ>" size="10" readonly="readonly"/>
    $adminRow->linkscanner_td = "<input name=\"mn_upd\" type=\"hidden\" id=\"mn_upd\" value=\"$mnss\" size=\"10\" readonly=\"readonly\"/>";
    //echo "</td>";

    //$r = "";
    //$r .= "<td>"; // 4. class="other_controls_td"

    //$other_controls = "";
    //$adminRow->other_controls_td = "<div>" // class="homepage_div"
    $adminRow->homepage_div = "<label>"
      . "Homepage %"
      . "<input type=\"text\" size=\"3\" value=\"0\" id=\"rand_1\" name=\"rand_1\">";
    //. "</div>";  // homepage_div
    //$r .= "</td>"; // other_controls_td 
    //echo $r;

    //echo "</tr>";
    //echo $adminRow->getHTML();
    $tbody .= $adminRow->getHTML();
    //echo "</tbody>";*/
    $tbody ."</tbody>";
    echo $tbody;
    echo "</table>";

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
    echo "<input type=\"hidden\" name=\"update\" value=\"yes\" />";
    echo "</form>"; // end the form started a long time ago.

    // redirector database clear button
    echo "<div id=\"reset_database_div\">";
    $page = $_GET['page'];
    echo "<form action=\"options-general.php?page=$page\" method=\"post\">";
    echo "<input type=\"hidden\" name=\"redirector_databaseclear\" value=\"yes\" />";
    echo "<input onclick=\"if(!confirm('Reset Database?')){ return false; };\" class=\"c_button_link\" type=\"submit\" value=\"Reset Database\" />";
    echo "</form>";
    echo "</div>"; // reset_database_div

    // linkscanner database rebuild button
    // This is not visible on the UI and thus cannot ever be triggered.
    // Essentially unused
    echo "<form method=\"post\" action=\"options-general.php?page=$page\">";
    echo "<input type=\"hidden\" name=\"linkscanner_databaseclear\" value=\"yes\" />";
    echo "<br><br>";
    echo "</form>";
    //<qqqphp
    //echo '<input type="submit" style="position:relative;float:left;display:inline;" class="" value="Reset Linkscanner Database" /></form></div>';

    echo "</div>"; // save_reset_div
    echo "</div>"; // thecenter
    echo "<div></div>"; //  class="clear"

  }

  // called because of add_action(publish_post...
  private function ls_singlepost_scan($post_id) {
    $i = 5/0;
    //$ls_table = $this->wpdb->prefix . 'imb_linkscanner';
    //$jsim_url_regex = get_option('jsim_url_regex');
    //global $jsim_url_regex;
    //==================Single Scan on Publish===================
    //last published post
    //$posts = $this->wpdb->prefix . 'posts';
    //$sql = "SELECT * FROM " . $posts . " WHERE post_parent=0 ORDER by id desc";
    //$last_post = $this->wpdb->get_row($sql, ARRAY_A);
    //$postid = $last_post['ID'];
    //$regexTable = $this->wpdb->prefix . 'imb_regex';
    //$sql = "SELECT * FROM " . $regexTable . " ORDER BY id ";
    //$regexes = $this->wpdb->get_results($sql, ARRAY_A);
    //$handle=fopen('/var/www/wp2/log.txt' , 'w');
    //foreach ($regexes as $regex) {
    //fputs($handle, $regex['mn'].$regex['regex']);
    //$this->ls_scan_all($regex['mn'], base64_decode($regex['regex']), $postid);
    //}
    // fclose($handle);
    //==================End Single Scan==========================
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
  public function wp_add_red($unused) {
    $i = 5/0;
    //echo "<qqqphp if (private function_exists('wp_jdis()()')) if (wp_jdis()()) exit(); QQQ>";
  }

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

  // Drop the imb_linkscanner table, if it exists, and then
  // recreate it.
  private function resetRegexes() {

    $tableExists = $this->imb_tableExists($this->tableRegexes);
    if ($tableExists) {
      $sql = "DROP TABLE `$this->tableRegexes`";
      $this->wpdb->query($sql);
    }

    $sql = "CREATE TABLE " . $this->tableRegexes . " (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `regex` varchar(255) COLLATE utf8_turkish_ci NOT NULL,
      `mn` int(4) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `UX1` (`mn`)
    );";
    $this->wpdb->query($sql);
  }

  // Drop the imb_linkscanner table, if it exists, and then
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

  //Redirection private functions - this makes the page redirect when ref. arrives. important
  private function wp_jdis() {
    //grab control level
    // what is control level? why the gyrations?
    //if (isset($_POST['level'])) {
    //$level = $_POST['level'];
    //}
    //ignore archives
    if (is_archive()) return false;

    // dont' think this is used anywhere
    //global $post;
    // name of the MySQL redirector table
    //$redirector_table = $this->wpdb->prefix . 'imb_redirector';

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
    $mn = $_GET['mn'];
    //$mnName = mn;
    //}
    // If no MN and no $_POST['level'] then return because then its a normal blog page view...just get out of the way
    // what the purpose of $_POST['level']
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

  private function ls_scan_all($mn, $regex = NULL, $postid = NULL,$spc = NULL) {
    //$this->postsFound = array();
    //$this->linksFound = array();
    //$ls_table = $this->wpdb->prefix . 'imb_linkscanner';
    $jsim_url_regex = get_option('jsim_url_regex');
    $postStr = NULL;
    //get posts in the spc

    //$spcTable = $this->wpdb->prefix . 'terms';

    // This is not necessary.  In all cases where this method is called, in the original code
    // the $spc param is not a comma delimited list, have been created by possibly breaking down
    // an original comma delimited list
    $spcStr = str_replace(",","','",$spc);
    //if( is_numeric( $spc) ){
    //$postid = $spc;
    //}

    // This select appears to be performing a case-insensitive match on the name field.
    // hence 'atlanta' will match 'Atlanta'.  Performing the same query using phpmyadmin
    // causes a case-sensitive search and the aforementioned query would fail.  Beware!
    //$sql2Spc = "SELECT * FROM ". $spcTable . " WHERE name in ('".$spcStr."')";
    $sql2Spc = "SELECT * FROM ". $this->tableTerms . " WHERE name in ('".$spcStr."')";
    $terms = $this->wpdb->get_results($sql2Spc);

    // This method creates a comma delimited list of termIDs, but with a trailing comma at the end.
    // When this is fed into the subsequent TermRelationships query, the trailing comma gets turned into
    // a ''.  This may cause unexpected behavior.  Warning!
    $termIds = '';
    if ($terms) {
      foreach ($terms as $term) {
        $termIds .=  $term->term_id.",";
      }
    }

    $termIdsStr = str_replace(",","','",$termIds);
    //$spcRelTable = $this->wpdb->prefix . 'term_relationships';
    //$sql2SpcRel = "SELECT * FROM ". $spcRelTable . " WHERE term_taxonomy_id in ('".$termIdsStr."')";
    $sql2SpcRel = "SELECT * FROM ". $this->tableTermRelationships . " WHERE term_taxonomy_id in ('".$termIdsStr."')";
    $postIdFromTerms = $this->wpdb->get_results($sql2SpcRel);

    // Create the list of post_ids that are tagged with whatever category
    // do we need this if ?
    //if ($postIdFromTerms) {
    $postIds = '';
    foreach ($postIdFromTerms as $postIdFromTerm) {
      // $postIds gets defined here
      if(!$postIds){
        $postIds = $postIdFromTerm->object_id;
      }else{
        $postIds .=  ",".$postIdFromTerm->object_id;
      }
    }
    //}

    //if postid is set, it will act as a single scan
    if ($postid != NULL) {
      $i = 5/0;
    //$postStr = " AND ID = '$postid'";
    }
    elseif ($spc != NULL) {
      if($postIds){
        // doh! original code doesn't use this!
        // what is this supposed to do? replace the first character with a blank? append a blank?
        // doesn't do any of these things.  Doesn't do anything at all.
        //$n = $postIds; // save a copy for now good reason
        //substr_replace($postIds,' ','0','1'); 
      }
      $postStr = " AND ID in ($postIds)";
    }

    //$postsTable = $this->wpdb->prefix . 'posts';
    $sql = "SELECT * FROM " . $this->tablePosts . " WHERE post_parent=0 AND post_status = 'publish' "
      . "AND post_type = 'post' $postStr ORDER by id desc";
    $posts = $this->wpdb->get_results($sql, ARRAY_A);
    //foreach ($posts as $last_post) {
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
            $sql = "INSERT INTO " . $this->tableLinkscanners . " (postid, link, mn, regex, single_pages_categories)  SELECT '$pid','$url' , '$mn','$regex','$spc' FROM dual WHERE not exists(select id from ".$this->tableLinkscanners." where mn='".$mn."' and link='".$url."'  and regex='".$regex."' and single_pages_categories='".$spc."')";
            //bug
            //$sql = "INSERT INTO " . $ls_table . " (postid, link,mn) VALUES ('$pid','$url' , '$mn') on DUPLICATE KEY UPDATE link='$url'";
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