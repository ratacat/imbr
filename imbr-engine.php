<?php

//
// Table Manipulations by IMBR
//
// Sometimes we want to create new tables.  Sometimes we want to clear the contents of
// existing tables.  Sometimes we want to modify the structure of existing tables.   How shall
// all this fit together?
//
// First of all, we need to define some basic nomenclature re: table operations
// lest we sink into the confusion of ambiguous naming.  We need to be able to do the following
// four basic operations:
//
// 1. Create a new table.
// 2. Truncate (empty) an existing table.
// 3. Drop an existing table.
// 4. Determine the existence (or not) of a table.
//
// Using these basic operations, there are two derived operations of interest:
// 1. Initialize a table.  If the table exists, do nothing,  else create it.
// 2. Reset a table.       If the table exists, truncate it, else create it.
//
// As you can see, initialize and reset are very similiar and it's tempting to lump them together into
// one method.  Don't succumb to temptation.  These are separate operations and they should be kept separte.
//
// Given these basic and derived operations, there are three places where we might care to do any of this:
//
// 1. Upon Plugin Activation:
//
// For each imbr table
//   Initialize
//
// 2. Upon Deactivation:
//
// Do nothing. Leave the tables and data intact.
//
// 3. Upon DB Reset:
//
// For each imbr table
//   Reset
//
// Please notice that this scheme makes no provision for table migration when upgrading/downgrading the IMBR plugin.
//

require("admin_row.php");

class imbanditRedirector {

  public $imbV = '2.0.0';

  // The names of the db tables specifically for imbr
  private static $tableLinkscanners;
  private static $tableRedirectors;
  private static $tableRegexes;
  private static $tableWhitelists;

  // The names of the db tables specifically for wordpress
  private static $tablePosts;
  private static $tableTerms;
  private static $tableTermRelationships;

  // A reference to the database itself
  private static $wpdb;

  // This is the constructor for the imbr plugin class.  Its basic
  // goal is to set eight hooks that are executed whenever certain events happen.
  // This is fundamentally how control is passed to the plugin code.
  public function __construct($wpdb) {

    // 1. We will need a reference to the database in order to invoke functionality on it.
    self::$wpdb = $wpdb;

    // 2. Setup some table names specific to the IMBR plugin.
    self::$tableLinkscanners = self::$wpdb->prefix . 'imb_linkscanner';
    self::$tableRedirectors  = self::$wpdb->prefix . 'imb_redirector';
    self::$tableRegexes      = self::$wpdb->prefix . 'imb_regex';
    self::$tableWhitelists   = self::$wpdb->prefix . 'imb_whitelist';

    // 3. Now setup some table names specific to Wordpress.
    self::$tablePosts             = self::$wpdb->prefix . 'posts';
    self::$tableTerms             = self::$wpdb->prefix . 'terms';
    self::$tableTermRelationships = self::$wpdb->prefix . 'term_relationships';

    // 4. This is how we arrange to get custom IMBR CSS injected into the admin screen html.
    add_action('admin_enqueue_scripts', array(&$this, 'enqueIMBR_CSS'));

    // 5. The target of this hook will add the IMBR choice to the Settings menu.  Doing so involves
    // specifying another function to be called when the choice is selected.
    add_action('admin_menu', array(&$this, 'addIMBROptionToAdminScreen'));

    // 6. This is how the redirection process gets triggered.
    add_action('get_header', array(&$this, 'doRedirection'));

    // 7. The target of this hook will deal with new posts as they occur.
    add_action('publish_post', array(&$this, 'handleNewPost'));

    // 8. What on Earth does this do?
    //add_action('wp_head', array(&$this, 'wp_add_red'));

    // 9. Something to do with control.js
    //add_action('init', array(&$this, 'enqueueBackendFiles'));

    // 10. The target of this hook is called when the plugin is activated.
    //     Note: This must apparently be a static method.
    register_activation_hook(WP_PLUGIN_DIR . '/imbr/imbr.php', array(&$this, 'activate_plugin'));

    // 11. The target of this hook is called when the plugin is deactivated.
    //register_deactivation_hook(WP_PLUGIN_DIR . '/imbr/imbr.php', array(&$this, 'imb_deactivate'));
  }

  // Because we have hooked the admin_menu action, this code will be called at that time.
  // This function will add the IMBR choice to the Settings menu.  Doing so involves
  // specifiying another function to be called when the choice is selected.
  public function addIMBROptionToAdminScreen() {
  	add_options_page('IMBR', 'IMBR', 'manage_options', __FILE__, array(&$this, 'imb_red_editor'));
  }

  //
  // This function implements the redirection, if any is desired for a particular page view.
  // Redirection is requested by passing the parameter "mn=x" where x is some valid redirector record.
  //
  // Any call to an ordinary page or post (not the admin section) will result in this code
  // getting called.  The first step is to weed out calls that don't want redirection.
  //
  // There are two phases for a given redirection request and for each phase this function will be called.
  // We use a POST parameter "redirectionPhase" (formerly "level") to communicate which phase of
  // the process is happening.
  //
  // In phase 1, we determine which onsite post should receive the initial redirection and
  // which offsite URL should receive the final redirect.  Then we redirect to the onsite post, via
  // a POST request, passing the offsite URL as a POST parameter.
  // Said request will result in this function getting executed again for phase 2.
  //
  // In phase 2, we simply redirect again, this time to the given offsite page, via a GET.
  public function doRedirection() {

    // 1. Look for useful parameters in the POST

    // 1.1 Determine redirectionPhase
    $redirectionPhase = "";
    if (isset($_POST['redirectionPhase']))
      $redirectionPhase = $_POST['redirectionPhase'];

    // 1.2 Determine mn.  mn only comes from the initial GET request, not from any subsequent POST requests.
    $mn = ""; // nada
    if (isset($_GET['mn']))
      $mn = $_GET['mn'];

    // 2. Weed out requests that do not want redirection

    // 2.1 Ignore archives
    if (is_archive()) return; // false;

    // 2.2 If no $mn and no $redirectionPhase then this request is a normal page view.
    // Just return a get out of the way
    if ($redirectionPhase == "")
      if ($mn == "")
        return;
      else
        $redirectionPhase = "1"; // this is the start of phase 1

    // 3. Now process a particular $redirectionPhase
    switch($redirectionPhase) {
      case "1":
        $this->doRedirection_Phase1($mn);
        break;
      case "2":
        $this->doRedirection_Phase2($mn);
        break;
      default:
        $i = 5/0; // shouldn't happen
    }
  }

  // This function will implement phase 1 of the redirection, which is the
  // bulk of the work for the entire process.  In phase 1 we will determine everything
  // necessary to complete the redirection, such as which post to initially bounce to and
  // which off-site target to ultimately redirect to.  At the end of this method, we will
  // redirect to the appropriate post sending the minimal amount of info required
  // to redirect again to the final destination.
  //
  // Don't know what the return value does.
  private function doRedirection_Phase1($mn) {
  
  	// 1. Get the redirector record
  	$sql = "select * from self::$tableRedirectors where mn='$mn'";
  	$redirector = $this->wpdb->get_row($sql);
  
  	// 2. Find one random linkscanner link that matches this mn, if any.
  	$sql = "SELECT * FROM self::$tableLinkscanners WHERE mn = '$mn' ORDER BY RAND() LIMIT 1";
  	$linkscanner_row = $this->wpdb->get_row($sql);
  
  	// 3. Now determine the onsite redirection post and ultimate offsite target.
  	if ($linkscanner_row) {
  		// 3.1. Because this redirector has at least one associated linkscanner, treat
  		// this redirection as route A.
  		$ls_postid = $linkscanner_row->postid;
  		$hopLink = get_permalink($ls_postid);
  	} else if ($redirector->url != "") {
  		// 3.2. Because this redirector has no linkscanner links, but does
  		// have manual urls, treat this redirection as route B.
  
  		// 3.2.1. First find a random relevant post associated with the spc
  		$relevantPosts = $this->findAllRelevantPosts($redirector->single_pages_categories);
  		$randomPostIdx = array_rand($relevantPosts);
  
  		// 3.2.2. Next find a random manual target.  Assume the manual target
  		// field may be a \n delimited list of urls.
  		$manual_urls = explode( "\n" , $redirector->url);
  		$manual_urlIdx = array_rand($manual_urls);
  
  		$hopLink = get_permalink($relevantPosts[$randomPostIdx]['ID']);
  	}
  
  	// Now create a form and then submit it.  This causes a post to the onsite redirection page.
  	echo "<html>";
  	echo   "<head><META NAME='ROBOTS' CONTENT='NOINDEX, NOFOLLOW'></head>";
  	echo   "<body>";
  	echo     "<form action='$hopLink' method='post' id='form1'>";
  	echo        "<input type='hidden' name='offsiteURL' value='$manual_urls[$manual_urlIdx]' />";
  	echo        "<input type='hidden' name='redirectionPhase' value='2' />";
    echo      "</form>";
    echo     "<script language='JavaScript'>document.getElementById('form1').submit();</script>";
    echo   "</body>";
    echo "</html>";
  
  }
  
    // This function will implement phase 2 of the redirection.  It will receive
    // an off-site URL (as a POST param) and redirect there.
    // Don't know what the return value does.
    private function doRedirection_Phase2($mn) {
  
    //Create Form
    	echo "<html>";
    	echo   "<head><META NAME='ROBOTS' CONTENT='NOINDEX, NOFOLLOW'></head>";
    echo   "<body>";
      $offsiteURL = $_POST['offsiteURL'];
    echo     "<form action='$offsiteURL' method='get' id='form1'>";
      echo     "</form>";
      echo     "<script language='JavaScript'>document.getElementById('form1').submit();</script>";
    echo   "</body>";
      echo "</html>";
    }

  // This is how we arrange to get custom IMBR CSS injected into the admin screen.
  public function enqueIMBR_CSS() {
    wp_register_style('admin_styles', plugins_url() . "/imbr/admin_styles.css");
    wp_enqueue_style('admin_styles');
  }

  // This function will iterate over all the posts and then
  // use the WP API to individually delete them.
  private function eraseAllPosts() {

    $sql = "SELECT * FROM " . self::$tablePosts;
    $posts = $this->wpdb->get_results($sql, ARRAY_A);
    foreach ($posts as $post) {
      $postid = $post['ID'];
      wp_delete_post($postid, true /* delete, no trashcan */);
    }

    // Now reset the autoincrement postid to 1.  This will
    // help Selenium keep track of the postids for it's purposes.
    $sql = "alter table self::$tablePosts auto_increment = 1";
    $this->wpdb->get_results($sql);
  }

  // Because we have hooked the publish_post action, this function will be
  // called whenever a new post is published.
  public function handleNewPost($post_id) {
  	$sql = "SELECT * FROM self::$tableRegexes"; // who cares about the order?
  	$regexes = $this->wpdb->get_results($sql, ARRAY_A);
  	foreach ($regexes as $regex)
  		$this->linkscan($regex['mn'], $regex['regex'], $post_id);
  }

  // called because of register_deactivation_hook
  private function imb_deactivate() {
  	$i = 5/0;
  	exit();
    $options = $this->getOptions();
    $options['apiKey'] = '';
    update_option('imbandit', $options);
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
  //
  // As mentioned earlier, this is the entry point for producing the admin screen.  This
  // screen is composed of several nested html elements and the code that generates that
  // is subdivided accordingly.
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

    // 1.2 Delete a specific redirector
    //if (isset($_POST['delete_redirector'])) {
    //$query = "delete from self::$tableRedirectors where mn = '$_POST['delete_redirector']'";
    //$redirector = $_POST['delete_redirector'];
    //$query = "delete from self::$tableRedirectors where mn = '$redirector'";
    //$this->wpdb->query($query);

    // 1.3 Save the contents of a single existing or new redirector record
    } else if (isset($_POST['save_redirector'])) {
      $this->redirector_save();
    }

    // 2. The entire IMBR admin page fits inside this div.
    echo "<div class=\"wrap\">";
    echo   $this->imb_red_editor_level1();
    echo "</div>";
  }

  // This 1st level wrap div wraps a 2nd level, wrapper div.  Why?
  private function imb_red_editor_level1() {
    echo "<div style=\"clear:both\">";
    echo   $this->imb_red_editor_level2();
    echo "</div>";
    echo "<div class=\"clear\"></div>";
  }

  // This 2nd level wrap div essentially wraps a 3rd level center tag.  Why?
  private function imb_red_editor_level2() {
    echo "<br>";
    echo "<center>";
    echo   $this->imb_red_editor_level3();
    echo "</center>";
  }

  // All of the IMBR admin screen is inside this 3rd layer wrapper.
  private function imb_red_editor_level3() {

    // 1. Now display the IMBR logo and revision.
    echo "<img src=\"../wp-content/plugins/imbr/imbr.png\"><br>Version: $this->imbV<br><br><br><br>";

    // 2. Emit the Instruction Box HTML
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

    // 3. The major part of the admin screen is displayed in a table.  This is the list of existing
    // redirectors as well as an additional row to contain inputs for a new redirector.
    // Emit that table now.
    $this->imb_red_editor_table_contents();

    // 4. redirector database clear button
    echo "<div id=\"reset_database_div\">";
    $page = $_GET['page'];
    echo "<form action=\"options-general.php?page=$page\" method=\"post\">";
    echo "<input type=\"hidden\" name=\"redirector_databaseclear\" value=\"yes\" />";
    echo "<input id=\"database_reset\"class=\"c_button_link\" type=\"submit\" value=\"Reset Database\" />";
    echo "</form>";
    echo "</div>"; // reset_database_div
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

    $redirectorsQuery = "SELECT id, random_post, random_page, url, single_pages_categories, mn, rand FROM self::$tableRedirectors";
    $redirectors = $this->wpdb->get_results($redirectorsQuery);

    // Don't need this any more
    //$i = 1; // index of populated rows
    //$mns = ''; // the array of mn for the rows

    // 3.1 Iterate over all the existing redirectors, if any
    // and emit a table-row to display them.
    foreach ($redirectors as $redirector) {

      $adminRow = new AdminRow();
      $adminRow->mn = $redirector->mn;

      $rpost = "";
      if ($redirector->random_post == 1)
        $rpost = 'checked="1"';

      $rpage = "";
      if ($redirector->random_page == 1)
        $rpage = 'checked="1"';

      // 3.1.1 single_pages_categories aka "category slugs + postIDs"
      $adminRow->cat_slug_ids_div = "<input value='$redirector->single_pages_categories' name='single_pages_categories' >";

      // 3.1.2 random referrers
      // single_pages_categories aka "category slugs + postIDs"
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
      $sql = "select * from self::$tableRegexes where mn = '$redirector->mn'";
      $regexRow = $this->wpdb->get_results($sql,ARRAY_A);
      ( count($regexRow) == 1) ? $regex = $regexRow[0]['regex'] : $regex = "";
      $adminRow->link_scanner_div = "<input name='ls_regex_new' value='$regex' type='text'></input>";

      // 3.1.4 manul url targets aka post_aff_url
      $adminRow->manual_url_targets_div = "<textarea name='post_aff_url' type='text-area' >$redirector->url</textarea>";

      // 3.1.5 linkscanner_td (different than link_scanner_div)
      $sql = "SELECT * FROM self::$tableLinkscanners WHERE mn = '$redirector->mn'";
      $links = $this->wpdb->get_results($sql, ARRAY_A);
      $linksText = "";

      foreach ($links as $link) {
        $linkText = (strlen($link['link']) >= 38) ? substr($link['link'], 0, 38)  : $link['link'];
        $linksText .= $linkText.'&#13;';
      }

      $countLink = count($links);
      $adminRow->linkscanner_td = "<textarea name='linkscanner_links'>$linksText</textarea>"
        . $countLink . " Posts";

      //$adminRow->homepage_div = "<label>"
      //. "Homepage %"
      //<input name="rand_<qqqphp echo $i; QQQ>" type="text" id="rand_<qqqphp echo $i; QQQ>" value="<qqqphp echo $post_post->rand; QQQ>" size="3"/>
      //. "<input name=\"rand_$i\" type=\"text\" id=\"rand_$i\" value=\"$n\"  size=\"3\"/>"
      //. "</label>";
      //. "</div>";      // homepage_div

      $adminRow->mn_numbers_div = "<label>"
        . "ID"
        . "<input name='mn' type='text' value='$redirector->mn' size='3\'>"
        . "</label>";

      $adminRow->other_save_div = "<input name='save_redirector' class='c_button_link' type='submit' value='Save' />";

      echo $adminRow->getHTML();
    } // $redirectors as $redirector

    // 3.2 Now emit a table row to contain the controls for a new redirector.
    $adminRow = new AdminRow();
    $adminRow->populated = false; // This is the unpopulated data-entry row

    // 3.2.1 category slugs + post ids, aka single_pages_categories
    $adminRow->cat_slug_ids_div = "<input name='single_pages_categories' type='text' >";

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
    $adminRow->link_scanner_div = "<input type='text' name='ls_regex_new'>";

    // 3.2.4 manul url targets aka post_aff_url
    $adminRow->manual_url_targets_div = "<textarea name='post_aff_url' type='text'></textarea>";

    // 3.2.5 linkscanner_td (different than link_scanner_div)
    $newRedirectorMN = rand(10, 999); // new redirector mn number
    $adminRow->linkscanner_td = "<input name='mn' type='hidden' value='$newRedirectorMN' />";

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
    $adminRow->other_save_div = "<input name='save_redirector' class='c_button_link' type='submit' value='Save' />";

    echo $adminRow->getHTML();
    echo "</tbody>";
    echo "</table>";
  }

  // This method will save the contents of a particular IMBR admin screen row, such as a modified redirector
  // or a new one.  It will _not_ deal with the whitelist or mn parameters.
  //
  // Each row on the redirector table, including the new row form, is wrapped in a form and has its
  // own submit button.  When that button is pressed, the post params will have all the information 
  // need to update an existing record or to create a new one.  The key to success here is the "mn" number
  // that is passed.  Any imbr data with this given number, if any, will be removed and recreated.
  // Hence existing records get updated and new records get added.
  private function redirector_save() {

    // 1. Extract the POST values of interest.
    $manual_targets = $_POST['post_aff_url'];
    $mn             = $_POST['mn'];
    $rand_post      = (isset($_POST['random_post'])) ? 1 : 0;
    $rand_page      = (isset($_POST['random_page'])) ? 1 : 0;
    $regex          = $_POST['ls_regex_new'];
    $spc            = $_POST['single_pages_categories'];

    // 2. Delete all traces of any imbr record that is associated with the given mn

    // 2.1. Delete anything in imb_redirector associated with the given mn
    $this->wpdb->query("delete from self::$tableRedirectors where mn='$mn'");

    // 2.2. Delete anything in imb_regex associated with the given mn
    $this->wpdb->query("delete from self::$tableRegexes where mn='$mn'");

    // 2.3. Delete anything in imb_linkscanner associated with the given mn
    $this->wpdb->query("delete from self::$tableLinkscanners where mn='$mn'");

    // 3. Now insert the relevant records

    // 3.1. Insert into imb_redirector
    $query = "insert into self::$tableRedirectors set " .
      "random_post = -1, " .    // presently unused
      "random_page = -1, " .    // presently unused
      "url = '" . $_POST['post_aff_url'] . "', " .  // aka post_aff_url aka manual url targets
      "single_pages_categories = '" . $_POST['single_pages_categories'] . "', ". // this may be a comma delimited list
      "mn = '$mn', " .
      "rand = -1, " .           // presently unused
      "post_identifier = 'N/A'"; // presently unused
    $this->wpdb->query($query);

    // 3.2. Insert into imb_regex
    $query = "INSERT INTO self::$tableRegexes (regex, mn) VALUES ('$regex','$mn') on DUPLICATE KEY UPDATE regex ='$newRegex'";
    $this->wpdb->query($query);

    // 3.3. Now run linkscan on each element of spc
    $spcs = explode(',', $spc);
    foreach ($spcs as $spcItem) {
      // only send $postid _or_ $spc, never both
      if (is_numeric($spcItem))
        $this->linkscan($mn, $newRegex, $spcItem); // this is a postid
      else {
        $this->linkscan($mn, $newRegex, NULL,$spcItem); // this is a spc
      }
    }

  }

  // This function will create the imbr tables in the db,
  // if they don't already exist.  If they do exist, then don't do anything with them.
  private function initDatabase() {

    // 1. Initialize the imbr tables
    $this->initLinkscanners();
    $this->initRedirectors();
    $this->initRegexes();
    $this->initWhitelists();

    // 2. Reset the url regex to the initial state
    $jsim_url_regex = '((mailto\:|(news|(ht|f)tp(s?))\://){1}\S+)';
    update_option('jsim_url_regex', $jsim_url_regex);
  }

  // This function will create the imbr tables in the db,
  // if they don't already exist.  If they do exist, then truncate them.
  private function resetDatabase() {
  
    // 1. Reset the imbr tables
    $this->resetLinkscanners();
    $this->resetRedirectors();
    $this->resetRegexes();
    $this->resetWhitelists();

    // 2. Reset the url regex to the initial state
    $jsim_url_regex = '((mailto\:|(news|(ht|f)tp(s?))\://){1}\S+)';
    update_option('jsim_url_regex', $jsim_url_regex);

    // 3. This is only useful for testing when we need to reset the
    // posts to a known state.
    //Jared- I'm commenting this out as it was causing some frustration on the production testbed.
    //$this->eraseAllPosts();

  }

  // Create the imb_linkscanner table.
  private static function createLinkscanners() {
    // single_pages_categories aka "category slugs + postIDs"
    $sql = "CREATE TABLE " . self::$tableLinkscanners . " (
      id INT(11) NOT NULL AUTO_INCREMENT,
      postid INT(11) NOT NULL,
      mn INT(4) unsigned NOT NULL,
      link VARCHAR(1000) NOT NULL,
      single_pages_categories longtext,
      regex varchar(255),
      PRIMARY KEY  (id)
    );";
  	self::$wpdb->query($sql);
  }

  // If the imb_linkscanner table exists, do nothing.  Else create it.
  private static function initLinkscanners() {
    $tableExists = self::imb_tableExists(self::$tableLinkscanners);
    if (!$tableExists) self::createLinkScanners();
  }

  // If the imb_linkscanner table exists, truncate it.  Else create it.
  private static function resetLinkscanners() {
    $tableExists = self::imb_tableExists(self::$tableLinkscanners);
    if ($tableExists) {
      $sql = "TRUNCATE TABLE `" . self::tableLinkscanners . "`";
      self::$wpdb->query($sql);
    }
    else
      self::createLinkScanners();
  }


  // Create the imb_redirector table.
  private static function createRedirectors() {
    $sql = "CREATE TABLE `" . self::$tableRedirectors . "` (
      `id` int( 11 ) NOT NULL AUTO_INCREMENT ,
      `random_post` bool,
      `random_page` bool,
      `url` longtext,
      `single_pages_categories` longtext,
      `mn` int( 11 ) NOT NULL ,
      `rand` int( 11 ) NOT NULL ,
      `post_identifier` longtext,
      PRIMARY KEY ( `id` )
      ) AUTO_INCREMENT =1";
    self::$wpdb->query($sql);
  }

  // If the imb_redirector table exists, do nothing.  Else create it.
  private static function initRedirectors() {
    $tableExists = self::imb_tableExists(self::$tableRedirectors);
    if (!$tableExists) self::createRedirectors();
  }

  // If the imb_redirector table exists, truncate it.  Else create it.
  private static function resetRedirectors() {

    $tableExists = self::imb_tableExists(self::$tableRedirectors); 
    if ($tableExists) {
      $sql = "TRUNCATE TABLE `" . self::tableRedirectors . "`";
      self::$wpdb->query($sql);
    }
    else
      self::createRedirectors();
  }


  // Create the imb_regex table.
  private static function createRegexes() {
    $sql = "CREATE TABLE " . self::$tableRegexes . " (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `regex` varchar(255) NOT NULL,
      `mn` int(4) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `UX1` (`mn`)
    );";
    self::$wpdb->query($sql);
  }

  // If the imb_regex table exists, do nothing.  Else create it.
  private static function initRegexes() {
    $tableExists = self::imb_tableExists(self::$tableRegexes);
    if (!$tableExists) self::createRegexes();
  }

  // If the imb_regex table exists, truncate it.  Else create it.
  private static function resetRegexes() {

    $tableExists = self::imb_tableExists(self::$tableRegexes); 
    if ($tableExists) {
      $sql = "TRUNCATE TABLE `" . self::$tableRegexes . "`";
      self::$wpdb->query($sql);
    }
    else
      self::createRegexes();
  }

  // Create the imb_whitelist table.
  private static function createWhitelists() {
    $sql = "CREATE TABLE `" . self::$tableWhitelists . "` (
      `id` int( 11 ) NOT NULL AUTO_INCREMENT ,
      `refererRegex` VARCHAR(1000),
      `status` int(11),
      PRIMARY KEY ( `id` )
      ) AUTO_INCREMENT =1";
    self::$wpdb->query($sql);
  }

  // If the imb_whitelist table exists, do nothing.  Else create it.
  private static function initWhitelists() {
    $tableExists = self::imb_tableExists(self::$tableWhitelists);
    if (!$tableExists) self::createWhitelists();
  }

  // If the imb_whitelist table exists, truncate it.  Else create it.
  private static function resetWhitelists() {
    $tableExists = self::imb_tableExists(self::$tableWhitelists);
    if ($tableExists) {
      $sql = "TRUNCATE TABLE `" . self::$tableWhitelists . "`";
      self::$wpdb->query($sql);
    }
    else
      self::createWhitelists();
  }


  // This function will take a comma delimted list of
  // any number of categories and post ID ($spc), mixed in any order, and return an array
  // of relevant posts.
  //
  // This function assumes that post ID are _always_ numeric and categories are _always_
  // non-numeric.  If this is not so, unexpected results _may_ occur.
  private function findAllRelevantPosts($spc) {
    $quotedSpc = str_replace( "," , "','" , $spc);
    $sql = "select distinct ID from wp_term_relationships " .
      "left join wp_term_taxonomy on wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id " .
      "left join wp_posts         on wp_term_relationships.object_id        = wp_posts.ID " .
      "left join wp_terms         on wp_term_taxonomy.term_id               = wp_terms.term_id " .
      "where (name in ('$quotedSpc') or ID in('$quotedSpc')) and post_parent=0 and post_status = 'publish'";
    return $this->wpdb->get_results($sql, ARRAY_A);
  }

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

      // The information can be found using a 3 level join.  The old edition did not do this
      // and instead used two queries and a lot of code.  Replace the old with a proper join query.
      // This select appears to be performing a case-insensitive match on the name field.
      // hence 'atlanta' will match 'Atlanta'.  Performing the same query using phpmyadmin
      // causes a case-sensitive search and the aforementioned query would fail.  Beware!

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
    $sql = "SELECT * FROM " . self::$tablePosts . " WHERE post_parent=0 AND post_status = 'publish' "
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

        }

        foreach ($final_links as $url) {
          $sql = "insert into self::$tableLinkscanners (postid, mn, link, regex, single_pages_categories) values('$pid','$mn', '$url','N/A','N/A')";
        	$this->wpdb->query($sql);
        }
      } // if preg_match_all
    } // for each post
  }

  private static function imb_tableExists($table) {
    $sql = "SHOW TABLES LIKE '$table'";
    //$r = $this->wpdb->get_row($sql);
    $r = self::$wpdb->get_row($sql);
    return $r;
  }

  // called because of register_activation_hook, upon activation of this plugin.
  public static function activate_plugin() {
    //include("phpclient.php");
    //$server_url = "http://imbandit.com/app/server/licenseserver.php";
    //$license_array = processLicense($server_url);
    //if ($license_array[6] != 'active')
    //die('Product not properly licensed. Please obtain a legal license from <a href="http://imbandit.com">Imbandit Website</a>');

    self::initLinkscanners();
    self::initRedirectors();
    self::initRegexes();
    self::initWhitelists();

    //move controls.js
    //$f1 = $this->im_get_wp_root()."wp-content/plugins/imbr/controls.js";
    //$f1contents = file_get_contents($f1);
    //file_put_contents($this->im_get_wp_root()."wp-includes/js/controls.js",$f1contents);
    //self::$tableLinkscanners      = $wpdb->prefix . 'imb_linkscanner';

  
  }

}

// called because of add_action(wp_head...
// What does this do?
//public function wp_add_red($unused) {
//echo "<qqqphp if (private function_exists('wp_jdis()()')) if (wp_jdis()()) exit(); QQQ>";
//}
//private function getOptions() {
//Don't forget to set up the default options
//if (!$options = get_option('imbandit')) {
//$options = array('default' => 'options');
//update_option('imbandit', $options);
//}
//unset($options['apiKey']); update_option('imbandit', $options);exit;
//return $options;
//}
  // called because of add_action(init...
  // we enqueue this same file in enqueIMBR_CSS.  Why do it in two places?
  //public function enqueueBackendFiles() {
    //wp_enqueue_script("controls.js", "/wp-includes/js/controls.js", array(), "0.0.1", true);
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
  // This is nice to know, but who cares?
  //private function im_get_wp_root() {
  //$base = dirname(__FILE__);
  //$path = false;
  //if (@file_exists(dirname(dirname($base)) . "/wp-config.php")) {
  //$path = dirname(dirname($base)) . "/";
  //} else if (@file_exists(dirname(dirname(dirname($base))) . "/wp-config.php")) {
  //$path = dirname(dirname(dirname($base))) . "/";
  //} else
  //$path = false;
  //if ($path != false) {
  //$path = str_replace("\\", "/", $path);
  //}
  //return $path;
  //}

global $wpdb;
$imbanditInstance = new imbanditRedirector($wpdb);

?>