<?php

// This class will model the data and produce the HTML required
// for one redirector row, as displayed by the IMBR admin screen.
// The rows may be "populated" or not.  Populated means that the
// row contains data for an existing redirector record.  Unpopulated
// means that the row to display, although similar to ordinary rows,
// is not to be populated with non-existent data and is instead used
// as a data-entry form.

class AdminRow {

  //1. public $custom_ref_td      = "custom_ref_td";
  public $cat_slug_ids_div        = "cat_slug_ids_div";
  //public $random_referrers_div    = "random_referrers_div";

  // 2. public $redirection_td    = "redirection_td";
  public $link_scanner_div        = "link_scanner_div";
  public $manual_url_targets_div  = "manual_url_targets_div";

  // 3.
  public $linkscanner_td          = "linkscanner_td";

  // 4. public $other_controls_td = "other_controls_td";
  //public $homepage_div            = "homepage_div";
  public $mn_numbers_div          = "mn_numbers_div";
  //public $other_url_div           = "other_url_div";
  //public $other_delete_div        = "other_delete_div";
  public $other_save_div          = "other_save_div";

  // An ordinary row that comes from the db is "populated."  The similar row
  // used to enter a new row is not populated.  There are slight differences 
  // between the HTML for these types, hence this flag.
  public $populated = true;

  // This method will return the HTML required to implement a given table row.
  // The contents of each row are wrapped in a form in order to make form handling
  // easier.
  public function getHTML() {

    $page = $_GET['page'];
 
    $r  = "<tr>";
    if ( !$this->populated) $r =  "<tr id='newRedirector'>";
    $r .=   "<form action='options-general.php?page=$page' method='post'>";
    $r .=     $this->getHTMLFormContents();
    $r .=   "</form>";
    $r .= "</tr>";
    return $r;
  }

  // This method returns just the innards of the table row itself.
  private function getHTMLFormContents() {

    // 1. custom_ref_td
    $r  = "<td class='custom_ref_td'>";

    $r .=   "<div class='cat_slug_ids_div'>"; 
    $r .=     "<div class='letter_label'>(AB)</div>";
    $r .=     "<strong>Category Slugs + PostIDs</strong>";
    $r .=     "<br>";
    $r .=     "<small>Comma delimited, mix and match</small>";

    // Set by the user
    $r .=     $this->cat_slug_ids_div;

    $r .=   "</div>"; // cat_slug_ids_div

    // Populated rows get an extra div tag.  Why?
    if($this->populated) 
      $r .= "<div>"; // div ???
    //$r .=     "<div class='random_referrers_div'>";
    //$r .=       "<div class='letter_label'>(C)</div>";
    //$r .=       "<strong>Random Referrers</strong>";

    // Set by the user
    //$r .=       $this->random_referrers_div;

    //$r .=     "</div>"; // random_referrers_div
    if ($this->populated)
      $r .= "</div>"; // div ???

    $r .= "</td>"; // custom_ref_td

    // 2. redirection_td
    $r .= "<td class='redirection_td'>";
    $r .=   "<div class='link_scanner_div'>";
    $r .=     "<div class='letter_label'>(A)</div>";
    $r .=     "<strong>Linkscanner URLs</strong>";
    $r .=     "<br>";
    $r .=     "<small>/Regex/ Scans for existing links</small>";
    $r .=     "<br>";

    // Set by the user
    $r .=     $this->link_scanner_div;

    $r .=   "</div>"; // link_scanner_div
    $r .=   "<div class='manual_url_targets_div'>";
    $r .=     "<div class='letter_label'>(BC)</div>";
    $r .=     "<strong>Manual URL Targets</strong>";
    $r .=     $this->manual_url_targets_div;
    $r .=   "</div>"; // manual_url_targets_div

    $r .= "</td>"; // redirection_td

    // 3. linkscanner_td
    if ($this->populated) {
      $r .= "<td class='linkscanner_td'>";
      $r .=   $this->linkscanner_td;
      $r .= "</td>"; // linkscanner_td    	
    } else {
      // Otherwise insert this nameless table cell.
      $r .= "<td>";
      $r .=   $this->linkscanner_td;
      $r .= "</td>"; // linkscanner_td
    }

    // 4. other_controls_td
    $r .= "<td class='other_controls_td'>";

    //$r .=   "<div class='homepage_div'>";
    //$r .=     $this->homepage_div;
    //$r .=   "</div>";

    // The following 3 divisions are only used for populated rows
    if($this->populated) {

      $r .= "<div class='mn_numbers_div'>";
      $r .=   $this->mn_numbers_div;
      $r .= "</div>"; // mn_numbers_div

      //$r .= "<div class='other_url_div'>";
      //$r .=   $this->other_url_div;
      //$r .= "</div>"; // other_url_div

      //$r .= "<div class='other_delete_div'>";
      //$r .=   $this->other_delete_div;
      //$r .= "</div>"; // other_delete_div

    }

    // And the save submit button applies to every row, populated or not.
    $r .=   "<div class='other_save_div'>";
    $r .=     $this->other_save_div;
    $r .=   "</div>"; // other_save_div

    $r .= "</td>"; // other_controls_td

    return $r;
  }
}

?>