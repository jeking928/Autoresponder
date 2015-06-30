<?php
/**
 * Autoresponder+
 * Version 2.4
 * By Steven300
 * @copyright Portions Copyright 2004-2008 Zen Cart Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */


//This line is important so that Autoresponder+ connects to the database and the Zen Cart programme
require('includes/application_top.php');


/* IF TIME VALIDATION ENABLED, DETERMINE WHETHER CURRENT TIME PASSES */
//If time validation is enabled
if ( AUTO_TIMER == 'true' ) {

//Get the configured start time
$entry_start = AUTO_START_TIME;
//Replace colons with empty ''
$start = str_replace(':', '', $entry_start);

//Get the configured end time
$entry_end = AUTO_END_TIME;
// replace colons with empty ''
$end = str_replace(':', '', $entry_end);

//Get current time
$t = time();
$now = date('His', $t);

//Compare the current time with configured times
if ( ($start < $now ) && ( $end > $now ) ) { //If current time is between start and end times
    $time_valid = TRUE; //Time is valid
    } else {
    $time_valid = FALSE; //Time is not valid
	}
	
} //End of time-validation-enabled



//If time validation passes or is not set
if ( ( (AUTO_TIMER == 'true') && ($time_valid) ) || (AUTO_TIMER == 'false') ) {

//If autoresponder+ enabled
if (AUTO_ENABLE_AUTORESPONDER == 'true') {

//Set the log filename
$log_file_name = AUTO_LOG_DIRECTORY . "autoresponder_log.log";

/* LOG FUNCTION */
//This writes (updates) the log file with whatever text is used when calling the function
function log_cws($msg){
	global $log_file_name; //Give access to log filename (due to scope issue)
	if (AUTO_LOG_EMAILS == 'true') { //If logging is enabled
		$fp=fopen($log_file_name,"a"); //Open file
		fputs($fp, $msg); //Write to file
		fclose($fp); //Close file
	} //End of if-logging-enabled
} //End of log function


/* SET ADMIN EMAIL ADDRESS */
//If owner email address left blank in autoresponder+ admin, default email address used
//Otherwise if owner email address entered in autoresponder+ admin, entered email address used
if (AUTO_OWNER_EMAIL_ADDRESS != '') {
$admin_email=AUTO_OWNER_EMAIL_ADDRESS;
} else {
$admin_email=STORE_OWNER_EMAIL_ADDRESS;
}

/* SET COUPON KEYWORD */
//This can be changed here if desired.
$coupon_key = "[coupon]";

//If preset #1 enabled
if (AUTO_ENABLE_PRESET == 'true') {

//Set these variables with settings from autoresponder+ admin
$this_auto_mode=AUTO_MODE;
$this_auto_order_status_id=AUTO_ORDER_STATUS_ID;
$this_auto_post_order_status_id=AUTO_POST_ORDER_STATUS_ID;
$this_auto_days_after=AUTO_DAYS_AFTER;
$this_auto_subscribed=AUTO_SUBSCRIBED;
$this_auto_subject=AUTO_SUBJECT;
$this_auto_include_name=AUTO_INCLUDE_NAME;
$this_auto_include_product_details=AUTO_INCLUDE_PRODUCT_DETAILS;
$this_auto_message_text_1=AUTO_MESSAGE_TEXT_PRE;
$this_auto_message_text_2=AUTO_MESSAGE_TEXT_POST;
$this_auto_message_html_1=AUTO_MESSAGE_HTML_PRE;
$this_auto_message_html_2=AUTO_MESSAGE_HTML_POST;
$this_auto_state=AUTO_STATE;
$this_auto_location_restrict=AUTO_LOCATION_RESTRICT;
$location=AUTO_LOCATION;
$coupon_id=AUTO_COUPON;
$products_as_url=AUTO_PRODUCT_URL;
$this_auto_product_restrict=AUTO_PRODUCT_RESTRICT;

//Set preset as preset#1
$preset="preset #1";

/* ******************************************************************** */
/* CODE BELOW HERE IS INTENTIONALLY DUPLICATED THREE TIMES IN THIS FILE */
/* ******************************************************************** */

	//Prepare log mode data
	if ($this_auto_mode == 'live') { //If in live mode
	$log_mode="(live mode)"; //Set log mode as live
	} else {
	$log_mode="(test mode)"; //Set log mode as test
	}

	//Call log function to write which preset it is, whether in live or test mode, and the date/time
	log_cws("Start " . $preset . " " . $log_mode . ": ".date("Y-m-d H:i:s")." ");



	//Calculate which day of the year the order should have been
	$order_day=mktime()-($this_auto_days_after*86400);
	$order_day=date("Y-m-d",$order_day);
	
	//Calculate which day of the year the account should have been
	$account_day=mktime()-($this_auto_days_after*86400);
	$account_day=date("Y-m-d",$account_day);
	
	/* The following SQL statements select the relevant data from the database according to autoresponder+ admin settings */
	
	//If in order mode and there is no location restriction
	if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'no') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status";
	
	//If in order mode and location is restricted to zone
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'to zone') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in order mode and location is restricted to country
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'to country') ) {
		
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in order mode and location is restricted from zone
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'from zone') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in order mode and location is restricted from country
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'from country') ) {
		
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";

	//If in account mode and there is no location restriction
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'no') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id";
	
	//If in account mode and location is restricted to zone
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'to zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in account mode and location is restricted to country
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'to country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in account mode and location is restricted from zone
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'from zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in account mode and location is restricted from country
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'from country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";
	
	//If in account-no-order mode and there is no location restriction
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'no') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id";
	
	//If in account-no-order mode and location is restricted to zone
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'to zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted to country
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'to country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted from zone
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'from zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted from country
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'from country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";
	
	}
	
	//Execute whichever SQL statement made it (if any)
	$result=$db->Execute($sql);
	
	//While there are matches in the executed SQL statement
	while (!$result->EOF) {
	
		
		//$continue_execution
		//Set this as true initially. If it ever gets changed to false, email won't send.
		$continue_execution = TRUE;
	
		
		
		/* First, let's get the customer's name */
	
		//If in order mode
		if ($this_auto_state == 'order') {
		
		$sql3="select customers_name from " . AUTO_TABLE_ORDERS . " where orders_id='".$result->fields['orders_id']."'";
		$result3=$db->Execute($sql3);
		
		$name=$result3->fields['customers_name'];
		
		} else { //Else if in account or account-no-order mode
		
		$first=$result->fields['customers_firstname'];
		$second=$result->fields['customers_lastname'];
		
		//Must concatenate
		$name= $first . " " . $second;
		
		} //End of get customer's name
		
		
		
		/* Now, let's determine whether the customer is subscribed to the newsletter */
		
		//Get newsletter status (step 1)
		$sql9="select customers_newsletter from " . AUTO_TABLE_CUSTOMERS . " where customers_email_address='".$result->fields['customers_email_address']."'";
		$result9=$db->Execute($sql9);
		
		//Get newsletter status (step 2)
		$subscribed=$result9->fields['customers_newsletter'];
		
		//Get newsletter status (step 3)
		if ( $subscribed == '1' ) {
		$subscribed = "true";
		} else {
		$subscribed = "false";
		}
		
		
		
		/* PREPARE EMAIL DATA */
		$sender = STORE_OWNER_EMAIL_ADDRESS; //From
		$to = $result->fields['customers_email_address']; //To
		$subject = $this_auto_subject; //Subject
		
		
		
		/* PRODUCT DETAILS IN EMAIL */
		
		//If in order mode AND product details should be included in email
		if ( ($this_auto_state == 'order') && ($this_auto_include_product_details == 'true') ) {
		
		//Insert a new paragraph break before product details are displayed
		$product_details_text = "\r\n" . "\r\n"; //If text email
		$product_details_html = "<p />"; //If html email
		
		//Get the order ID
		$prod_order_id = $result->fields['orders_id'];
		
		//Get product details for order
		$sql_prod="SELECT DISTINCT OP.products_quantity, OP.products_name, OP.products_id
		FROM " . AUTO_TABLE_ORDERS_PRODUCTS . " OP 
		WHERE OP.orders_id = '".$prod_order_id."'";
	
		//Exectute SQL statement
		$result_prod=$db->Execute($sql_prod);
		
		//While there are products
		while(!$result_prod->EOF) {
		
		$product_quantity = $result_prod->fields['products_quantity']; //Get quantity of product
		$product_name = $result_prod->fields['products_name']; //Get name of product
		
		//If products should be hyperlinked back to store
		if ( ($products_as_url == 'yes, to product page') || ($products_as_url == 'yes, to product review page') ) {
		
		$products_id = $result_prod->fields['products_id']; //Get product ID
		$products_id_part = "&products_id=" . $products_id; //Build part of URL that contains product ID
		
		//Prepare SQL to get the category of the product
		$sql_find_category="SELECT DISTINCT PTC.categories_id
		FROM " . AUTO_TABLE_PRODUCTS_TO_CATEGORIES . " PTC 
		WHERE PTC.products_id = '".$products_id."'";
		
		//Exectute SQL statement
		$sql_find_category_result=$db->Execute($sql_find_category);
		
		//Finally get the category of the product
		$category_id = $sql_find_category_result->fields['categories_id'];
		
		/* Build first part of URL */
		//If hyperlink is to main product page
		if ($products_as_url == 'yes, to product page') {
		
		//If store is not in a directory
		if ( dirname($_SERVER['PHP_SELF']) == "/" ) {
		$initial_url = HTTP_SERVER . "/index.php?main_page=product_info&cPath=";
		} else {
		$initial_url = HTTP_SERVER . dirname($_SERVER['PHP_SELF']) . "/index.php?main_page=product_info&cPath=";
		}
		
		} else { //Hyperlink is to product review page
		//If store is not in a directory
		if ( dirname($_SERVER['PHP_SELF']) == "/" ) {
		$initial_url = HTTP_SERVER . "/index.php?main_page=product_reviews_write&cPath=";
		} else {
		$initial_url = HTTP_SERVER . dirname($_SERVER['PHP_SELF']) . "/index.php?main_page=product_reviews_write&cPath=";
		}
		}
		
		//Build the text version of complete URL
		$product_text_url = $initial_url . $category_id . $products_id_part;
		
		//Product details to be included in email:
		$product_details_text = $product_details_text . $product_quantity . "x " . $product_name . "\r\n" . $product_text_url . "\r\n" . "\r\n";
		$product_details_html = $product_details_html . $product_quantity . "x " . "<a href=\"$product_text_url\" target=\"_blank\">$product_name</a>" . "<br />";
				
		} else { //No hyperlinks in email
		
		//Product details to be included in email:
		$product_details_text = $product_details_text . $product_quantity . "x " . $product_name . "\r\n";
		$product_details_html = $product_details_html . $product_quantity . "x " . $product_name . "<br />";
		
		} //End of no-hyperlinks-in-email
		
		//Go to next product
		$result_prod->MoveNext();
		}
		
		//Insert a new paragraph break after product details are displayed
		$product_details_text = $product_details_text . "\r\n"; //If text email
		$product_details_html = $product_details_html . "<br />"; //If html email
		
		} else { //No product details to be included in email
		$product_details_text='';
		$product_details_html='';
		}
		
		
		
		/* CUSTOMER DISPLAY NAME */
		
		/* Set customer display name, according to autoresponder+ admin settings */
		
		//Get customer's first name from full name, should it be needed
		$words = split('[ ]+', $name);
		$words[0];
		
		//Customer name options for text email
		if ($this_auto_include_name == '0') {
		$text_message = $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '1') {
		$text_message = $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '2') {
		$text_message = $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '3') {
		$text_message = AUTO_GREETING_HI . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '4') {
		$text_message = AUTO_GREETING_HI . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '5') {
		$text_message = AUTO_GREETING_HELLO . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '6') {
		$text_message = AUTO_GREETING_HELLO . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '7') {
		$text_message = $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '8') {
		$text_message = $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '9') {
		$text_message = AUTO_GREETING_HI . " " . $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '10') {
		$text_message = AUTO_GREETING_HI . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '11') {
		$text_message = AUTO_GREETING_HELLO . " " . $words[0]  . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '12') {
		$text_message = AUTO_GREETING_HELLO . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '13') {
		$text_message = AUTO_GREETING_DEAR . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '14') {
		$text_message = AUTO_GREETING_DEAR . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '15') {
		$text_message = AUTO_GREETING_DEAR . " " . $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '16') {
		$text_message = AUTO_GREETING_DEAR . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		}
		
		//Customer name options for html email		
		if ($this_auto_include_name == '0') {
		$html_message = $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '1') {
		$html_message = $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '2') {
		$html_message = $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '3') {
		$html_message = AUTO_GREETING_HI . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '4') {
		$html_message = AUTO_GREETING_HI . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '5') {
		$html_message = AUTO_GREETING_HELLO . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '6') {
		$html_message = AUTO_GREETING_HELLO . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '7') {
		$html_message = $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '8') {
		$html_message = $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '9') {
		$html_message = AUTO_GREETING_HI . " " . $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '10') {
		$html_message = AUTO_GREETING_HI . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '11') {
		$html_message = AUTO_GREETING_HELLO . " " . $words[0]  . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '12') {
		$html_message = AUTO_GREETING_HELLO . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '13') {
		$html_message = AUTO_GREETING_DEAR . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '14') {
		$html_message = AUTO_GREETING_DEAR . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '15') {
		$html_message = AUTO_GREETING_DEAR . " " . $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '16') {
		$html_message = AUTO_GREETING_DEAR . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		
		}
		
		
		
		/* NEWSLETTER SUBSCRIPTION CHECK */
		
		//If customer must be subscribed to newsletter but is not, don't go any further
		if ( ($this_auto_subscribed == 'true') && ($subscribed == "false") ) {
		$continue_execution = FALSE;
		}
		
		
		
		/* PRODUCT RESTRICTION */
		
		//If in order mode and there are product IDs to restrict
		if ( ($this_auto_state == 'order') && ($this_auto_product_restrict != '') ) {
		
		//First, remove any commas from user-submitted product IDs and convert to an array
		$ids = explode(",", $this_auto_product_restrict);
		
		//Get the order ID
		$prod_order_id = $result->fields['orders_id'];
		
		//SQL statement to get the product IDs of the order
		$sql_prod="SELECT DISTINCT OP.products_id 
		FROM " . AUTO_TABLE_ORDERS_PRODUCTS . " OP 
		WHERE OP.orders_id = '".$prod_order_id."'";
	
		//Execute SQL statement to get product IDs
		$result_prod=$db->Execute($sql_prod);
		
		//Okay so let's assume there is no match until found
		$product_match = FALSE;
		
		//While there are products
		while(!$result_prod->EOF) {
		
		$products_id = $result_prod->fields['products_id']; //Get product ID from the order
		
		//Even if one of the user-submitted product IDs match, everything is good
		if (in_array($products_id, $ids)) { //If the product ID from the order is in the array of user-submitted product IDs
		$product_match = TRUE; //A match
		}
		
		//Go to next product
		$result_prod->MoveNext();
		
		} //End of while-there-are-products
		
		//So if there was no match at all, don't send any emails
		if ($product_match) {
		} else {
		$continue_execution = FALSE;
		}
		
		} //End of product restriction
		
		
		
		/* ---------- IMPORTANT ---------- */
		//The below code actually performs actions (including sending emails), e.g. create coupon, update order status.
		//So ONLY proceed if email is definitely going to be sent
		if ($continue_execution) {
		
		
		/* COUPON CODE */
		
		//If coupon code has been entered in admin, and text email contains the coupon keyword
		//Create a unique coupon and replace the keyword with the new coupon's code
		if ( ($coupon_id != '') && (strstr($text_message,$coupon_key)) ) {
		
		//Get the example coupon from the database to replicate its settings
		$sql_get_coupon="select * from " . AUTO_TABLE_COUPONS . " where coupon_code='".$coupon_id."'";
		$get_coupon_result=$db->Execute($sql_get_coupon);
		
		//Get the example coupon's description from the database
		$sql_get_coupon_desc="select * from " . AUTO_TABLE_COUPONS_DESCRIPTION . " where coupon_id='".$get_coupon_result->fields['coupon_id']."'";
		$get_coupon_desc_result=$db->Execute($sql_get_coupon_desc);
		
		//Get the example coupon's restrictions from the database
		$sql_get_coupon_restrict="select * from " . AUTO_TABLE_COUPON_RESTRICT . " where coupon_id='".$get_coupon_result->fields['coupon_id']."'";
		$get_coupon_restrict_result=$db->Execute($sql_get_coupon_restrict);
		
		//Generate random integer between 10,000 and 999,999
		$new_coupon_code = rand(10000, 999999);
		
		//Add a year from now for the new coupon's expiry date
		$this_moment = date("Y-m-d");
		$next_year = strtotime ( '+1 year' , strtotime ( $this_moment ) ) ;
		$next_year = date ( 'Y-m-j' , $next_year );
		
		//Insert new coupon into db based on retrieved coupon but with new coupon code
		$sql_insert_coupon="insert into " . AUTO_TABLE_COUPONS . " values ( '', '".$get_coupon_result->fields['coupon_type']."', '".$new_coupon_code."', '".$get_coupon_result->fields['coupon_amount']."', '".$get_coupon_result->fields['coupon_minimum_order']."', now(), '".$next_year."', '".$get_coupon_result->fields['uses_per_coupon']."', '".$get_coupon_result->fields['uses_per_user']."', '".$get_coupon_result->fields['restrict_to_products']."', '".$get_coupon_result->fields['restrict_to_categories']."', '".$get_coupon_result->fields['restrict_to_customers']."', '".$get_coupon_result->fields['coupon_active']."', now(), now(), '".$get_coupon_result->fields['coupon_zone_restriction']."' )";
		$db->Execute($sql_insert_coupon);
		
		//Determine the newest autoincremented coupon id so the id for the coupon description and restriction can match
		$sql_get_coupon_new_id="select * from " . AUTO_TABLE_COUPONS . " where coupon_code = '".$new_coupon_code."'";
		$get_coupon_new_id_result=$db->Execute($sql_get_coupon_new_id);
		$new_coupon_id = $get_coupon_new_id_result->fields['coupon_id'];
		
		//Insert new coupon description into db based on retrieved coupon
		$sql_insert_coupon_desc="insert into " . AUTO_TABLE_COUPONS_DESCRIPTION . " values ( '".$new_coupon_id."', '1', '".$get_coupon_desc_result->fields['coupon_name']."', '".$get_coupon_desc_result->fields['coupon_description']."' )";
		$db->Execute($sql_insert_coupon_desc);
		
		//While there are coupon restrictions (if any)
		while(!$get_coupon_restrict_result->EOF) {
		
		//Insert new coupon restriction
		$sql_insert_coupon_restrict="insert into " . AUTO_TABLE_COUPON_RESTRICT . " values ( '', '".$new_coupon_id."', '".$get_coupon_restrict_result->fields['product_id']."', '".$get_coupon_restrict_result->fields['category_id']."', '".$get_coupon_restrict_result->fields['coupon_restrict']."' )";
		$db->Execute($sql_insert_coupon_restrict);
		
		//Go to next coupon restriction
		$get_coupon_restrict_result->MoveNext();
		
		} //End of while-there-are-coupon-restrictions
		
		//Replace coupon keyword in text message with new coupon code
		$text_message = str_replace($coupon_key, $new_coupon_code, $text_message);
		
		//Repeat for html message
		$html_message = str_replace($coupon_key, $new_coupon_code, $html_message);
		
		}
		
		
		
		/* PREPARE LOG DATA */
		
		//Prepare log data
		if ($this_auto_state == 'order') { //If in order mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Post-Order" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Location Restricted: " . $this_auto_location_restrict . "\r\n" . "Location: " . $location . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Order ID: " . $result->fields['orders_id'] . "\r\n" . "Date of Order: " . $order_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";
		} else if ($this_auto_state == 'account-no-order') { //If in account-no-order mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Account No Order" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Account Date: " . $account_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";
		} else if ($this_auto_state == 'account') { //If in account mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Account" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Account Date: " . $account_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";		
		}
		
		
		
		
		/* UPDATE ORDER STATUS */
		
		//If in order mode and configured order status ID is different to configured post-order status ID, update database with new status
		if ( ($this_auto_state == 'order') && ($this_auto_order_status_id != $this_auto_post_order_status_id) ) {
		
		//Update table ORDERS
		$sql_update_new_status="UPDATE " . AUTO_TABLE_ORDERS . " SET orders_status = '".$this_auto_post_order_status_id."' WHERE orders_id = '".$result->fields['orders_id']."'";
		$db->Execute($sql_update_new_status);
		
		//Update table ORDERS STATUS HISTORY
		$sql_insert_new_status="insert into " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " values ( '', '".$result->fields['orders_id']."', '".$this_auto_post_order_status_id."', now(), 0, '' )";
		$db->Execute($sql_insert_new_status);
		
		} //End of change-order-status-ID
		
		
		
		
		/* SEND EMAIL */
		
		/* Finally, let's send the email */
		
		//If in test mode, send email to store owner only
		if ($this_auto_mode == 'test') {
				
		zen_mail(STORE_NAME, $admin_email, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
    
		} else if ($this_auto_mode == 'live') { //else if in live mode, send email to customer
		
		zen_mail($name, $to, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
		
		}
		
		//If in live mode and admin receives copy, send email to store owner as well
		if ( (AUTO_ADMIN_COPY == 'true') && ($this_auto_mode == 'live')  ) {
		
		zen_mail(STORE_NAME, $admin_email, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
		
		}
    	
    	//Write prepared data to log
		log_cws($logdata);
		
		
		
		} //End continue_execution
	
	//Go to next email
	$result->MoveNext();

	} //End while-there-are-possible-emails

//End the log
log_cws("End: ".date("Y-m-d H:i:s"). "\r\n" . "\r\n" . "\r\n");

/* ******************************************************************** */
/* CODE ABOVE HERE IS INTENTIONALLY DUPLICATED THREE TIMES IN THIS FILE */
/* ******************************************************************** */

} // end of preset #1









//If preset #2 enabled
if (AUTO_ENABLE_PRESET_2 == 'true') {

//Set these variables with settings from autoresponder+ admin
$this_auto_mode=AUTO_MODE_2;
$this_auto_order_status_id=AUTO_ORDER_STATUS_ID_2;
$this_auto_post_order_status_id=AUTO_POST_ORDER_STATUS_ID_2;
$this_auto_days_after=AUTO_DAYS_AFTER_2;
$this_auto_subscribed=AUTO_SUBSCRIBED_2;
$this_auto_subject=AUTO_SUBJECT_2;
$this_auto_include_name=AUTO_INCLUDE_NAME_2;
$this_auto_include_product_details=AUTO_INCLUDE_PRODUCT_DETAILS_2;
$this_auto_message_text_1=AUTO_MESSAGE_TEXT_PRE_2;
$this_auto_message_text_2=AUTO_MESSAGE_TEXT_POST_2;
$this_auto_message_html_1=AUTO_MESSAGE_HTML_PRE_2;
$this_auto_message_html_2=AUTO_MESSAGE_HTML_POST_2;
$this_auto_state=AUTO_STATE_2;
$this_auto_location_restrict=AUTO_LOCATION_RESTRICT_2;
$location=AUTO_LOCATION_2;
$coupon_id=AUTO_COUPON_2;
$products_as_url=AUTO_PRODUCT_URL_2;
$this_auto_product_restrict=AUTO_PRODUCT_RESTRICT_2;

//Set preset as preset#2
$preset="preset #2";

/* ******************************************************************** */
/* CODE BELOW HERE IS INTENTIONALLY DUPLICATED THREE TIMES IN THIS FILE */
/* ******************************************************************** */

	//Prepare log mode data
	if ($this_auto_mode == 'live') { //If in live mode
	$log_mode="(live mode)"; //Set log mode as live
	} else {
	$log_mode="(test mode)"; //Set log mode as test
	}

	//Call log function to write which preset it is, whether in live or test mode, and the date/time
	log_cws("Start " . $preset . " " . $log_mode . ": ".date("Y-m-d H:i:s")." ");



	//Calculate which day of the year the order should have been
	$order_day=mktime()-($this_auto_days_after*86400);
	$order_day=date("Y-m-d",$order_day);
	
	//Calculate which day of the year the account should have been
	$account_day=mktime()-($this_auto_days_after*86400);
	$account_day=date("Y-m-d",$account_day);
	
	/* The following SQL statements select the relevant data from the database according to autoresponder+ admin settings */
	
	//If in order mode and there is no location restriction
	if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'no') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status";
	
	//If in order mode and location is restricted to zone
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'to zone') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in order mode and location is restricted to country
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'to country') ) {
		
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in order mode and location is restricted from zone
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'from zone') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in order mode and location is restricted from country
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'from country') ) {
		
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";

	//If in account mode and there is no location restriction
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'no') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id";
	
	//If in account mode and location is restricted to zone
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'to zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in account mode and location is restricted to country
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'to country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in account mode and location is restricted from zone
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'from zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in account mode and location is restricted from country
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'from country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";
	
	//If in account-no-order mode and there is no location restriction
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'no') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id";
	
	//If in account-no-order mode and location is restricted to zone
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'to zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted to country
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'to country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted from zone
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'from zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted from country
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'from country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";
	
	}
	
	//Execute whichever SQL statement made it (if any)
	$result=$db->Execute($sql);
	
	//While there are matches in the executed SQL statement
	while (!$result->EOF) {
	
		
		//$continue_execution
		//Set this as true initially. If it ever gets changed to false, email won't send.
		$continue_execution = TRUE;
	
		
		
		/* First, let's get the customer's name */
	
		//If in order mode
		if ($this_auto_state == 'order') {
		
		$sql3="select customers_name from " . AUTO_TABLE_ORDERS . " where orders_id='".$result->fields['orders_id']."'";
		$result3=$db->Execute($sql3);
		
		$name=$result3->fields['customers_name'];
		
		} else { //Else if in account or account-no-order mode
		
		$first=$result->fields['customers_firstname'];
		$second=$result->fields['customers_lastname'];
		
		//Must concatenate
		$name= $first . " " . $second;
		
		} //End of get customer's name
		
		
		
		/* Now, let's determine whether the customer is subscribed to the newsletter */
		
		//Get newsletter status (step 1)
		$sql9="select customers_newsletter from " . AUTO_TABLE_CUSTOMERS . " where customers_email_address='".$result->fields['customers_email_address']."'";
		$result9=$db->Execute($sql9);
		
		//Get newsletter status (step 2)
		$subscribed=$result9->fields['customers_newsletter'];
		
		//Get newsletter status (step 3)
		if ( $subscribed == '1' ) {
		$subscribed = "true";
		} else {
		$subscribed = "false";
		}
		
		
		
		/* PREPARE EMAIL DATA */
		$sender = STORE_OWNER_EMAIL_ADDRESS; //From
		$to = $result->fields['customers_email_address']; //To
		$subject = $this_auto_subject; //Subject
		
		
		
		/* PRODUCT DETAILS IN EMAIL */
		
		//If in order mode AND product details should be included in email
		if ( ($this_auto_state == 'order') && ($this_auto_include_product_details == 'true') ) {
		
		//Insert a new paragraph break before product details are displayed
		$product_details_text = "\r\n" . "\r\n"; //If text email
		$product_details_html = "<p />"; //If html email
		
		//Get the order ID
		$prod_order_id = $result->fields['orders_id'];
		
		//Get product details for order
		$sql_prod="SELECT DISTINCT OP.products_quantity, OP.products_name, OP.products_id
		FROM " . AUTO_TABLE_ORDERS_PRODUCTS . " OP 
		WHERE OP.orders_id = '".$prod_order_id."'";
	
		//Exectute SQL statement
		$result_prod=$db->Execute($sql_prod);
		
		//While there are products
		while(!$result_prod->EOF) {
		
		$product_quantity = $result_prod->fields['products_quantity']; //Get quantity of product
		$product_name = $result_prod->fields['products_name']; //Get name of product
		
		//If products should be hyperlinked back to store
		if ( ($products_as_url == 'yes, to product page') || ($products_as_url == 'yes, to product review page') ) {
		
		$products_id = $result_prod->fields['products_id']; //Get product ID
		$products_id_part = "&products_id=" . $products_id; //Build part of URL that contains product ID
		
		//Prepare SQL to get the category of the product
		$sql_find_category="SELECT DISTINCT PTC.categories_id
		FROM " . AUTO_TABLE_PRODUCTS_TO_CATEGORIES . " PTC 
		WHERE PTC.products_id = '".$products_id."'";
		
		//Exectute SQL statement
		$sql_find_category_result=$db->Execute($sql_find_category);
		
		//Finally get the category of the product
		$category_id = $sql_find_category_result->fields['categories_id'];
		
		/* Build first part of URL */
		//If hyperlink is to main product page
		if ($products_as_url == 'yes, to product page') {
		
		//If store is not in a directory
		if ( dirname($_SERVER['PHP_SELF']) == "/" ) {
		$initial_url = HTTP_SERVER . "/index.php?main_page=product_info&cPath=";
		} else {
		$initial_url = HTTP_SERVER . dirname($_SERVER['PHP_SELF']) . "/index.php?main_page=product_info&cPath=";
		}
		
		} else { //Hyperlink is to product review page
		//If store is not in a directory
		if ( dirname($_SERVER['PHP_SELF']) == "/" ) {
		$initial_url = HTTP_SERVER . "/index.php?main_page=product_reviews_write&cPath=";
		} else {
		$initial_url = HTTP_SERVER . dirname($_SERVER['PHP_SELF']) . "/index.php?main_page=product_reviews_write&cPath=";
		}
		}
		
		//Build the text version of complete URL
		$product_text_url = $initial_url . $category_id . $products_id_part;
		
		//Product details to be included in email:
		$product_details_text = $product_details_text . $product_quantity . "x " . $product_name . "\r\n" . $product_text_url . "\r\n" . "\r\n";
		$product_details_html = $product_details_html . $product_quantity . "x " . "<a href=\"$product_text_url\" target=\"_blank\">$product_name</a>" . "<br />";
				
		} else { //No hyperlinks in email
		
		//Product details to be included in email:
		$product_details_text = $product_details_text . $product_quantity . "x " . $product_name . "\r\n";
		$product_details_html = $product_details_html . $product_quantity . "x " . $product_name . "<br />";
		
		} //End of no-hyperlinks-in-email
		
		//Go to next product
		$result_prod->MoveNext();
		}
		
		//Insert a new paragraph break after product details are displayed
		$product_details_text = $product_details_text . "\r\n"; //If text email
		$product_details_html = $product_details_html . "<br />"; //If html email
		
		} else { //No product details to be included in email
		$product_details_text='';
		$product_details_html='';
		}
		
		
		
		/* CUSTOMER DISPLAY NAME */
		
		/* Set customer display name, according to autoresponder+ admin settings */
		
		//Get customer's first name from full name, should it be needed
		$words = split('[ ]+', $name);
		$words[0];
		
		//Customer name options for text email
		if ($this_auto_include_name == '0') {
		$text_message = $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '1') {
		$text_message = $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '2') {
		$text_message = $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '3') {
		$text_message = AUTO_GREETING_HI . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '4') {
		$text_message = AUTO_GREETING_HI . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '5') {
		$text_message = AUTO_GREETING_HELLO . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '6') {
		$text_message = AUTO_GREETING_HELLO . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '7') {
		$text_message = $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '8') {
		$text_message = $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '9') {
		$text_message = AUTO_GREETING_HI . " " . $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '10') {
		$text_message = AUTO_GREETING_HI . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '11') {
		$text_message = AUTO_GREETING_HELLO . " " . $words[0]  . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '12') {
		$text_message = AUTO_GREETING_HELLO . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '13') {
		$text_message = AUTO_GREETING_DEAR . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '14') {
		$text_message = AUTO_GREETING_DEAR . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '15') {
		$text_message = AUTO_GREETING_DEAR . " " . $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '16') {
		$text_message = AUTO_GREETING_DEAR . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		}
		
		//Customer name options for html email		
		if ($this_auto_include_name == '0') {
		$html_message = $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '1') {
		$html_message = $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '2') {
		$html_message = $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '3') {
		$html_message = AUTO_GREETING_HI . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '4') {
		$html_message = AUTO_GREETING_HI . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '5') {
		$html_message = AUTO_GREETING_HELLO . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '6') {
		$html_message = AUTO_GREETING_HELLO . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '7') {
		$html_message = $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '8') {
		$html_message = $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '9') {
		$html_message = AUTO_GREETING_HI . " " . $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '10') {
		$html_message = AUTO_GREETING_HI . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '11') {
		$html_message = AUTO_GREETING_HELLO . " " . $words[0]  . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '12') {
		$html_message = AUTO_GREETING_HELLO . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '13') {
		$html_message = AUTO_GREETING_DEAR . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '14') {
		$html_message = AUTO_GREETING_DEAR . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '15') {
		$html_message = AUTO_GREETING_DEAR . " " . $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '16') {
		$html_message = AUTO_GREETING_DEAR . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		
		}
		
		
		
		/* NEWSLETTER SUBSCRIPTION CHECK */
		
		//If customer must be subscribed to newsletter but is not, don't go any further
		if ( ($this_auto_subscribed == 'true') && ($subscribed == "false") ) {
		$continue_execution = FALSE;
		}
		
		
		
		/* PRODUCT RESTRICTION */
		
		//If in order mode and there are product IDs to restrict
		if ( ($this_auto_state == 'order') && ($this_auto_product_restrict != '') ) {
		
		//First, remove any commas from user-submitted product IDs and convert to an array
		$ids = explode(",", $this_auto_product_restrict);
		
		//Get the order ID
		$prod_order_id = $result->fields['orders_id'];
		
		//SQL statement to get the product IDs of the order
		$sql_prod="SELECT DISTINCT OP.products_id 
		FROM " . AUTO_TABLE_ORDERS_PRODUCTS . " OP 
		WHERE OP.orders_id = '".$prod_order_id."'";
	
		//Execute SQL statement to get product IDs
		$result_prod=$db->Execute($sql_prod);
		
		//Okay so let's assume there is no match until found
		$product_match = FALSE;
		
		//While there are products
		while(!$result_prod->EOF) {
		
		$products_id = $result_prod->fields['products_id']; //Get product ID from the order
		
		//Even if one of the user-submitted product IDs match, everything is good
		if (in_array($products_id, $ids)) { //If the product ID from the order is in the array of user-submitted product IDs
		$product_match = TRUE; //A match
		}
		
		//Go to next product
		$result_prod->MoveNext();
		
		} //End of while-there-are-products
		
		//So if there was no match at all, don't send any emails
		if ($product_match) {
		} else {
		$continue_execution = FALSE;
		}
		
		} //End of product restriction
		
		
		
		/* ---------- IMPORTANT ---------- */
		//The below code actually performs actions (including sending emails), e.g. create coupon, update order status.
		//So ONLY proceed if email is definitely going to be sent
		if ($continue_execution) {
		
		
		/* COUPON CODE */
		
		//If coupon code has been entered in admin, and text email contains the coupon keyword
		//Create a unique coupon and replace the keyword with the new coupon's code
		if ( ($coupon_id != '') && (strstr($text_message,$coupon_key)) ) {
		
		//Get the example coupon from the database to replicate its settings
		$sql_get_coupon="select * from " . AUTO_TABLE_COUPONS . " where coupon_code='".$coupon_id."'";
		$get_coupon_result=$db->Execute($sql_get_coupon);
		
		//Get the example coupon's description from the database
		$sql_get_coupon_desc="select * from " . AUTO_TABLE_COUPONS_DESCRIPTION . " where coupon_id='".$get_coupon_result->fields['coupon_id']."'";
		$get_coupon_desc_result=$db->Execute($sql_get_coupon_desc);
		
		//Get the example coupon's restrictions from the database
		$sql_get_coupon_restrict="select * from " . AUTO_TABLE_COUPON_RESTRICT . " where coupon_id='".$get_coupon_result->fields['coupon_id']."'";
		$get_coupon_restrict_result=$db->Execute($sql_get_coupon_restrict);
		
		//Generate random integer between 10,000 and 999,999
		$new_coupon_code = rand(10000, 999999);
		
		//Add a year from now for the new coupon's expiry date
		$this_moment = date("Y-m-d");
		$next_year = strtotime ( '+1 year' , strtotime ( $this_moment ) ) ;
		$next_year = date ( 'Y-m-j' , $next_year );
		
		//Insert new coupon into db based on retrieved coupon but with new coupon code
		$sql_insert_coupon="insert into " . AUTO_TABLE_COUPONS . " values ( '', '".$get_coupon_result->fields['coupon_type']."', '".$new_coupon_code."', '".$get_coupon_result->fields['coupon_amount']."', '".$get_coupon_result->fields['coupon_minimum_order']."', now(), '".$next_year."', '".$get_coupon_result->fields['uses_per_coupon']."', '".$get_coupon_result->fields['uses_per_user']."', '".$get_coupon_result->fields['restrict_to_products']."', '".$get_coupon_result->fields['restrict_to_categories']."', '".$get_coupon_result->fields['restrict_to_customers']."', '".$get_coupon_result->fields['coupon_active']."', now(), now(), '".$get_coupon_result->fields['coupon_zone_restriction']."' )";
		$db->Execute($sql_insert_coupon);
		
		//Determine the newest autoincremented coupon id so the id for the coupon description and restriction can match
		$sql_get_coupon_new_id="select * from " . AUTO_TABLE_COUPONS . " where coupon_code = '".$new_coupon_code."'";
		$get_coupon_new_id_result=$db->Execute($sql_get_coupon_new_id);
		$new_coupon_id = $get_coupon_new_id_result->fields['coupon_id'];
		
		//Insert new coupon description into db based on retrieved coupon
		$sql_insert_coupon_desc="insert into " . AUTO_TABLE_COUPONS_DESCRIPTION . " values ( '".$new_coupon_id."', '1', '".$get_coupon_desc_result->fields['coupon_name']."', '".$get_coupon_desc_result->fields['coupon_description']."' )";
		$db->Execute($sql_insert_coupon_desc);
		
		//While there are coupon restrictions (if any)
		while(!$get_coupon_restrict_result->EOF) {
		
		//Insert new coupon restriction
		$sql_insert_coupon_restrict="insert into " . AUTO_TABLE_COUPON_RESTRICT . " values ( '', '".$new_coupon_id."', '".$get_coupon_restrict_result->fields['product_id']."', '".$get_coupon_restrict_result->fields['category_id']."', '".$get_coupon_restrict_result->fields['coupon_restrict']."' )";
		$db->Execute($sql_insert_coupon_restrict);
		
		//Go to next coupon restriction
		$get_coupon_restrict_result->MoveNext();
		
		} //End of while-there-are-coupon-restrictions
		
		//Replace coupon keyword in text message with new coupon code
		$text_message = str_replace($coupon_key, $new_coupon_code, $text_message);
		
		//Repeat for html message
		$html_message = str_replace($coupon_key, $new_coupon_code, $html_message);
		
		}
		
		
		
		/* PREPARE LOG DATA */
		
		//Prepare log data
		if ($this_auto_state == 'order') { //If in order mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Post-Order" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Location Restricted: " . $this_auto_location_restrict . "\r\n" . "Location: " . $location . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Order ID: " . $result->fields['orders_id'] . "\r\n" . "Date of Order: " . $order_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";
		} else if ($this_auto_state == 'account-no-order') { //If in account-no-order mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Account No Order" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Account Date: " . $account_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";
		} else if ($this_auto_state == 'account') { //If in account mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Account" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Account Date: " . $account_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";		
		}
		
		
		
		
		/* UPDATE ORDER STATUS */
		
		//If in order mode and configured order status ID is different to configured post-order status ID, update database with new status
		if ( ($this_auto_state == 'order') && ($this_auto_order_status_id != $this_auto_post_order_status_id) ) {
		
		//Update table ORDERS
		$sql_update_new_status="UPDATE " . AUTO_TABLE_ORDERS . " SET orders_status = '".$this_auto_post_order_status_id."' WHERE orders_id = '".$result->fields['orders_id']."'";
		$db->Execute($sql_update_new_status);
		
		//Update table ORDERS STATUS HISTORY
		$sql_insert_new_status="insert into " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " values ( '', '".$result->fields['orders_id']."', '".$this_auto_post_order_status_id."', now(), 0, '' )";
		$db->Execute($sql_insert_new_status);
		
		} //End of change-order-status-ID
		
		
		
		
		/* SEND EMAIL */
		
		/* Finally, let's send the email */
		
		//If in test mode, send email to store owner only
		if ($this_auto_mode == 'test') {
				
		zen_mail(STORE_NAME, $admin_email, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
    
		} else if ($this_auto_mode == 'live') { //else if in live mode, send email to customer
		
		zen_mail($name, $to, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
		
		}
		
		//If in live mode and admin receives copy, send email to store owner as well
		if ( (AUTO_ADMIN_COPY == 'true') && ($this_auto_mode == 'live')  ) {
		
		zen_mail(STORE_NAME, $admin_email, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
		
		}
    	
    	//Write prepared data to log
		log_cws($logdata);
		
		
		
		} //End continue_execution
	
	//Go to next email
	$result->MoveNext();

	} //End while-there-are-possible-emails

//End the log
log_cws("End: ".date("Y-m-d H:i:s"). "\r\n" . "\r\n" . "\r\n");

/* ******************************************************************** */
/* CODE ABOVE HERE IS INTENTIONALLY DUPLICATED THREE TIMES IN THIS FILE */
/* ******************************************************************** */

} // end of preset #2







//If preset #3 enabled
if (AUTO_ENABLE_PRESET_3 == 'true') {

//Set these variables with settings from autoresponder+ admin
$this_auto_mode=AUTO_MODE_3;
$this_auto_order_status_id=AUTO_ORDER_STATUS_ID_3;
$this_auto_post_order_status_id=AUTO_POST_ORDER_STATUS_ID_3;
$this_auto_days_after=AUTO_DAYS_AFTER_3;
$this_auto_subscribed=AUTO_SUBSCRIBED_3;
$this_auto_subject=AUTO_SUBJECT_3;
$this_auto_include_name=AUTO_INCLUDE_NAME_3;
$this_auto_include_product_details=AUTO_INCLUDE_PRODUCT_DETAILS_3;
$this_auto_message_text_1=AUTO_MESSAGE_TEXT_PRE_3;
$this_auto_message_text_2=AUTO_MESSAGE_TEXT_POST_3;
$this_auto_message_html_1=AUTO_MESSAGE_HTML_PRE_3;
$this_auto_message_html_2=AUTO_MESSAGE_HTML_POST_3;
$this_auto_state=AUTO_STATE_3;
$this_auto_location_restrict=AUTO_LOCATION_RESTRICT_3;
$location=AUTO_LOCATION_3;
$coupon_id=AUTO_COUPON_3;
$products_as_url=AUTO_PRODUCT_URL_3;
$this_auto_product_restrict=AUTO_PRODUCT_RESTRICT_3;

//Set preset as preset#3
$preset="preset #3";

/* ******************************************************************** */
/* CODE BELOW HERE IS INTENTIONALLY DUPLICATED THREE TIMES IN THIS FILE */
/* ******************************************************************** */

	//Prepare log mode data
	if ($this_auto_mode == 'live') { //If in live mode
	$log_mode="(live mode)"; //Set log mode as live
	} else {
	$log_mode="(test mode)"; //Set log mode as test
	}

	//Call log function to write which preset it is, whether in live or test mode, and the date/time
	log_cws("Start " . $preset . " " . $log_mode . ": ".date("Y-m-d H:i:s")." ");



	//Calculate which day of the year the order should have been
	$order_day=mktime()-($this_auto_days_after*86400);
	$order_day=date("Y-m-d",$order_day);
	
	//Calculate which day of the year the account should have been
	$account_day=mktime()-($this_auto_days_after*86400);
	$account_day=date("Y-m-d",$account_day);
	
	/* The following SQL statements select the relevant data from the database according to autoresponder+ admin settings */
	
	//If in order mode and there is no location restriction
	if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'no') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status";
	
	//If in order mode and location is restricted to zone
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'to zone') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in order mode and location is restricted to country
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'to country') ) {
		
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in order mode and location is restricted from zone
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'from zone') ) {
	
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in order mode and location is restricted from country
	} else if ( ($this_auto_state == 'order') && ($this_auto_location_restrict == 'from country') ) {
		
	$sql="SELECT DISTINCT OSH.orders_id, O.customers_email_address, O.customers_name  
	FROM " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " OSH, " . AUTO_TABLE_ORDERS . " O, " . AUTO_TABLE_CUSTOMERS . " c, " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co 
	WHERE OSH.date_added like '".$order_day."%'
	AND OSH.orders_status_id IN (". AUTO_ORDER_STATUS_ID .") 
	AND OSH.orders_id=O.orders_id 
	AND OSH.orders_status_id=O.orders_status 
	AND O.customers_id=c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";

	//If in account mode and there is no location restriction
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'no') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id";
	
	//If in account mode and location is restricted to zone
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'to zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in account mode and location is restricted to country
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'to country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in account mode and location is restricted from zone
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'from zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in account mode and location is restricted from country
	} else if ( ($this_auto_state == 'account') && ($this_auto_location_restrict == 'from country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";
	
	//If in account-no-order mode and there is no location restriction
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'no') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id";
	
	//If in account-no-order mode and location is restricted to zone
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'to zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted to country
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'to country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted from zone
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'from zone') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_ZONES . " z, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_zone_id=z.zone_id 
	AND z.zone_name NOT LIKE '".$location."%'";
	
	//If in account-no-order mode and location is restricted from country
	} else if ( ($this_auto_state == 'account-no-order') && ($this_auto_location_restrict == 'from country') ) {

	$sql="SELECT DISTINCT c.customers_email_address, c.customers_firstname, c.customers_lastname, c.customers_id 
	FROM " . AUTO_TABLE_ADDRESS_BOOK . " ab, " . AUTO_TABLE_COUNTRIES . " co, " . AUTO_TABLE_CUSTOMERS_INFO . " ci, " . AUTO_TABLE_CUSTOMERS . " c left join " . AUTO_TABLE_ORDERS . " o on c.customers_id = o.customers_id 
	WHERE o.customers_id is NULL 
	AND ci.customers_info_date_account_created LIKE '".$account_day."%' 
	AND ci.customers_info_id = c.customers_id 
	AND c.customers_default_address_id = ab.address_book_id 
	AND ab.entry_country_id=co.countries_id 
	AND co.countries_name NOT LIKE '".$location."%'";
	
	}
	
	//Execute whichever SQL statement made it (if any)
	$result=$db->Execute($sql);
	
	//While there are matches in the executed SQL statement
	while (!$result->EOF) {
	
		
		//$continue_execution
		//Set this as true initially. If it ever gets changed to false, email won't send.
		$continue_execution = TRUE;
	
		
		
		/* First, let's get the customer's name */
	
		//If in order mode
		if ($this_auto_state == 'order') {
		
		$sql3="select customers_name from " . AUTO_TABLE_ORDERS . " where orders_id='".$result->fields['orders_id']."'";
		$result3=$db->Execute($sql3);
		
		$name=$result3->fields['customers_name'];
		
		} else { //Else if in account or account-no-order mode
		
		$first=$result->fields['customers_firstname'];
		$second=$result->fields['customers_lastname'];
		
		//Must concatenate
		$name= $first . " " . $second;
		
		} //End of get customer's name
		
		
		
		/* Now, let's determine whether the customer is subscribed to the newsletter */
		
		//Get newsletter status (step 1)
		$sql9="select customers_newsletter from " . AUTO_TABLE_CUSTOMERS . " where customers_email_address='".$result->fields['customers_email_address']."'";
		$result9=$db->Execute($sql9);
		
		//Get newsletter status (step 2)
		$subscribed=$result9->fields['customers_newsletter'];
		
		//Get newsletter status (step 3)
		if ( $subscribed == '1' ) {
		$subscribed = "true";
		} else {
		$subscribed = "false";
		}
		
		
		
		/* PREPARE EMAIL DATA */
		$sender = STORE_OWNER_EMAIL_ADDRESS; //From
		$to = $result->fields['customers_email_address']; //To
		$subject = $this_auto_subject; //Subject
		
		
		
		/* PRODUCT DETAILS IN EMAIL */
		
		//If in order mode AND product details should be included in email
		if ( ($this_auto_state == 'order') && ($this_auto_include_product_details == 'true') ) {
		
		//Insert a new paragraph break before product details are displayed
		$product_details_text = "\r\n" . "\r\n"; //If text email
		$product_details_html = "<p />"; //If html email
		
		//Get the order ID
		$prod_order_id = $result->fields['orders_id'];
		
		//Get product details for order
		$sql_prod="SELECT DISTINCT OP.products_quantity, OP.products_name, OP.products_id
		FROM " . AUTO_TABLE_ORDERS_PRODUCTS . " OP 
		WHERE OP.orders_id = '".$prod_order_id."'";
	
		//Exectute SQL statement
		$result_prod=$db->Execute($sql_prod);
		
		//While there are products
		while(!$result_prod->EOF) {
		
		$product_quantity = $result_prod->fields['products_quantity']; //Get quantity of product
		$product_name = $result_prod->fields['products_name']; //Get name of product
		
		//If products should be hyperlinked back to store
		if ( ($products_as_url == 'yes, to product page') || ($products_as_url == 'yes, to product review page') ) {
		
		$products_id = $result_prod->fields['products_id']; //Get product ID
		$products_id_part = "&products_id=" . $products_id; //Build part of URL that contains product ID
		
		//Prepare SQL to get the category of the product
		$sql_find_category="SELECT DISTINCT PTC.categories_id
		FROM " . AUTO_TABLE_PRODUCTS_TO_CATEGORIES . " PTC 
		WHERE PTC.products_id = '".$products_id."'";
		
		//Exectute SQL statement
		$sql_find_category_result=$db->Execute($sql_find_category);
		
		//Finally get the category of the product
		$category_id = $sql_find_category_result->fields['categories_id'];
		
		/* Build first part of URL */
		//If hyperlink is to main product page
		if ($products_as_url == 'yes, to product page') {
		
		//If store is not in a directory
		if ( dirname($_SERVER['PHP_SELF']) == "/" ) {
		$initial_url = HTTP_SERVER . "/index.php?main_page=product_info&cPath=";
		} else {
		$initial_url = HTTP_SERVER . dirname($_SERVER['PHP_SELF']) . "/index.php?main_page=product_info&cPath=";
		}
		
		} else { //Hyperlink is to product review page
		//If store is not in a directory
		if ( dirname($_SERVER['PHP_SELF']) == "/" ) {
		$initial_url = HTTP_SERVER . "/index.php?main_page=product_reviews_write&cPath=";
		} else {
		$initial_url = HTTP_SERVER . dirname($_SERVER['PHP_SELF']) . "/index.php?main_page=product_reviews_write&cPath=";
		}
		}
		
		//Build the text version of complete URL
		$product_text_url = $initial_url . $category_id . $products_id_part;
		
		//Product details to be included in email:
		$product_details_text = $product_details_text . $product_quantity . "x " . $product_name . "\r\n" . $product_text_url . "\r\n" . "\r\n";
		$product_details_html = $product_details_html . $product_quantity . "x " . "<a href=\"$product_text_url\" target=\"_blank\">$product_name</a>" . "<br />";
				
		} else { //No hyperlinks in email
		
		//Product details to be included in email:
		$product_details_text = $product_details_text . $product_quantity . "x " . $product_name . "\r\n";
		$product_details_html = $product_details_html . $product_quantity . "x " . $product_name . "<br />";
		
		} //End of no-hyperlinks-in-email
		
		//Go to next product
		$result_prod->MoveNext();
		}
		
		//Insert a new paragraph break after product details are displayed
		$product_details_text = $product_details_text . "\r\n"; //If text email
		$product_details_html = $product_details_html . "<br />"; //If html email
		
		} else { //No product details to be included in email
		$product_details_text='';
		$product_details_html='';
		}
		
		
		
		/* CUSTOMER DISPLAY NAME */
		
		/* Set customer display name, according to autoresponder+ admin settings */
		
		//Get customer's first name from full name, should it be needed
		$words = split('[ ]+', $name);
		$words[0];
		
		//Customer name options for text email
		if ($this_auto_include_name == '0') {
		$text_message = $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '1') {
		$text_message = $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '2') {
		$text_message = $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '3') {
		$text_message = AUTO_GREETING_HI . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '4') {
		$text_message = AUTO_GREETING_HI . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '5') {
		$text_message = AUTO_GREETING_HELLO . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '6') {
		$text_message = AUTO_GREETING_HELLO . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '7') {
		$text_message = $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '8') {
		$text_message = $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '9') {
		$text_message = AUTO_GREETING_HI . " " . $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '10') {
		$text_message = AUTO_GREETING_HI . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '11') {
		$text_message = AUTO_GREETING_HELLO . " " . $words[0]  . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '12') {
		$text_message = AUTO_GREETING_HELLO . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '13') {
		$text_message = AUTO_GREETING_DEAR . " " . $words[0] . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '14') {
		$text_message = AUTO_GREETING_DEAR . " " . $name . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '15') {
		$text_message = AUTO_GREETING_DEAR . " " . $words[0] . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		} else if ($this_auto_include_name == '16') {
		$text_message = AUTO_GREETING_DEAR . " " . $name . "," . "\r\n" . "\r\n" . $this_auto_message_text_1 . $product_details_text . $this_auto_message_text_2;
		}
		
		//Customer name options for html email		
		if ($this_auto_include_name == '0') {
		$html_message = $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '1') {
		$html_message = $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '2') {
		$html_message = $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '3') {
		$html_message = AUTO_GREETING_HI . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '4') {
		$html_message = AUTO_GREETING_HI . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '5') {
		$html_message = AUTO_GREETING_HELLO . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '6') {
		$html_message = AUTO_GREETING_HELLO . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '7') {
		$html_message = $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '8') {
		$html_message = $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '9') {
		$html_message = AUTO_GREETING_HI . " " . $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '10') {
		$html_message = AUTO_GREETING_HI . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '11') {
		$html_message = AUTO_GREETING_HELLO . " " . $words[0]  . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '12') {
		$html_message = AUTO_GREETING_HELLO . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '13') {
		$html_message = AUTO_GREETING_DEAR . " " . $words[0] . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '14') {
		$html_message = AUTO_GREETING_DEAR . " " . $name . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '15') {
		$html_message = AUTO_GREETING_DEAR . " " . $words[0] . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		} else if ($this_auto_include_name == '16') {
		$html_message = AUTO_GREETING_DEAR . " " . $name . "," . "<p />" . $this_auto_message_html_1 . $product_details_html . $this_auto_message_html_2;
		
		}
		
		
		
		/* NEWSLETTER SUBSCRIPTION CHECK */
		
		//If customer must be subscribed to newsletter but is not, don't go any further
		if ( ($this_auto_subscribed == 'true') && ($subscribed == "false") ) {
		$continue_execution = FALSE;
		}
		
		
		
		/* PRODUCT RESTRICTION */
		
		//If in order mode and there are product IDs to restrict
		if ( ($this_auto_state == 'order') && ($this_auto_product_restrict != '') ) {
		
		//First, remove any commas from user-submitted product IDs and convert to an array
		$ids = explode(",", $this_auto_product_restrict);
		
		//Get the order ID
		$prod_order_id = $result->fields['orders_id'];
		
		//SQL statement to get the product IDs of the order
		$sql_prod="SELECT DISTINCT OP.products_id 
		FROM " . AUTO_TABLE_ORDERS_PRODUCTS . " OP 
		WHERE OP.orders_id = '".$prod_order_id."'";
	
		//Execute SQL statement to get product IDs
		$result_prod=$db->Execute($sql_prod);
		
		//Okay so let's assume there is no match until found
		$product_match = FALSE;
		
		//While there are products
		while(!$result_prod->EOF) {
		
		$products_id = $result_prod->fields['products_id']; //Get product ID from the order
		
		//Even if one of the user-submitted product IDs match, everything is good
		if (in_array($products_id, $ids)) { //If the product ID from the order is in the array of user-submitted product IDs
		$product_match = TRUE; //A match
		}
		
		//Go to next product
		$result_prod->MoveNext();
		
		} //End of while-there-are-products
		
		//So if there was no match at all, don't send any emails
		if ($product_match) {
		} else {
		$continue_execution = FALSE;
		}
		
		} //End of product restriction
		
		
		
		/* ---------- IMPORTANT ---------- */
		//The below code actually performs actions (including sending emails), e.g. create coupon, update order status.
		//So ONLY proceed if email is definitely going to be sent
		if ($continue_execution) {
		
		
		/* COUPON CODE */
		
		//If coupon code has been entered in admin, and text email contains the coupon keyword
		//Create a unique coupon and replace the keyword with the new coupon's code
		if ( ($coupon_id != '') && (strstr($text_message,$coupon_key)) ) {
		
		//Get the example coupon from the database to replicate its settings
		$sql_get_coupon="select * from " . AUTO_TABLE_COUPONS . " where coupon_code='".$coupon_id."'";
		$get_coupon_result=$db->Execute($sql_get_coupon);
		
		//Get the example coupon's description from the database
		$sql_get_coupon_desc="select * from " . AUTO_TABLE_COUPONS_DESCRIPTION . " where coupon_id='".$get_coupon_result->fields['coupon_id']."'";
		$get_coupon_desc_result=$db->Execute($sql_get_coupon_desc);
		
		//Get the example coupon's restrictions from the database
		$sql_get_coupon_restrict="select * from " . AUTO_TABLE_COUPON_RESTRICT . " where coupon_id='".$get_coupon_result->fields['coupon_id']."'";
		$get_coupon_restrict_result=$db->Execute($sql_get_coupon_restrict);
		
		//Generate random integer between 10,000 and 999,999
		$new_coupon_code = rand(10000, 999999);
		
		//Add a year from now for the new coupon's expiry date
		$this_moment = date("Y-m-d");
		$next_year = strtotime ( '+1 year' , strtotime ( $this_moment ) ) ;
		$next_year = date ( 'Y-m-j' , $next_year );
		
		//Insert new coupon into db based on retrieved coupon but with new coupon code
		$sql_insert_coupon="insert into " . AUTO_TABLE_COUPONS . " values ( '', '".$get_coupon_result->fields['coupon_type']."', '".$new_coupon_code."', '".$get_coupon_result->fields['coupon_amount']."', '".$get_coupon_result->fields['coupon_minimum_order']."', now(), '".$next_year."', '".$get_coupon_result->fields['uses_per_coupon']."', '".$get_coupon_result->fields['uses_per_user']."', '".$get_coupon_result->fields['restrict_to_products']."', '".$get_coupon_result->fields['restrict_to_categories']."', '".$get_coupon_result->fields['restrict_to_customers']."', '".$get_coupon_result->fields['coupon_active']."', now(), now(), '".$get_coupon_result->fields['coupon_zone_restriction']."' )";
		$db->Execute($sql_insert_coupon);
		
		//Determine the newest autoincremented coupon id so the id for the coupon description and restriction can match
		$sql_get_coupon_new_id="select * from " . AUTO_TABLE_COUPONS . " where coupon_code = '".$new_coupon_code."'";
		$get_coupon_new_id_result=$db->Execute($sql_get_coupon_new_id);
		$new_coupon_id = $get_coupon_new_id_result->fields['coupon_id'];
		
		//Insert new coupon description into db based on retrieved coupon
		$sql_insert_coupon_desc="insert into " . AUTO_TABLE_COUPONS_DESCRIPTION . " values ( '".$new_coupon_id."', '1', '".$get_coupon_desc_result->fields['coupon_name']."', '".$get_coupon_desc_result->fields['coupon_description']."' )";
		$db->Execute($sql_insert_coupon_desc);
		
		//While there are coupon restrictions (if any)
		while(!$get_coupon_restrict_result->EOF) {
		
		//Insert new coupon restriction
		$sql_insert_coupon_restrict="insert into " . AUTO_TABLE_COUPON_RESTRICT . " values ( '', '".$new_coupon_id."', '".$get_coupon_restrict_result->fields['product_id']."', '".$get_coupon_restrict_result->fields['category_id']."', '".$get_coupon_restrict_result->fields['coupon_restrict']."' )";
		$db->Execute($sql_insert_coupon_restrict);
		
		//Go to next coupon restriction
		$get_coupon_restrict_result->MoveNext();
		
		} //End of while-there-are-coupon-restrictions
		
		//Replace coupon keyword in text message with new coupon code
		$text_message = str_replace($coupon_key, $new_coupon_code, $text_message);
		
		//Repeat for html message
		$html_message = str_replace($coupon_key, $new_coupon_code, $html_message);
		
		}
		
		
		
		/* PREPARE LOG DATA */
		
		//Prepare log data
		if ($this_auto_state == 'order') { //If in order mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Post-Order" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Location Restricted: " . $this_auto_location_restrict . "\r\n" . "Location: " . $location . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Order ID: " . $result->fields['orders_id'] . "\r\n" . "Date of Order: " . $order_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";
		} else if ($this_auto_state == 'account-no-order') { //If in account-no-order mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Account No Order" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Account Date: " . $account_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";
		} else if ($this_auto_state == 'account') { //If in account mode
		$logdata="\r\n" . "\r\n" . "Sending email.." . "\r\n" . "Date: " . date("Y-m-d H:i:s") . "\r\n" . "Query: Account" . "\r\n" . "Customer: " . $name . "\r\n" . "Email Address: " . $to . "\r\n" . "Subject: " . $subject . "\r\n" . "Subscribed: " . $subscribed . "\r\n" . "Account Date: " . $account_day . "\r\n". "Days Waited: " . $this_auto_days_after . "\r\n" . "\r\n";		
		}
		
		
		
		
		/* UPDATE ORDER STATUS */
		
		//If in order mode and configured order status ID is different to configured post-order status ID, update database with new status
		if ( ($this_auto_state == 'order') && ($this_auto_order_status_id != $this_auto_post_order_status_id) ) {
		
		//Update table ORDERS
		$sql_update_new_status="UPDATE " . AUTO_TABLE_ORDERS . " SET orders_status = '".$this_auto_post_order_status_id."' WHERE orders_id = '".$result->fields['orders_id']."'";
		$db->Execute($sql_update_new_status);
		
		//Update table ORDERS STATUS HISTORY
		$sql_insert_new_status="insert into " . AUTO_TABLE_ORDERS_STATUS_HISTORY . " values ( '', '".$result->fields['orders_id']."', '".$this_auto_post_order_status_id."', now(), 0, '' )";
		$db->Execute($sql_insert_new_status);
		
		} //End of change-order-status-ID
		
		
		
		
		/* SEND EMAIL */
		
		/* Finally, let's send the email */
		
		//If in test mode, send email to store owner only
		if ($this_auto_mode == 'test') {
				
		zen_mail(STORE_NAME, $admin_email, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
    
		} else if ($this_auto_mode == 'live') { //else if in live mode, send email to customer
		
		zen_mail($name, $to, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
		
		}
		
		//If in live mode and admin receives copy, send email to store owner as well
		if ( (AUTO_ADMIN_COPY == 'true') && ($this_auto_mode == 'live')  ) {
		
		zen_mail(STORE_NAME, $admin_email, $subject, $text_message, STORE_NAME, $sender, $html_message,'autoresponder');
		
		}
    	
    	//Write prepared data to log
		log_cws($logdata);
		
		
		
		} //End continue_execution
	
	//Go to next email
	$result->MoveNext();

	} //End while-there-are-possible-emails

//End the log
log_cws("End: ".date("Y-m-d H:i:s"). "\r\n" . "\r\n" . "\r\n");

/* ******************************************************************** */
/* CODE ABOVE HERE IS INTENTIONALLY DUPLICATED THREE TIMES IN THIS FILE */
/* ******************************************************************** */

} // end of preset #3









} //end of autoresponder+ enabled

} //end of time validation

//Output confirmation message to screen
echo "Autoresponder successfully loaded." . "<br />" . "End of message.";

//EOF