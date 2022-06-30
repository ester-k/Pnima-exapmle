<?php

add_action('init', function () {

  // Register AJAX handlers

  add_action('wp_ajax_check_customer', 'check_customer');

  add_action('wp_ajax_nopriv_check_customer', 'check_customer');

  add_action('wp_ajax_add_tv_focus', 'add_tv_focus');

  add_action('wp_ajax_nopriv_add_tv_focus', 'add_tv_focus');

  add_action('wp_ajax_call_pelecard', 'call_pelecard');

  add_action('wp_ajax_nopriv_call_pelecard', 'call_pelecard');

  add_action('wp_ajax_update_address', 'update_address');

  add_action('wp_ajax_nopriv_update_address', 'update_address');

  add_action('wp_ajax_is_login', 'is_login');

  add_action('wp_ajax_nopriv_is_login', 'is_login');

  add_action('wp_ajax_update_details', 'update_details');

  add_action('wp_ajax_nopriv_update_details', 'update_details');

  create_db_user_confirmatiomkey();

});

//add new custoner in wordpress and crm

function add_new_customer($customer_details)

{

  if (isset($customer_details['MAIL'])) {

    $response = add_customer_xml($customer_details, $customer_details['token']);

    error_log("add_customer_check");

    error_log($response);

    if (get_xml_value($response, 'code') != 0) {

      error_log('msg = ' . get_xml_value($response, 'msg'));

      return 1004;

      wp_redirect(home_url() . '/error-page?code=' . $code);

      exit;

    }

    $res = createUser($customer_details['MAIL'], $customer_details['PSWTVSUB'], $customer_details['CELL']);

    error_log("user creted: " . $res);

    $ret_val = array();

    array_push($ret_val, $res);

    array_push($ret_val, get_xml_value($response, 'identifier'));

    return $ret_val;

  } else {

    error_log("can't read user email");

  }

}




//build XML to add customer, use on add cutomer

function add_customer_xml($xml_CUST_DETAILS, $token)

{

  $card_type = "private_customer";

  $xml_data = "<CARD_TYPE>" . $card_type . "</CARD_TYPE>";

  $xml_data = $xml_data . "<FOCUSES>";

  $xml_data = $xml_data . "<FOCUS>";

  $xml_data = $xml_data . "<KEY>" . "P_TV" . "</KEY>";

  $xml_data = $xml_data . "<STATUS>" . "tv_s_pnima" . "</STATUS>";

  $xml_data = $xml_data . "</FOCUS>";

  $xml_data = $xml_data . "</FOCUSES>";

  $xml_data = $xml_data . "<CUST_DETAILS>";

  foreach ($xml_CUST_DETAILS as $CUST_DETAILS => $CUST_VAL) {

    $xml_data = $xml_data . "<" . $CUST_DETAILS . ">" . $CUST_VAL . "</" . $CUST_DETAILS . ">";

  }

  $xml_data = $xml_data . "</CUST_DETAILS>";

  $xml_data = $xml_data . "<PELECARD_TOKEN>" . $token . "</PELECARD_TOKEN>";

  $xml_data = $xml_data . "<ADD_DOUBLE_CARDS>" . 1 . "</ADD_DOUBLE_CARDS>";



  $response = call_zebra("add_customer", "", "", $xml_data);



  error_log('Add New Customer: got to result');

  if ($response === false) {

    $error = error_get_last();

    error_log('add: POST request failed: ' . $error['message']);

  }

  // setcookie('personalDetails', null, -1, '/');

  return $response;

}

//build XML to update customer, use on update cutomer

function update_customer_xml($xml_CUST_DETAILS)

{

  $card_type = "private_customer";

  $xml_data = "<CARD_TYPE>" . $card_type . "</CARD_TYPE>";

  $xml_data = $xml_data . "<FOCUSES>";

  $xml_data = $xml_data . "<FOCUS>";

  $xml_data = $xml_data . "<KEY>" . "P_TV" . "</KEY>";

  $xml_data = $xml_data . "<STATUS>" . "tv_s_pnima" . "</STATUS>";

  $xml_data = $xml_data . "</FOCUS>";

  $xml_data = $xml_data . "</FOCUSES>";

  $xml_data = $xml_data . "<CUST_DETAILS>";

  foreach ($xml_CUST_DETAILS as $CUST_DETAILS => $CUST_VAL) {

    $xml_data = $xml_data . "<" . $CUST_DETAILS . ">" . $CUST_VAL . "</" . $CUST_DETAILS . ">";

  }

  $xml_data = $xml_data . "</CUST_DETAILS>";



  $response = call_zebra("update_customer", "IDENTIFIER", "USER_CARD_ID", $xml_data);



  error_log('got to result');

  if ($response === false) {

    //the error.

    $error = error_get_last();

    error_log('POST request failed: ' . $error['message']);

    // return $response;

  }

  // return parse_response($response);

  return $response;

}



//call zebra 

function call_zebra($function, $identifire_feild, $identifire_items, $xml_content)

{

  //$url = "http://23096.stest.zebracrm.com/ext_interface.php?b=" . $function;

  $url = "https://23096.zebracrm.com/ext_interface.php?b=" . $function;

  $user_name = "xxx";

  $password = "xxx";



  $xml_data = '<?xml version="1.0" encoding="utf-8"?>';

  $xml_data = $xml_data . "<ROOT>";

  $xml_data = $xml_data . "<PERMISSION>";

  $xml_data = $xml_data . "<USERNAME>" . $user_name . "</USERNAME>";

  $xml_data = $xml_data . "<PASSWORD>" . $password . "</PASSWORD>";

  $xml_data = $xml_data . "</PERMISSION>";

  if ($identifire_feild != '')  $xml_data = $xml_data . "<" . $identifire_feild . ">" . $identifire_items . "</" . $identifire_feild . ">";

  $xml_data = $xml_data . $xml_content;

  $xml_data = $xml_data . "</ROOT>";

  $options = array(

    'http' =>

    array(

      'method'  => 'POST',

      'header'  => 'Content-type: text/xml',

      'content' => $xml_data

    )

  );

  $streamContext  = stream_context_create($options);

  $response = file_get_contents($url, null, $streamContext);

  return $response;

}



//get the return field from zebra

function get_xml_value($response, $field)

{

  error_log('response = ' . $response);

  $start_index = strpos($response, "<" . $field . ">") + strlen($field) + 2;

  $end_index = strpos($response, "</" . $field . ">") - $start_index;

  $value = substr($response, $start_index, $end_index);

  error_log("field: " . $field . ", value: " . $value);

  return $value;

}


  

  //check if user is logged in

  function is_login()

  {

    wp_send_json_success(array('result' => is_user_logged_in()));

  }

  

  function create_db_user_confirmatiomkey()

  {

    global $wpdb;

    $table_name = $wpdb->prefix . "usersconfirmationkey";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (

        id mediumint(9) NOT NULL AUTO_INCREMENT,

    email tinytext NOT NULL,

    password tinytext NOT NULL,

    confirmationkey tinytext NOT NULL,

    PRIMARY KEY  (id)

    ) $charset_collate";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);

    error_log("create table");

  }

  

 

  function call_pelecard()

  {

  

    $pesonal_details = $_POST['personalDetails'];

  

    error_log("pesonalDetails " . $pesonal_details);

    $good_url = get_site_url(null, '/pack?get_toke=true&next_level=3&pd=' . $pesonal_details . '&ui=');

    $currency = '1';

    $error_url = get_site_url(null, '/error-page?code=1002');

    $pelecard_args = array(

      "terminal" => 5773649,

      "user" => 'abmaim',

      "password" => 'RaL3YLdT',

      "GoodURL" => $good_url,

      "ErrorURL" => $error_url,

      "ActionType" => "J2",

      "Currency" => $currency,

      "Total" => 36  * 100,

      "CreateToken" => "True",

      "Language" => "HE",

      "CustomerIdField" => "must",

      "Cvv2Field" => "must",

      // "MaxPayments"=> "12",

      // "MinPayments"=> "1",

      // "FirstPayment"=> "auto",

      "ShopNo" => "001",

  

      // "ParamX"=> $entry['id'],

      // "CssURL"=> plugin_dir_url( __FILE__ )."css/pelecard.css",

      // "LogoURL"=> "https=>//gateway20.pelecard.biz/Content/images/Pelecard.png"

    );

    $url = 'https://gateway20.pelecard.biz/PaymentGW/init';

  

    $curl = curl_init();

  

    curl_setopt_array($curl, array(

      CURLOPT_URL => $url,

      CURLOPT_RETURNTRANSFER => true,

      CURLOPT_ENCODING => "",

      CURLOPT_MAXREDIRS => 10,

      CURLOPT_TIMEOUT => 0,

      CURLOPT_FOLLOWLOCATION => true,

      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

      CURLOPT_CUSTOMREQUEST => "POST",

      CURLOPT_POSTFIELDS => json_encode($pelecard_args),

      CURLOPT_HTTPHEADER => array(

        "Content-Type: application/json"

      ),

    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response);

  

  

    // save ConfirmationKey in data base

    $personal_data = base64_decode($pesonal_details);

    $customer_details = json_decode($personal_data);

  

    global $wpdb;

    $table = $wpdb->prefix . 'usersconfirmationkey';

    error_log("confirmation in function " . $response->ConfirmationKey);

    $data = array('email' => $customer_details->email, 'confirmationkey' => $response->ConfirmationKey, "password" =>  $customer_details->password);

    $format = array('%s', '%s', '%s', '%s');

    $wpdb->insert($table, $data, $format);

    error_log("חזרה מפלאקרד");

  

    wp_send_json_success($response);

  }

  
  function get_details_by_transaction_id($pelecardTransactionId)

  {

    $pelecard_args = array(

      "terminal" => 5773649,

      "user" => 'abmaim',

      "password" => 'RaL3YLdT',

      "TransactionId" => $pelecardTransactionId,

    );

  

    $url = "https://gateway20.pelecard.biz/PaymentGW/GetTransaction";

  

    $curl = curl_init();

  

    curl_setopt_array($curl, array(

      CURLOPT_URL => $url,

      CURLOPT_RETURNTRANSFER => true,

      CURLOPT_ENCODING => "",

      CURLOPT_MAXREDIRS => 10,

      CURLOPT_TIMEOUT => 0,

      CURLOPT_FOLLOWLOCATION => true,

      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

      CURLOPT_CUSTOMREQUEST => "POST",

      CURLOPT_POSTFIELDS => json_encode($pelecard_args),

      CURLOPT_HTTPHEADER => array(

        "Content-Type: application/json"

      ),

    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;

  }

  

  

  //add subscribe to exisite user

  function add_tv_focus()

  {

    $code = 1005;

    $identifier = $_POST['identifier'];

    $phone = $_POST['phone'];

    $email = $_POST['email'];

    $password = $_POST['password'];

    $xml_CUST_DETAILS = array(

      "USER_CARD_ID" => $identifier,

      "TVSubscription" => '1',

      "PSWTVSUB" => $password,

      "CAMPAIGN" => 'פנימה TV',

      "MAIL" => $email,

  

    );

    $response = update_customer_xml($xml_CUST_DETAILS);

    if (get_xml_value($response, 'code') != 0)  $result = false;

    else {

      $filters = array(

        'CELL' => $phone

      );

      $fields = array('MAIL', 'ID');

      // $email = get_email_from_zebra($filters, $fields);

      $code = createUser($email, $password, $phone);

      error_log('add_tv_focus_code '.$code);

  

      if ($code == 0) $result = true;

      else $result = false;

    }

    wp_send_json_success(array('result' => $result, "code" => $code));

  }

  

  //לבדיקת לקוח אם קיים בזברה XML בנית
  //build XML to check if user is exisit

  function check_customer_xml($xml_CUST_DETAILS, $val_CUST_DETAILS)

  {

    $card_type = "private_customer";

  

    $xml_data = "<CARD_TYPE>" . $card_type . "</CARD_TYPE>";

    $xml_data = $xml_data . "<CUST_DETAILS>";

    foreach ($xml_CUST_DETAILS as $index => $CUST_DETAIL) {

      $xml_data = $xml_data . "<" . $CUST_DETAIL . ">" . $val_CUST_DETAILS[$index] . "</" . $CUST_DETAIL . ">";

    }

    $xml_data = $xml_data . "</CUST_DETAILS>";

  

    return call_zebra("check_customer", "MATCH_FIELDS", implode(",", $xml_CUST_DETAILS), $xml_data);

  }

  

  //check if user exisit

  function check_customer()

  {

    $phone = $_POST['phone'];

    $xml_CUST_DETAILS = array('CELL');

    $val_CUST_DETAILS = array($phone);

  

    $response = check_customer_xml($xml_CUST_DETAILS, $val_CUST_DETAILS);

    if ($response === false) {

      //the error.

      $error = error_get_last();

      error_log('POST request failed: ' . $error['message']);

      wp_send_json_error(array('result' => false));

    } else {

      // $arr_response = parse_response($response);

      if (get_xml_value($response, 'code') != 0) $result = false;

      else $result = true;

    }

    wp_send_json_success(array('result' => $result, 'identifier' => get_xml_value($response, 'identifier')));

  }

  

  add_filter('wpcf7_validate_password', 'custom_email_confirmation_validation_filter', 20, 2);

  

  function custom_email_confirmation_validation_filter($result, $tag)

  {

    if ('confirmPassword' == $tag->name) {

      $your_email = isset($_POST['password']) ? trim($_POST['password']) : '';

      $your_email_confirm = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';

  

      if ($your_email != $your_email_confirm) {

        $result->invalidate($tag, "הסיסמאות אינן תואמות");

      }

    }

  

    return $result;

  }

  

  //create user on wordpress

  function createUser($username, $password, $phone)

  {



    if (username_exists($username)) {

      return 1010;

    }

    $user_id = wp_create_user($username, $password, $username);

    if (is_wp_error($user_id)) {

      error_log($user_id->get_error_message());

      return 1009;

    }

    error_log("user_id: " . $user_id);

    add_user_meta($user_id, 'phone_number', $phone);

    $user = get_user_by('ID', $user_id);

    wp_set_current_user($user_id, $user->user_login);

    wp_set_auth_cookie($user_id);

    do_action('wp_login', $user->user_login, $user);

    return 0;

  }

  

//get customer email from zebra to craete uset on wordpress
  function get_email_from_zebra($filters, $feilds)

  {

    $function = "get_multi_cards_details";

    $card_type = "private_customer";

  

    $xml_data = "<FILTERS>";

    foreach ($filters as $filter => $value)

      $xml_data = $xml_data . "<" . $filter . ">" . $value . "</" . $filter . ">";

    $xml_data = $xml_data . "</FILTERS>";

  

    $xml_data = $xml_data . "<FIELDS>";

    foreach ($feilds as $feild)

      $xml_data = $xml_data . "<" . $feild . "></" . $feild . ">";

    $xml_data = $xml_data . "</FIELDS>";

  

    $response = call_zebra($function, "CARD_TYPE_FILTER", $card_type, $xml_data);

    if ($response === false) {

      //the error.

      $error = error_get_last();

      error_log('POST request failed: ' . $error['message']);

      return false;

    }

    // $parse_response = parse_response($response);

    // $start_index = strpos($response, "<MAIL>") + 6;

    // $end_index = strpos($response, "</MAIL>") - $start_index;

    // error_log("get_email_from_zebra: ".substr($response, $start_index, $end_index));

    if (get_xml_value($response, 'code') == 39)  return substr($response, $start_index, $end_index);

    else return '';

  }

  

//login just if user is active on zebra
  add_filter('authenticate', 'filter_login_zebra', 30, 3);

  function filter_login_zebra($user, $email, $password)

  {

    if (is_wp_error($user) || user_can($user, 'administrator'))  return $user;

  

    $message = esc_html__('שגיאה באימות המשתמש', 'text-domain');

    $print_user = json_encode($user);

    // $user_phone = $user->data->user_login;

    $current_user_id = get_current_user_id();

    $user_phone = get_user_meta($user->ID, 'phone_number', true);

    error_log("user: " .  $print_user);

    error_log("email: " .  $email);

    error_log("password: " .  $password);

    error_log("user_phone: " .  $user_phone);

  

  

    $xml_CUST_DETAILS = array('CELL');

    $val_CUST_DETAILS = array($user_phone);

    $response = check_customer_xml($xml_CUST_DETAILS, $val_CUST_DETAILS);

    if ($response === false) {

      //the error.

      $error = error_get_last();

      return new WP_Error('user_not_verified', $message);

    } else {

      // $arr_response = parse_response($response);

      if (get_xml_value($response, 'code') != 0) return new WP_Error('user_not_verified', $message);

      else {

        $xml_CUST_DETAILS = array('CELL', 'TVSubscription');

        $val_CUST_DETAILS = array($user_phone, '1');

        $response = check_customer_xml($xml_CUST_DETAILS, $val_CUST_DETAILS);

        if ($response === false) {

          //the error.

          $error = error_get_last();

          error_log('POST request failed: ' . $error['message']);

          return new WP_Error('user_not_verified', $message);

        } else {

          // $arr_response = parse_response($response);

          if (get_xml_value($response, 'code') != 0) return new WP_Error('user_not_verified', $message);

        }

      }

    }

    return $user;

  }
