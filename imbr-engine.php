<?php
$imbV = 'v294';

class imbanditRedirector
{

        public function __construct($wpdb)
        {
                $this->wpdb = $wpdb;

                register_activation_hook(WP_PLUGIN_DIR . '/imbr/imbr.php', array(&$this, 'prc_plugin_install'));
                add_action('publish_post', array(&$this, 'ls_singlepost_scan'));
                add_action('admin_menu', array(&$this, 'prc_add_options_to_admin'));
                add_action('wp_head', array(&$this, 'wp_add_red'));
                add_action('get_header', array(&$this, 'wp_imb_red_head'));
                //add_action('init', array(&$this, 'enqueueBackendFiles'));
				register_deactivation_hook(WP_PLUGIN_DIR . '/imbr/imbr.php', array(&$this, 'imb_deactivate'));
				add_action( 'admin_enqueue_scripts', array(&$this, 'imb_enqueue' ));
        }	

        public function prc_plugin_install()
        {
				
                include("phpclient.php");
                $server_url = "http://imbandit.com/app/server/licenseserver.php";
                $license_array = processLicense($server_url);
                if ($license_array[6] != 'active')
                       die('Product not properly licensed. Please obtain a legal license from <a href="http://imbandit.com">Imbandit Website</a>');
				
                //run imbandit init
                $this->imb_init();

                //run linkscanner init
                $this->ls_init();
				
				//move controls.js
				/*$f1 = $this->im_get_wp_root()."wp-content/plugins/imbr/controls.js";
				$f1contents = file_get_contents($f1);
				file_put_contents($this->im_get_wp_root()."wp-includes/js/controls.js",$f1contents); */
			
        }

        //end setup public function

        public function getOptions()
        {
                //Don't forget to set up the default options
                if (!$options = get_option('imbandit'))
                {
                        $options = array('default' => 'options');
                        update_option('imbandit', $options);
                }
                //unset($options['apiKey']); update_option('imbandit', $options);exit;
                return $options;
        }

        public function checkApiKey()
        {
                $options = $this->getOptions();
                if (!isset($options['apiKey']))
                {
                        return FALSE;
                }
                $key = md5(md5($_SERVER['SERVER_NAME'] . 'laDonnaEMobile'));
                if ($options['apiKey'] != $key)
                        return FALSE;

                return TRUE;
        }

        public function getApiKey()
        {
                $content = 'Please enter the paypal email address used to purchase your license:
                        <p><form action="options-general.php?page=' . $_GET['page'] . '" method="post">
                        <table><tr><td>Paypal Email:</td><td><input type="text" name="paypalemail"></td><td colspan="2" align="left"><input type="submit" name="submit1" value="Activate"></td></tr>
                        </table>
                        <input type="hidden" name="apiKeyRequest" value="1">
                        </form></p>';
                return $content;
        }

        public function requestApiKey($email)
        {
                $key = file_get_contents('http://imbandit.com/app/store/apiKeyServer.php?email=' . $email . '&server=' . $_SERVER['SERVER_NAME']);
                $options = $this->getOptions();
                $options['apiKey'] = $key;
                update_option('imbandit', $options);
                $myKey = md5(md5($_SERVER['SERVER_NAME'] . 'laDonnaEMobile'));
                if ($key == $myKey)
                        echo 'API Key set successfully.';
                else
                        echo 'Unauthorized';
        }
		
		 public function copyemz($file1,$file2){ 
          $contentx =@file_get_contents($file1); 
                   $openedfile = fopen($file2, "w"); 
                   fwrite($openedfile, $contentx); 
                   fclose($openedfile); 
                    if ($contentx === FALSE) { 
                    $status=false; 
                    }else $status=true; 
                    
                    return $status; 
    } 
	
		public function im_get_wp_root()
		{
			$base = dirname(__FILE__);
			$path = false;

			if (@file_exists(dirname(dirname($base))."/wp-config.php"))
			{
				$path = dirname(dirname($base))."/";
			}
			else
			if (@file_exists(dirname(dirname(dirname($base)))."/wp-config.php"))
			{
				$path = dirname(dirname(dirname($base)))."/";
			}
			else
			$path = false;

			if ($path != false)
			{
				$path = str_replace("\\", "/", $path);
			}
			return $path;
		}

        public function enqueueBackendFiles()
        {
                //wp_enqueue_script("controls.js", "/wp-includes/js/controls.js", array(), "0.0.1", true);
        }
		
		public function imb_enqueue() {
			$f1 = $this->im_get_wp_root()."wp-content/plugins/imbr/controls.js";
			wp_enqueue_script( 'control.js', "/wp-content/plugins/imbr/controls.js", array(),".0.0.1", true);
			file_put_contents($this->im_get_wp_root()."wp-content/plugins/imbr/text.txt","TEST!");
		}

		//deprecated
        public function ls_init($delete = null)
        {
			$tablename = $this->wpdb->prefix . "imb_linkscanner";
            $posts = $this->wpdb->prefix . 'posts';
            $tableNameExists = $this->imb_tableExists($tablename);

			//save regexes in options
            $jsim_url_regex = '((mailto\:|(news|(ht|f)tp(s?))\://){1}\S+)';
            update_option('jsim_url_regex', $jsim_url_regex);

            //delete database with public function argument
            if ($delete)
            {
                    if ($tableNameExists)
                    {
                            $sql = "DROP TABLE `$tablename`";
                            $this->wpdb->query($sql);
                    }
            }

            //create table if it doesn't exist
            if (!$tableNameExists)
            {
                    $sql = "CREATE TABLE " . $tablename . " (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    postid INT(11) NOT NULL,
                    mn INT(4) unsigned NOT NULL,
                    link VARCHAR(1000) NOT NULL,
                    PRIMARY KEY  (id),
                    UNIQUE KEY postid (postid , mn)
                    );";
                    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                    dbDelta($sql);
            }

            //table imb_regex
            $tablename = $this->wpdb->prefix . "imb_regex";
            $tableNameExists = $this->imb_tableExists($tablename);

            //delete database with public function argument
            if ($delete)
            {
                    if ($tableNameExists)
                    {
                            $sql = "DROP TABLE `$tablename`";
                            $this->wpdb->query($sql);
                    }
            }

            //create table if it doesn't exist
            if (!$tableNameExists)
            {
                    $sql = "CREATE TABLE " . $tablename . " (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `regex` varchar(255) COLLATE utf8_turkish_ci NOT NULL,
                      `mn` int(4) NOT NULL,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `UX1` (`mn`)

                    );";

                    dbDelta($sql);
            }
        }

        public function imb_init($delete = null)
        {

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                $tablename = $this->wpdb->prefix . 'imb_redirector';

                $tableNameExists = $this->imb_tableExists($tablename);
                if ($delete)
                {
                        if ($tableNameExists)
                        {
                                $sql = "DROP TABLE `$tablename`";
                                $this->wpdb->query($sql);
                        }
                }

                if (!$tableNameExists)
                {
                        $sql = "CREATE TABLE `" . $tablename . "` (
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


                        dbDelta($sql);
                } // end create table if not exist
        }

        //end imb_init
        //Redirection public functions - this makes the page redirect when ref. arrives.
        public function wp_jdis()
        {



                //grab control level
                if (isset($_POST['level']))
                {
                        $level = $_POST['level'];
                }

                //ignore archives
                if (is_archive ())
                        return false;

                global $post;


                $table = $this->wpdb->prefix . 'imb_redirector';

                //overwrite $mn from $_POST if $_GET is present
                $mn = $_GET['mn'];

                //no MN? No $_POST['level']?  Just die!  (because then its a normal blog page view...just get out of the way)
                if (!isset($_GET['mn']) && !isset($_POST['level']))
                {
                        return false;
                        exit();
                }

                //start the log
                $log = "Start imb_red<br>"; //log
                //Initialize level 1 if no other exists

                if (!isset($level))
                {
                        session_start();
                        $log .= "Creating Session at Level 1<br>"; //logging
                        //Prep Final Data Structure
                        if (!isset($this->singlePageCategoriesSet))
                                $_SESSION['single-pages-categories'] = "default";
                        $_SESSION['sid'] = "default";
                        $_SESSION['mn'] = "default";
                        $_SESSION['affiliate_url'] = "default";
                        $_SESSION['type'] = "default";
                        $log .= '$_SESSION values Initialized to Default<br>'; //logging
                        //Deal with empty or invalid MNs
                        if (!isset($_GET['mn']))
                        {
                                header('Location: ' . get_option('siteurl'));
                                $log .= "MN Not Set, Redirecting to Siteurl<br>"; //logging
                                return true;
                                exit();
                        } elseif ($_GET['mn'] >= 1100 && $_GET['mn'] <= 1600)
                        {
                                //Give Linkscanner a Type
                                $log .= "MN is 7, using Linkscanner<br>"; //logging
                                $_SESSION['type'] = 'linkscanner';
                        } elseif ($_GET['mn'] > 999)
                        {
                                header('Location: ' . get_option('siteurl'));
                                $log .= "MN above 1000, Redirecting to Siteurl<br>"; //logging
                                return true;
                                exit();
                        } else
                        {
                                //Check for MN in Database
                                $query = "select * from $table where mn='" . $_GET['mn'] . "'";
                                $qq_mn = $this->wpdb->query($query);
                                if ($qq_mn == 0)
                                { //no mn found
                                        header('Location: ' . get_option('siteurl'));
                                        $log .= "MN Not found in database<br>"; //logging
                                        return true;
                                        exit();
                                } else
                                { //mn was found
                                        $log .= "MN($mn) looks alright<br>"; //logging
                                        //Pull Down Database Data
                                        $qq_data = $this->wpdb->get_row($query);
                                        $_SESSION['mn'] = $qq_data->mn;
                                        $_SESSION['affiliate_url'] = $qq_data->url;
                                        $_SESSION['rand'] = $qq_data->rand;
                                        $log .= 'Data pulled down<br>'; //logging
                                }
                        }

                        if (isset($_SESSION['rand']))
                        {
                                $total = 0;
                                for ($n = 0; $n <= 9; $n++)
                                        $total += rand(0, 10);

                                if ($total <= $_SESSION['rand'])
                                {
                                        $_SESSION['single-pages-categories'] = get_option('siteurl');
                                        $this->singlePageCategoriesSet = 1;
                                }
                        }

                        //Give Random & Single a type if not already defined
                        if ($_SESSION['type'] != 'linkscanner')
                        {
                                if ($qq_data->random_page && $qq_data->random_post)
                                {
                                        $_SESSION['type'] = 'Full Random';
                                } elseif ($qq_data->random_page)
                                {
                                        $_SESSION['type'] = 'Random Page';
                                } elseif ($qq_data->random_post)
                                {
                                        $_SESSION['type'] = 'Random Post';
                                } elseif ($qq_data->single_pages_categories)
                                {
                                        $_SESSION['type'] = 'single';
                                } else
                                {
                                        $log .= "Unknown TYPE!!<br>"; //logging
                                        echo $log;
                                        print_r($qq_data);
                                        die;
                                }
                        }
                        $log .= "Session Type Set: " . $_SESSION['type'] . "<br>"; //logging
                        //Format & Post sid from $_GET['sid']
                        if (count($_GET) > 1)
                        {
                                $sid = explode('mn=' . $_GET['mn'], $_SERVER['REQUEST_URI']);
                                $_SESSION['sid'] = $sid[1];
                                $log .= "SID(" . $_SESSION['sid'] . ") found<br>"; //logging
                        }


                        //Switch for RANDOMS, SINGLES, LS  ===single-pages-categoriesS set here
                        switch ($_SESSION['type'])
                        {
                                case 'linkscanner':
                                        //check linkscanner database
                                        $table = $this->wpdb->prefix . "imb_linkscanner";
                                        if ($this->imb_tableExists($table))
                                        {
                                                $sql = "SELECT * FROM $table
                                                WHERE mn = '" . $_GET['mn'] . "'
                                                ORDER BY RAND() LIMIT 1";
                                                $linkscanner = $this->wpdb->get_row($sql);

                                                $ls_postid = $linkscanner->postid;
                                                $ls_link = $linkscanner->link;
                                        }
                                        if (!isset($this->singlePageCategoriesSet))
                                                $_SESSION['single-pages-categories'] = get_permalink($ls_postid);
                                        $_SESSION['mn'] = $_GET['mn'];
                                        $_SESSION['affiliate_url'] = $ls_link;
                                        $log .= "Switch Statement chose Linkscanner<br>"; //logging
                                        break;

                                case 'single':
                                        //divvy up postids & categories
                                        $exp = explode(',', $qq_data->single_pages_categories);
                                        foreach ($exp as $item)
                                        {
                                                if (is_numeric($item))
                                                {
                                                        $postids[] = $item;
                                                } elseif (is_string($item))
                                                {
                                                        $cats[] = $item;
                                                }
                                        }

                                        //if cats, grab all inclusive postids and mix them
                                        if (isset($cats)){
											foreach ($cats as $cat)
											{

													//select all term ids for appropriate category
													$table = $this->wpdb->prefix . "terms";
													$query = "select term_id from $table WHERE slug = '" . $cat . "'";
													$term_id = $this->wpdb->get_row($query);
													$term = $term_id->term_id;

													//convert term_id to term_taxonomy_id
													$table = $this->wpdb->prefix . "term_taxonomy";
													$query = "select term_taxonomy_id from $table WHERE term_id = '" . $term . "'";
													$return = $this->wpdb->get_row($query);
													$term_tax = $return->term_taxonomy_id;

													//lookup all post_ids within term id
													$table = $this->wpdb->prefix . "term_relationships";
													$query = "select object_id from $table WHERE term_taxonomy_id = " . $term_tax;
													$rawposts = $this->wpdb->get_results($query);

													//pull data from allposts
													$table = $this->wpdb->prefix . "posts";
													$query = "select ID, post_status from $table";
													$get_posts = $this->wpdb->get_results($query);

													//filter out drafts, revisions from allposts
													foreach ($get_posts as $key => $post)
													{
															if (!stristr($post->post_status, 'publish'))
															{
																	unset($get_posts[$key]);
															}

															//match published posts to posts with valid term_id
															foreach ($rawposts as $termpost)
															{
																	if ($termpost->object_id == $post->ID)
																	{
																			$final_valid_cat_posts[] = $termpost->object_id;
																	}
															}
													}
											}
										}

                                        //check if postids exists and merge
                                        if (isset($postids))
                                        {
                                                if (isset($final_valid_cat_posts)) {$finalpostids = array_merge($postids, $final_valid_cat_posts);} else {$finalpostids = $postids;}
                                        } else
                                        { //otherwise just use cat postids
                                                $finalpostids = $final_valid_cat_posts;
                                        }

                                        //choose a random postid and go with it
                                        $r = rand(0, count($finalpostids) - 1);
                                        if (!isset($this->singlePageCategoriesSet))
                                                $_SESSION['single-pages-categories'] = get_permalink($finalpostids[$r]);

                                        //if not valid, retry until you find a valid entry

                                        $log .= "Switch Statement chose Single<br>"; //logging
                                        break;

                                case 'Random Post':
                                        $query = "SELECT ID, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish' and post_type = 'post'";
                                        $qq_post = $this->wpdb->get_results($query);
                                        $r = rand(0, count($qq_post));
                                        $d = $qq_post[$r];
                                        if (!isset($this->singlePageCategoriesSet))
                                                $_SESSION['single-pages-categories'] = get_permalink($d->ID);
                                        $log .= "Switch Statement chose Random Post<br>"; //logging
                                        break;

                                case 'Random Page':
                                        $query = "SELECT ID, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish' and post_type = 'page'";
                                        $qq_page = $this->wpdb->get_results($query);
                                        $r = rand(0, count($qq_page));
                                        $d = $qq_page[$r];
                                        if (!isset($this->singlePageCategoriesSet))
                                                $_SESSION['single-pages-categories'] = get_permalink($d->ID);
                                        $log .= "Switch Statement chose Random Page<br>"; //logging
                                        break;

                                case 'Full Random':
                                        $query = "SELECT ID, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish'";
                                        $qq_full = $this->wpdb->get_results($query);
                                        $r = rand(0, count($qq_full));
                                        $d = $qq_full[$r];
                                        if (!isset($this->singlePageCategoriesSet))
                                                $_SESSION['single-pages-categories'] = get_permalink($d->ID);
                                        $log .= "Switch Statement chose Full Random<br>"; //logging
                                        break;

                                default:
                                        $log .= "Nothing satisfied switch statement!<br>"; //logging
                                        die;
                        }

                        //Create Form
                        echo '<html><head><META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW"></head><body><form action="' . $_SESSION['single-pages-categories'] . '" method="post" id="form1">';
                        echo '<input type="hidden"  name="type" value="' . $_SESSION['type'] . '" />';
                        echo '<input type="hidden"  name="mn" value="' . $_SESSION['mn'] . '" />';
                        echo '<input type="hidden"  name="affiliate_url" value="' . $_SESSION['affiliate_url'] . '" />';
                        echo '<input type="hidden"  name="single-pages-categories" value="' . $_SESSION['single-pages-categories'] . '" />';
                        echo '<input type="hidden"  name="level1log" value="' . $log . '" />';
                        echo '<input type="hidden"  name="sid" value="' . $_SESSION['sid'] . '" />';
                        echo '<input type="hidden"  name="level" value="2" />';
                        echo '</form><script language="JavaScript">document.getElementById(\'form1\').submit();</script></body></html>';

                        //Finish & Return
                        $log .= "Finishing Level 1<br>"; //logging

                        return true;
                        exit();
                } else
                {  //Move to appropriate control level
                        if ($level == 2)
                        {
                                $log .= "Switching to Level 2<br>"; //logging
                                //bringing along variables from L1
                                if (isset($_POST['affiliate_url']))
                                {
                                        $affiliate_url = $_POST['affiliate_url'];
                                }
                                if (isset($_POST['single-pages-categories']))
                                {
                                        $spc = $_POST['single-pages-categories'];
                                }
                                if (isset($_POST['mn']))
                                {
                                        $mn = $_POST['mn'];
                                }
                                if (isset($_POST['type']))
                                {
                                        $type = $_POST['type'];
                                }
                                if (isset($_POST['sid']))
                                {
                                        $sid = $_POST['sid'];
                                }
                                if (isset($_POST['level1log']))
                                {
                                        $level1log = $_POST['level1log'];
                                }

                                // Debug Stopper
                                //echo $log;
                                //$ses = print_r($_POST, true);
                                //echo "======$_POST======<br><pre>".$ses."</pre>";
                                //die;
                                //

                                $log .= "Finishing Level 2<br>"; //logging
                                //Create Form
                                echo '<html><head><META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW"></head><body><form action="' . $spc . '" method="post" id="form1">';
                                echo '<input type="hidden"  name="type" value="' . $type . '" />';
                                echo '<input type="hidden"  name="mn" value="' . $mn . '" />';
                                echo '<input type="hidden"  name="affiliate_url" value="' . $affiliate_url . '" />';
                                echo '<input type="hidden"  name="single-pages-categories" value="' . $spc . '" />';
                                echo '<input type="hidden"  name="level2log" value="' . $log . '" />';
                                echo '<input type="hidden"  name="level1log" value="' . $level1log . '" />';
                                echo '<input type="hidden"  name="sid" value="' . $sid . '" />';
                                echo '<input type="hidden"  name="level" value="3" />';
                                echo '</form><script language="JavaScript">document.getElementById(\'form1\').submit();</script></body></html>';

                                return true;
                                exit();
                        } elseif ($level == 3)
                        {
                                $log .= "Switching to Level 3<br>"; //logging
                                if (isset($_POST['affiliate_url']))
                                {
                                        $affiliate_url = $_POST['affiliate_url'];
                                }
                                if (isset($_POST['single-pages-categories']))
                                {
                                        $spc = $_POST['single-pages-categories'];
                                }
                                if (isset($_POST['mn']))
                                {
                                        $mn = $_POST['mn'];
                                }
                                if (isset($_POST['type']))
                                {
                                        $type = $_POST['type'];
                                }
                                if (isset($_POST['sid']))
                                {
                                        $sid = $_POST['sid'];
                                }
                                if (isset($_POST['level1log']))
                                {
                                        $level1log = $_POST['level1log'];
                                }

                                //clean up SID action
                                if ($sid == 'default')
                                {
                                        unset($sid);
                                } else
                                { // fix & and ?
                                        if (stristr($affiliate_url, '?'))
                                        {
                                                $log .= "SID Fit<br>"; //logging
                                        } else
                                        {
                                                $sid = ltrim($sid, '&');
                                                $sid = '?' . $sid;
                                                $log .= "SID Modified to fit<br>"; //logging
                                        }
                                }

                                //New public functionality: If the URL is in fact a list separated with a new line, explode the list and pick up a random one - Can
                                if (strstr($affiliate_url, "\n"))
                                {
                                        $urlArray = explode("\n", $affiliate_url);
                                        $rand = rand(0, sizeof($urlArray) - 1);
                                        $url = str_replace("\n", '', trim($urlArray[$rand])) . $sid;
                                } else { $url = $affiliate_url . $sid;}
								
								/*
								echo "Finished L3<br>";
                                echo $log;
                                $ses = print_r($_POST, true);
                                echo "======$_POST======<br><pre>".$ses."</pre><br><br>";
                                echo "~~~~URLARRAY~~~~";
								print_r($urlArray);
								echo "<br>".$url."<br>";
                                die;
								*/
								
								//brad's hackfix to doublecheck referers
								$dom = preg_replace( "/^www\./", "", $_SERVER[ 'HTTP_HOST' ] ) ; 
								$ref= $_SERVER['HTTP_REFERER']; 
								if ((strpos($ref, $dom)!=FALSE) || (trim($ref)=="" ) ) { 
									header('Location: ' . $url);
								} else {
								//echo "brads hackfix fired";
                                //Relocate to URL
                                $log .= "Finishing Level 3<br>"; //logging
                                unset($level);
                                return true;
                                exit();
								}
                        } else
                        {
                                $log .= "Control Level Error: Level " . $level . "<br>"; //logging
                        }
                } // Which Control Level?

                $log .= "Completed & failed<br>"; //logging
                return false;
        }

        // Wordpress Settings Page
        public function imb_red_editor()
        {
			global $imbV;
                if (isset($_POST['apiKeyRequest']))
                {
                        $this->requestApiKey($_POST['paypalemail']);
                }
				//echo $this->im_get_wp_root();
                echo '<div class="wrap"><div style="clear:both;">';
				
                echo '<br><center><img src="../wp-content/plugins/imbr/imbr.png"><br>'.$imbV.'<br><br><br><br>';
                
				//run API key check
                $apiKey = $this->checkApiKey();
                if ($apiKey == FALSE)
                {
                        echo $this->getApiKey() . '</div></center></div>';
                        exit;
                }
				
                //is there any regex to be deleted?
                if (isset($_GET['action']) && $_GET['action'] == 'deleteRegex')
                {
                        $id = $_GET['regexid'];
                        $regexTable = $this->wpdb->prefix . 'imb_regex';
                        $lsTable = $this->wpdb->prefix . 'imb_linkscanner';
                        $sql = "SELECT * FROM " . $regexTable . " WHERE id='$id'";
                        $data = $this->wpdb->get_row($sql, ARRAY_A);

                        $mn = $data['mn'];
                        $query = ("DELETE FROM $lsTable WHERE mn = '$mn'");
                        $this->wpdb->query($query);
                        $query2 = ("DELETE FROM $regexTable WHERE id = '" . $_GET['regexid'] . "'");
                        $this->wpdb->query($query2);
                }

                if (isset($_POST['post_aff_url']))
                {
                        $post_aff_url = $_POST['post_aff_url'];
                } else
                {
                        $post_aff_url = '';
                }
                $table = $this->wpdb->prefix . 'imb_redirector';
                $ls_table = $this->wpdb->prefix . "imb_linkscanner";
                $co = explode('wp-admin', $_SERVER['REQUEST_URI']); //Gets last part of URL link
                $co = substr($co[0], 0, -1); //Cleans up link so that it's at the last letter.
                //$linkadd = 'http://' . $_SERVER['HTTP_HOST'] . $co; //Adds HTTP to the beginning of the link from above.
                //deal with magic quote crap for update_options
                if (get_magic_quotes_gpc ())
                {
                        $_POST = array_map('stripslashes_deep', $_POST);
                        $_GET = array_map('stripslashes_deep', $_GET);
                        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
                        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
                }

                //-------------------------Process Database Delete---------------------------------------------
                if (isset($_POST['redirector_databaseclear']))
                {
                        $this->imb_init('delete');
                } elseif (isset($_POST['linkscanner_databaseclear']))
                {
                        $this->ls_init('delete');
                } else
                {
                        //--------Process Save Settings---------------------------------------------
                        //runs if the save button is pressed

                        if (isset($_POST['update']))
                        {
                                //For Create New Line Box Only
                                if (isset($_POST['post_aff_url']) && $_POST['post_aff_url'] != '')
                                {
                                        if (!isset($_POST['single_pages_categories']))
                                                $_POST['single_pages_categories'] = NULL;

                                        if (isset($_POST['random_post']))
                                        {
                                                $rand_post = 1;
                                        } else
                                        {
                                                $rand_post = 0;
                                        }
                                        if (isset($_POST['random_page']))
                                        {
                                                $rand_page = 1;
                                        } else
                                        {
                                                $rand_page = 0;
                                        }
                                        $query = "INSERT INTO $table  set url = '" . $_POST['post_aff_url'] . "', single_pages_categories = '" . $_POST['single_pages_categories'] . "', mn ='" . $_POST['mn_upd'] . "', random_post = '" . $rand_post . "', random_page = '" . $rand_page . "' ";

                                        $this->wpdb->query($query);

                                        $_POST['post_aff_url'] = false;
                                        $_POST['single_pages_categories'] = false;
                                        $_POST['mn_upd'] = false;

                                        //exit(var_dump($this->wpdb->last_query));
                                }

                                //--------LS Regexs----------------------------------------------------
                                if (isset($_POST['ls_regex_new']))
                                {
                                        $regexTable = $this->wpdb->prefix . 'imb_regex';
                                        $regexArray = explode("\n", $_POST['ls_regex_new']);
                                        foreach ($regexArray as $newRegex)
                                        {
                                                $newRegex = trim($newRegex);
                                                if ($newRegex == NULL)
                                                        continue;
                                                $encoded = base64_encode($newRegex);

                                                $mn = rand(1100, 1500);
                                                $sql = "INSERT INTO " . $regexTable . " (regex, mn) VALUES ('$encoded','$mn') on DUPLICATE KEY UPDATE regex='$encoded'";
                                                $this->wpdb->query($sql);
                                                $this->ls_scan_all($mn, $newRegex, NULL);
                                        }
                                }

                                //-------------------------Prepare numbers for all existing boxes-----------------------
                                for ($i = 1; $i <= $_POST['post_count']; $i++)
                                {
                                        $url = "post_aff_url_" . $i;
                                        $rpost = "random_post_" . $i;
                                        $rpage = "random_page_" . $i;
                                        $spc = "single_pages_categories_" . $i;
                                        $mn = "mn_" . $i;
                                        $id = "id_" . $i;
                                        $rand = "rand_" . $i;
                                        $select = "select_" . $i;

                                        //set variables for easily reading checkboxes
                                        if (isset($_POST[$rpost]))
                                        {
                                                $rpost2 = 1;
                                        } else
                                        {
                                                $rpost2 = 0;
                                        }
                                        if (isset($_POST[$rpage]))
                                        {
                                                $rpage2 = 1;
                                        } else
                                        {
                                                $rpage2 = 0;
                                        }

                                        //delete single box (using buttons to the right)
                                        if ($_POST[$select] == 'delete')
                                        {
                                                $query = ("delete from $table where mn = '" . $_POST[$mn] . "'");
                                                $this->wpdb->query($query);
                                        } else
                                        { // else update
                                                $query = ("update $table set url = '" . $_POST[$url] . "', random_post = '" . $rpost2 . "', random_page = '" . $rpage2 . "', single_pages_categories = '" . $_POST[$spc] . "', mn = '" . $_POST[$mn] . "', rand = '" . $_POST[$rand] . "' where id = '" . $_POST[$id] . "'");
                                                $this->wpdb->query($query);
                                        }
                                }
                                //exit(var_dump($this->wpdb->last_query));
                        } //end update process
                }

                $links = '';

                //-------------------------Prepare Column Headers for Table-----------------------
                $posts_columns = array(
                    'URL' => __('Affiliate URLs (One per line)'),
                    'post URL' => __('Random Referrers OR Specific and Category Referrers'),
                    'Random MP' => __('Homepage %'),
                    'MN' => __('MN'),
                    'url' => __('url'),
                    'DEL' => __('Delete')
                );

                $posts_columns = apply_filters('manage_posts_columns', $posts_columns);


                echo '<style type="text/css">';
                echo '#thecenter { text-align:center; }';
                echo '</style>';
                echo '<table class="widefat">';

                echo '<form action="options-general.php?page=' . $_GET['page'] . '" method="post">';
                echo '<br><br>';

                //-------------------------Output Column Headers--------------------------------
                echo '<thead><tr>';
                foreach ($posts_columns as $column_display_name)
                {
                        echo '<th>';
                        echo $column_display_name . '</th>';
                } //$posts_columns as $column_display_name
                echo '</tr> </thead> <tbody id="the-list">';
                $time_difference = get_settings('gmt_offset');
                $now = gmdate("Y-m-d H:i:s", time());
                $request = "SELECT ID, post_title, post_excerpt, post_type FROM " . $this->wpdb->posts . " WHERE post_status = 'publish' ";
                $posts = $this->wpdb->get_results($request);
                $request_post = "SELECT id, random_post, random_page, url, single_pages_categories, mn, rand FROM $table";
                $posts_post = $this->wpdb->get_results($request_post);
                //exit(var_dump($this->wpdb->last_query));

                $i = 1;
                $mns = '';


                //----------------Load Existing Redirectors---------------------------------------------
                if ($posts_post)
                {
                        //displays existing boxes
                        foreach ($posts_post as $post_post)
                        {
                                $aff_url = $post_post->url;
                                $single_pages_categories = $post_post->single_pages_categories;
                                $mns[$i] = $post_post->mn;
                                if ($post_post->random_post == 1)
                                {
                                        $rpost = 'checked="1"';
                                }
                                if ($post_post->random_page == 1)
                                {
                                        $rpage = 'checked="1"';
                                }

                                //iterates through existing segments re-assigns MN if it already exists
                                for ($j = 0; $j < $i; $j++)
                                {
                                        if ($post_post->mn == $mns[$j] || $post_post->mn <= 9 || $post_post->mn >= 1000)
                                        {
                                                $mns[$i] = rand(10, 999);
                                        }
                                }

                                echo '<th style="text-align: left"><textarea name="post_aff_url_' . $i . '" type="text-area" id="post_aff_url' . $i . '"  size="40%" >' . $aff_url . '</textarea>';
                                echo '<th style="text-align: top"><div style="width:120px;float:left;"><div style="width:120px;">Random Posts<input id="random_post_' . $i . '" name="random_post_' . $i . '" type="checkbox" ' . $rpost . ' style="position:relative;top:4px;"';
                                if ($single_pages_categories != NULL)
                                        echo ' disabled';
                                echo ' onchange="disableTextbox(' . $i . ');"></div><div style="width:120px;">Random Pages<input id="random_page_' . $i . '" name="random_page_' . $i . '" type="checkbox" ' . $rpage . '  style="position:relative;top:4px;left:-2px;"';
                                if ($single_pages_categories != NULL)
                                        echo ' disabled';
                                echo ' onchange="disableTextbox(' . $i . ');"></div></div><div style="positive:relative;font-size:.7em;text-align:center;">Enter comma delimited PostIDs or Category slugs below.<input value="' . $single_pages_categories . '" id="single_pages_categories_' . $i . '" name="single_pages_categories_' . $i . '" type="text" style="width:280px;" ';
                                /*if ($rpost != NULL || $rpage != NULL)
                                        echo ' disabled'; */
                                echo ' onkeyup="disableCheckboxes(this.value , \'' . $i . '\');"></div>';
                                echo '<th style="text-align: left"><input style="width: 40px;" name="rand_' . $i . '" type="text" id="rand_' . $i . '" value="' . $post_post->rand . '" size="3"/>';
                                $co = explode('wp-admin', $_SERVER['REQUEST_URI']);
                                $co = substr($co[0], 0, -1); //jared modified negative offset to -1, it used to be -2
                                echo '<th style="text-align: left"><input style="width: 40px;" name="mn_' . $i . '" type="text" id="mn_' . $i . '" value="' . $mns[$i] . '" size="3"/>';
                                echo '<input name="id_' . $i . '" type="hidden" id="id_' . $i . '" value="' . $post_post->id . '"/>';
                                echo '<th><input type="button" onClick="alert_url(\'http:\/\/' . $_SERVER['HTTP_HOST'] . $co . '/?mn=' . $mns[$i] . '\')" value="url"/></th>';
                                echo '<th><button name="select_' . $i . '" id="select_' . $i . '" value="' . 'delete' . '">delete</button></th>';
                                echo '</tr>';
                                $i++;
                                unset($rpost);
                                unset($rpage);
                        } //$posts_post as $post_post
                } //$posts_post
                //-------------------------Create New Redirector---------------------------------------------
                $last = '';
                $mnss = rand(10, 999);
                echo '<input type="hidden" name="update" value="yes" />';
                echo '<th style="text-align: left"><textarea name="post_aff_url" type="text" id="post_aff_url" size="40" >' . '</textarea>';

                echo '<th style="text-align: top"><div style="width:120px;float:left;"><div style="width:120px;">Random Posts<input name="random_post" id="random_post" type="checkbox" style="position:relative;top:4px;" onchange="disableTextboxNew()"></div><div style="width:120px;">Random Pages<input id="random_page" name="random_page" type="checkbox" style="position:relative;top:4px;left:-2px;"  onchange="disableTextboxNew()"></div></div><div style="positive:relative;font-size:.7em;text-align:center;">Enter comma delimited PostIDs or Category slugs below.<input name="single_pages_categories" id="single_pages_categories" type="text" style="width:280px;" onkeyup="disableCheckboxesNew(this.value)"></div></th>';

                echo '<th style="text-align: left"><input name="mn_upd" type="hidden" id="mn_upd" value="' . $mnss . '" size="10" readonly="readonly"/>';
                echo '</tr>';
                echo '</table>';
                echo '<div style="clear:both;"></div>';
                echo '<input type="hidden" name="post_count" value="' . count($posts_post) . '"/>';
                echo '<br>';
                echo '<div id="thecenter">';


                $regexTable = $this->wpdb->prefix . 'imb_regex';
                $sql = "SELECT * FROM " . $regexTable . " ORDER BY id ";
                $regexes = $this->wpdb->get_results($sql, ARRAY_A);

                //$linkscanner_url = 'http://' . $_SERVER['HTTP_HOST'] . $co . '/?mn=7';



                echo '<div><br>';
                echo '<div style="margin:0px 0px 0px 10px;position:relative;float:left;">';

                if (sizeof($regexes != 0))
                {
                        echo '<h3>Link Scanner [regexp]</h3>';
                        foreach ($regexes as $regexItem)
                        {
                                $lsTable = $this->wpdb->prefix . 'imb_linkscanner';
                                $sql = "SELECT * FROM " . $lsTable . " WHERE mn = '" . $regexItem['mn'] . "' ORDER BY RAND() LIMIT 3 ";

                                $links = $this->wpdb->get_results($sql, ARRAY_A);

                                echo '<hr/><table><tr><td>' . base64_decode($regexItem['regex']) . '</td><td><input type="button" onClick="alert_url(\'http:\/\/' . $_SERVER['HTTP_HOST'] . $co . '/?mn=' . $regexItem['mn'] . '\')" value="url"/></td><td> <input type="button" id="deletebutton1" name="deletebutton1" onclick="a=confirm(\'Are you sure?\');if(a == true) top.location=\'options-general.php?action=deleteRegex&regexid=' . $regexItem['id'] . '&page=' . $_GET['page'] . '\';" value="delete"></td></tr></table>';
                                foreach ($links as $link)
                                {

                                        $linkText = (strlen($link['link']) >= 40) ? substr($link['link'], 0, 40) . '...' : $link['link'];
                                        echo '<br/><a href="' . $link['link'] . '" target="_new">' . $linkText . '</a>  ';
                                }
                                if (isset($this->postsFound[$link['mn']]))
                                        echo '<br/>';
                                if (isset($this->postsFound[$link['mn']]))
                                        echo ' Posts Found: ' . $this->postsFound[$link['mn']];
                                if (isset($linksFound[$link['mn']]))
                                        echo ' Links Found: ' . $this->linksFound[$link['mn']];
                        }
                }
                echo '<hr>New Regex: <input name="ls_regex_new" id="ls_regex_new" type="text"  style="width:350px;clear:both;"></input><br>';
                echo '</div>';


                echo '</div>';
                $linkscanner = 1;
                echo '<input style="height:70px;width:140;font-size:1em;" type="submit" value="Save All Settings" />';
                echo '</form>';


                //-------------------------Load Database Buttons---------------------------------------------
                //redirector database clear button
                echo '<div style="position:relative;float:left;width:40%;clear:both;"> <br><br><br><br><br><h2>Database Resets</h2><hr ><br><form action="options-general.php?page=' . $_GET['page'] . '" method="post">';
                echo '<input type="hidden" name="redirector_databaseclear" value="yes" />';
                echo '<input style="position:relative;float:left;display:inline;" type="submit" value="Reset Redirector Database" />';
                echo '</form>';

                //linkscanner database rebuild button
                echo '<form method="post" action="options-general.php?page=' . $_GET['page'] . '">';
                echo '<input type="hidden" name="linkscanner_databaseclear" value="yes" />';
                echo '<input type="submit" style="position:relative;float:left;display:inline;" class="" value="Reset Linkscanner Database" /></form></div>';


                echo '</br>';

                if (isset($verbose))
                {
                        //echo 'Bugnest!!! </br>';
                        echo 'Exp (permalink array):', $exp, "<br>";
                        echo 'Co: ', $co, "<br>";
                        echo 'Now: ', $now, "<br>";
                        echo 'Request: ', $request, "<br>";
                        echo 'Request_post: ', $request_post, "<br>";
                        echo 'Request_post_categories: ', $request_post_categories, "<br>";
                        echo 'Posts_post: ', $posts_post, "<br>";
                        echo 'Posts_posts: ', $posts_posts, "<br>";
                        echo 'Post_columns:(array) ', $post_columns, "<br>";
                        echo 'I: ', $i, "<br>";
                        echo 'OMG: ', $omg, "<br>";
                        echo 'hell: ', $hell, "<br>";
                        echo 'linkadd: ', $linkadd, "<br>";
                        echo 'Last: ', $last, "<br>";
                        echo 'Mn: ', $mn, "<br>";
                        echo 'Mns: ', $mns, "<br>";
                        echo 'Mnss: ', $mnss, "<br>";
                        echo 'qq: ', $qq, "<br>";
                        echo 'sid: ', $sid, "<br>";
                        echo 'Categories: ', $categories, "<br>";
                        echo 'Categories1: ', $categories1, "<br>";
                        echo 'Categories_categories: ', $categories_categories, "<br>";
                        echo 'Categories_request: ', $categories_request, "<br>";
                        echo 'Link add: ', $linkadd, '</br>';
                        echo 'Links : ', $links, '</br>';
                        echo 'TestPull: ', $testpull, '</br>';
                        $rawco = explode('wp-admin', $_SERVER['REQUEST_URI']);
                        echo 'RawCo: ';
                        print_r($rawco);

                        echo "<pre>'$_POST:'";
                        print_r($_POST);
                        echo "</pre>";
                        echo '<br>';

                        echo "<pre>'$posts_post:'";
                        print_r($posts_post);
                        echo "</pre>";
                }
                echo '</div></div>';
        }

        public function wp_add_red($unused)
        {
                echo "<?php if (public function_exists('wp_jdis()()')) if (wp_jdis()()) exit(); ?>";   
        }

        public function prc_add_options_to_admin()
        {
                add_options_page('IMBR', 'IMBR', 'manage_options', __FILE__, array(&$this, 'imb_red_editor'));
        }

        public function ls_scan_all($mn, $regex = NULL, $postid = NULL)
        {


                $this->postsFound = array();
                $this->linksFound = array();

                $ls_table = $this->wpdb->prefix . 'imb_linkscanner';
                $jsim_url_regex = get_option('jsim_url_regex');

                $postStr = NULL;

                //if postid is set, it will act as a single scan
                if ($postid != NULL)
                {
                        $postStr = " AND ID = '$postid'";
                }
                $postsTable = $this->wpdb->prefix . 'posts';
                $sql = "SELECT * FROM " . $postsTable . " WHERE post_parent=0 AND post_status = 'publish'
                AND post_type = 'post' $postStr
                ORDER by id desc";
                $posts = $this->wpdb->get_results($sql, ARRAY_A);



                foreach ($posts as $last_post)
                {

                        //check if there are links at all
                        if (preg_match_all($jsim_url_regex, $last_post['post_content'], $urls))
                        {

                                //unset links from last post
                                if (isset($final_links))
                                {
                                        unset($final_links);
                                }

                                //trim the " to end off if need be
                                foreach ($urls[0] as $url)
                                {
                                        // /^http:\\/\\/www\.([A-Za-z0-9_])\.com\\/?$/
                                        //do these  links match our custom regEx?
                                        if (@preg_match($regex, $url))
                                        {
                                                if ($pos = stripos($url, '"'))
                                                {
                                                        $diff = strlen($url) - $pos;
                                                        $final_links[] = substr($url, 0, - $diff);
                                                } else
                                                {
                                                        $final_links[] = $url;
                                                }
                                        }
                                        //     else echo '|'.$regex.'|';exit;
                                }

                                //prepare and store and found links along with pid
                                if (isset($final_links))
                                {

                                        //post id
                                        $pid = $last_post['ID'];
                                        $this->postsFound[$mn]++;
                                        //reset from last item
                                        $collate_links = '';

                                        //collate links

                                        foreach ($final_links as $url)
                                        {
                                                $this->linksFound[$mn]++;
                                                //$collate_links.=$url . '##';
                                                $sql = "INSERT INTO " . $ls_table . " (postid, link,mn) VALUES ('$pid','$url' , '$mn') on DUPLICATE KEY UPDATE link='$url'";
                                                $this->wpdb->query($sql);
                                        }
                                }
                        }
                }
                //==================End Single Scan==========================
        }


        public function ls_singlepost_scan($post_id)
        {

                $ls_table = $this->wpdb->prefix . 'imb_linkscanner';
                $jsim_url_regex = get_option('jsim_url_regex');
                //global $jsim_url_regex;
                //==================Single Scan on Publish===================
                //last published post
                $posts = $this->wpdb->prefix . 'posts';
                $sql = "SELECT * FROM " . $posts . " WHERE post_parent=0 ORDER by id desc";
                $last_post = $this->wpdb->get_row($sql, ARRAY_A);
                $postid = $last_post['ID'];

                $regexTable = $this->wpdb->prefix . 'imb_regex';
                $sql = "SELECT * FROM " . $regexTable . " ORDER BY id ";
                $regexes = $this->wpdb->get_results($sql, ARRAY_A);
                //$handle=fopen('/var/www/wp2/log.txt' , 'w');
                foreach ($regexes as $regex)
                {
                        //fputs($handle, $regex['mn'].$regex['regex']);
                        $this->ls_scan_all($regex['mn'], base64_decode($regex['regex']), $postid);
                }
                // fclose($handle);
                //==================End Single Scan==========================
        } 

        //public function_exists('add_action')

        public function wp_imb_red_head()
        {
                if ($this->wp_jdis())
                        exit();
                //	thesis_html_framework();
        }

        public function imb_tableExists($table)
        {

                $sql = "SHOW TABLES LIKE '$table'";
                $r = $this->wpdb->get_row($sql);
                return $r;
        }

        public function array_random($arr, $num = 1)
        {
                shuffle($arr);

                $r = array();
                for ($i = 0; $i < $num; $i++)
                {
                        $r[] = $arr[$i];
                }
                return $num == 1 ? $r[0] : $r;
        }

		public function imb_deactivate()
		{
		        $options = $this->getOptions();
                $options['apiKey'] = '';
                update_option('imbandit', $options);
		}
}

global $wpdb;
$imbanditInstance = new imbanditRedirector($wpdb);

?>