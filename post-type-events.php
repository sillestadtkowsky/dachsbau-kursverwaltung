<?php
function timetable_events_settings()
{
	$timetable_events_settings = get_option("timetable_events_settings");
	if(!$timetable_events_settings)
	{
		$timetable_events_settings = array(
			"slug" => "events",
			"label_singular" => "Event",
			"label_plural" => "Events",
		);
		add_option("timetable_events_settings", $timetable_events_settings);
	}
	return $timetable_events_settings;
}

//custom post type - events
function timetable_events_init()
{
	global $wpdb;
	$timetable_events_settings = timetable_events_settings();
	$labels = array(
		'name' => $timetable_events_settings['label_plural'],
		'singular_name' => $timetable_events_settings['label_singular'],
		'add_new' => _x('Add New', $timetable_events_settings["slug"], 'timetable'),
		'add_new_item' => sprintf(esc_html__('Add New %s' , 'timetable') , $timetable_events_settings['label_singular']),
		'edit_item' => sprintf(esc_html__('Edit %s', 'timetable'), $timetable_events_settings['label_singular']),
		'new_item' => sprintf(esc_html__('New %s', 'timetable'), $timetable_events_settings['label_singular']),
		'all_items' => sprintf(esc_html__('All %s', 'timetable'), $timetable_events_settings['label_plural']),
		'view_item' => sprintf(esc_html__('View %s', 'timetable'), $timetable_events_settings['label_singular']),
		'search_items' => sprintf(esc_html__('Search %s', 'timetable'), $timetable_events_settings['label_singular']),
		'not_found' =>  sprintf(esc_html__('No %s found', 'timetable'), strtolower($timetable_events_settings['label_plural'])),
		'not_found_in_trash' => sprintf(esc_html__('No %s found in Trash', 'timetable'), strtolower($timetable_events_settings['label_plural'])), 
		'parent_item_colon' => '',
		'menu_name' => $timetable_events_settings['label_plural']
	);
	$args = array(  
		"labels" => $labels, 
		"public" => true,
		"show_ui" => true,  
		"capability_type" => "post",  
		"menu_position" => 20,
		"hierarchical" => false,  
		"rewrite" => true,
		"show_in_rest" => true,
		"supports" => array("title", "editor", "excerpt", "thumbnail", "page-attributes")  
	);
	register_post_type($timetable_events_settings["slug"], $args);
	
	register_taxonomy("events_category", array($timetable_events_settings["slug"]), array("label" => esc_html__("Categories", 'timetable'), "singular_label" => esc_html__("Category", 'timetable'), "rewrite" => true, "hierarchical" => true, "show_in_rest" => true));
	
	if(array_key_exists("timetable_warning", $_GET) && $_GET["timetable_warning"]=="available_places")
	{
		add_action("admin_notices", "timetable_warning_available_places");
	}
	
	
	if(!get_option("timetable_event_hours_table_installed"))
	{
		//create custom db table
		$query = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "event_hours` (
			`event_hours_id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`event_id` BIGINT( 20 ) NOT NULL ,
			`weekday_id` BIGINT( 20 ) NOT NULL ,
			`start` TIME NOT NULL ,
			`end` TIME NOT NULL,
			`tooltip` text NOT NULL,
			`before_hour_text` text NOT NULL,
			`after_hour_text` text NOT NULL,
			`category` varchar(255) NOT NULL,
			`available_places` int(11) NOT NULL DEFAULT 0,
			KEY `event_id` (`event_id`),
			KEY `weekday_id` (`weekday_id`)
		) ENGINE = MYISAM DEFAULT CHARSET=utf8;";
		$wpdb->query($query);
		update_option("timetable_event_hours_table_installed", 1);
		global $wp_rewrite;
		$wp_rewrite->flush_rules(); 
	}
	
	if(!get_option("timetable_event_hours_table_column_available_places"))
	{
		$query = "SHOW COLUMNS FROM " . $wpdb->prefix . "event_hours LIKE 'available_places'";
		$result=$wpdb->get_results($query);
		if(!$result)
		{
			$query = "ALTER TABLE " . $wpdb->prefix . "event_hours ADD available_places int(11) NOT NULL DEFAULT 0";
			$wpdb->query($query);
		}
		update_option("timetable_event_hours_table_column_available_places", 1);
	}
	
	if(!get_option("timetable_event_hours_table_column_slots_per_user"))
	{
		$query = "SHOW COLUMNS FROM " . $wpdb->prefix . "event_hours LIKE 'slots_per_user'";
		$result=$wpdb->get_results($query);
		if(!$result)
		{
			$query = "ALTER TABLE " . $wpdb->prefix . "event_hours ADD slots_per_user int(11) NOT NULL DEFAULT 1";
			$wpdb->query($query);
		}
		update_option("timetable_event_hours_table_column_slots_per_user", 1);
	}
	
	if(!get_option("timetable_event_hours_booking_table_installed"))
	{
		//create custom db table
		$query = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "event_hours_booking` (
			`booking_id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`event_hours_id` BIGINT( 20 ) UNSIGNED NOT NULL,
			`user_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT 0,
			`booking_datetime` DATETIME NOT NULL,
			`validation_code` VARCHAR(32) NOT NULL,
			`guest_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT 0
		) ENGINE = MYISAM DEFAULT CHARSET=utf8;";
		
		$wpdb->query($query);
		update_option("timetable_event_hours_booking_table_installed", 1);
	}
	
	if(!get_option('timetable_event_hours_booking_table_modify_1'))
	{
		$query = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'event_hours_booking LIKE "guest_id"';
		$result=$wpdb->get_results($query);
		if(!$result)
		{
			$query = 'ALTER TABLE ' . $wpdb->prefix . 'event_hours_booking ADD guest_id BIGINT(20) UNSIGNED';
			$wpdb->query($query);
		}
		
		$query = 'SHOW INDEX FROM ' . $wpdb->prefix . 'event_hours_booking WHERE Key_name="unique_index"';
		$result = $wpdb->get_results($query);
		if($result)
		{
			$query = 'ALTER TABLE ' . $wpdb->prefix . 'event_hours_booking DROP INDEX unique_index';
			$wpdb->query($query);
		}
		update_option('timetable_event_hours_booking_table_modify_1', 1);
	}
	
	if(!get_option("timetable_timetable_guests_table_installed"))
	{
		//create custom db table
		$query = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "timetable_guests` (
			`guest_id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`name` VARCHAR(250),
			`email` VARCHAR(100),
			`phone` VARCHAR(50),
			`message` TEXT
		) ENGINE = MYISAM DEFAULT CHARSET=utf8;";
		$wpdb->query($query);
		update_option("timetable_timetable_guests_table_installed", 1);
	}

	if(!get_option("timetable_event_booking_save_table_installed"))
	{
		//create custom db table
		$query = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "event_booking_saves` (
			`booking_id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`email` VARCHAR(120),
			`mitgliedsnummer` VARCHAR(10),
			`event_hours_id` BIGINT( 20 ) UNSIGNED NOT NULL,
			`name` VARCHAR(120),
			`booking_datetime` DATETIME NOT NULL,
			`booking_delete_datetime` DATETIME NOT NULL
		) ENGINE = MYISAM DEFAULT CHARSET=utf8;";
		
		$wpdb->query($query);
		update_option("timetable_event_booking_save_table_installed", 1);
	}
	
}  
add_action("init", "timetable_events_init"); 

function process_waitlist_submission() {
    if (isset($_POST['join_waitlist']) && !empty($_POST['waitlist_email']) && !empty($_POST['event_hours_id']) && !empty($_POST['member_id']) && isset($_POST['data_protection'])) {
        global $wpdb;

        // Daten vorbereiten
        $event_id = intval($_POST['event_hours_id']);
        $waitlist_email = sanitize_email($_POST['waitlist_email']);
        $member_id = sanitize_text_field($_POST['member_id']);
        $event_title = isset($_POST['event_title']) ? sanitize_text_field($_POST['event_title']) : 'Kursname unbekannt';
        $event_date = isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : 'Datum unbekannt';
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : 'Startzeit unbekannt';
        $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : 'Endzeit unbekannt';

        // Mitgliedsnummer prüfen
        $member = MEMBERS_CHECK::checkMeberByNumber($member_id);
        if ($member == 0) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('error-message').style.display = 'block';
                        document.getElementById('error-message').innerHTML = 'Gib bitte eine gültige Mitgliedsnummer ein. (NUR <u>EINE</u> MITGLIEDNUMMER ERLAUBT)';
                        document.getElementById('waitlist-overlay-$event_id').style.display = 'block';
                    });
                  </script>";
            return;
        }

        // Verhindere doppelte Einträge
        $existing_entry = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}waitlist WHERE event_id = %d AND member_id = %s",
            $event_id,
            $member_id
        ));

        if ($existing_entry > 0) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('error-message').style.display = 'block';
                        document.getElementById('error-message').innerHTML = 'Sie sind bereits auf der Warteliste für diesen Kurs.';
                        document.getElementById('waitlist-overlay-$event_id').style.display = 'block';
                    });
                  </script>";
            return;
        }

        // Eintrag in die Warteliste hinzufügen
        $wpdb->insert(
            "{$wpdb->prefix}waitlist",
            array(
                'event_id' => $event_id,
                'user_email' => $waitlist_email,
                'member_id' => $member_id,
                'date_registered' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );

        if (!$wpdb->last_error) {
            // Die ID des neuen Wartelisten-Eintrags auslesen
            $waitlist_id = $wpdb->insert_id;

            // Übergabe der Daten, einschließlich der neuen `waitlist_id`
            $email_args = array(
                'member_email' => $waitlist_email,
                'event_title' => $event_title,
                'event_date' => $event_date,
                'start' => $start,
                'end' => $end,
                'event_id' => $event_id,
                'waitlist_id' => $waitlist_id // Übergabe der ID an die E-Mail-Funktion
            );
            sendWaitlistConfirmationMail($email_args);

            $message = 'Sie wurden erfolgreich auf die Warteliste gesetzt.';
        } else {
            $message = 'Datenbankfehler: ' . $wpdb->last_error;
        }

        echo "<div id='waitlist-message-overlay' class='waitlist-overlay' style='display: block;'>
                <div class='waitlist-overlay-content'>
                    <span class='close-overlay' onclick='closeWaitlistMessageOverlay()'>&times;</span>
                    <p>$message</p>
                </div>
              </div>";

        echo "<script>
                function closeWaitlistMessageOverlay() {
                    document.getElementById('waitlist-message-overlay').style.display = 'none';
                }
              </script>";
    }
}
add_action('wp', 'process_waitlist_submission');


function sendWaitlistConfirmationMail($args) {
    $member_email = $args['member_email'];
    $event_title = $args['event_title'];
    $event_date = $args['event_date'];
    $start_time = $args['start'];
    $end_time = $args['end'];
    $event_id = $args['event_id'];
    $waitlist_id = $args['waitlist_id'];

    // Token für den Löschlink erstellen
    $token = urlencode(base64_encode($member_email . '|' . $event_id . '|' . $waitlist_id));
    $unsubscribe_link = site_url() . "/?remove_from_waitlist=1&token=$token";

    $subject = "Bestätigung deiner Wartelistenanmeldung für " . $event_title;
    $message = '<div>';
    $message .= '<div><b>Lieber Teilnehmer,</b></div>';
    $message .= '<div>du wurdest erfolgreich auf die Warteliste gesetzt.</div>';
    $message .= '<h3>Details zur Wartelisten-Anmeldung:</h3>';
    $message .= '<div><b>Kurs:</b> ' . esc_html($event_title) . '</div>';
    $message .= '<div><b>Datum:</b> ' . esc_html($event_date) . '</div>';
    $message .= '<div><b>Uhrzeit:</b> ' . esc_html($start_time) . ' - ' . esc_html($end_time) . '</div>';
    $message .= '<p>Falls du dich von der Warteliste entfernen möchtest, klicke bitte <a href="' . esc_url($unsubscribe_link) . '">hier</a>.</p>';
	$message .= '<p>' . nl2br(get_option('so_coach_mail_footer')) . '<br><img id="bild_vorschau" src="' . esc_url(get_option('so_coach_mail_footer_logo_url')) . '" style="max-width: 100px; max-height: 100px;"/></p>';
    $message .= '</div>';

    $to = $member_email;
    $headers = 'From: Dachsbau <' . get_option('so_coach_mail_to') . '>' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

    wp_mail($to, $subject, $message, $headers);
}

function remove_from_waitlist() {
    if (isset($_GET['remove_from_waitlist']) && $_GET['remove_from_waitlist'] == 1 && isset($_GET['token'])) {
        global $wpdb;
        $token = sanitize_text_field($_GET['token']);
        
        // Entschlüsselung des Tokens
        list($user_email, $event_id, $waitlist_id) = explode('|', base64_decode(urldecode($token)));

        // Überprüfen, ob der Eintrag existiert
        $table_name = $wpdb->prefix . 'waitlist';
        $entry_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %d AND event_id = %d AND user_email = %s",
            $waitlist_id,
            $event_id,
            $user_email
        ));

        if ($entry_exists) {
            // Eintrag aus der Warteliste löschen
            $wpdb->delete(
                $table_name,
                array(
                    'id' => $waitlist_id,
                    'event_id' => $event_id,
                    'user_email' => $user_email
                ),
                array('%d', '%d', '%s')
            );

            // Bestätigungs-E-Mail über die Stornierung
            $subject = "Deine Wartelistenanmeldung für den Kurs wurde storniert";
            $message = "<p>Hallo lieber Dachs,</p>";
            $message .= "<p>du wurdest erfolgreich von der Warteliste entfernt. Falls du dies nicht veranlasst hast, kontaktiere uns bitte.</p>";
            $message .= '<p>' . nl2br(get_option('so_coach_mail_footer')) . '<br><img id="bild_vorschau" src="' . esc_url(get_option('so_coach_mail_footer_logo_url')) . '" style="max-width: 100px; max-height: 100px;"/></p>';

            $headers = 'From: Dachsbau <' . get_option('so_coach_mail_to') . '>' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            wp_mail($user_email, $subject, $message, $headers);

            // Benutzer zur Startseite mit Parameter weiterleiten
            wp_redirect(home_url('/?waitlist_removed=1'));
            exit;
        } else {
            wp_redirect(home_url('/?waitlist_removed=0'));
            exit;
        }
    }
}
add_action('init', 'remove_from_waitlist');

function display_waitlist_feedback() {
    if (isset($_GET['waitlist_removed'])) {
        $success = $_GET['waitlist_removed'] == 1;
        $message = $success 
            ? "Du wurdest erfolgreich von der Warteliste entfernt." 
            : "Der Wartelisten-Eintrag existiert nicht oder ist ungültig.";
        
        $backgroundColor = $success ? '#2d7a1f' : '#c0392b';  // Dunklerer Grün für Erfolg, Rot für Fehler
        $textColor = '#ffffff';  // Weißer Text für maximalen Kontrast

        echo "<div id='waitlist-overlay' class='waitlist-overlay'>
                <div id='waitlist-feedback' class='waitlist-feedback'>
                    <span class='close-feedback' onclick='closeFeedback()'>&times;</span>
                    <p style='font-size: 20px;'>$message</p>
                </div>
              </div>
              <style>
                .waitlist-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);  /* Halbtransparentes Schwarz */
                    z-index: 9998;
                }
                .waitlist-feedback {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background-color: $backgroundColor;
                    color: $textColor;
                    padding: 20px;
                    border-radius: 5px;
                    text-align: center;
                    font-size: 25px;
                    z-index: 9999;
                    width: 80%;
                    max-width: 400px;
                    box-sizing: border-box;
                    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
                }
                .waitlist-feedback .close-feedback {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    font-size: 30px;
                    cursor: pointer;
                    color: $textColor;
                }
              </style>
              <script>
                function closeFeedback() {
                    document.getElementById('waitlist-overlay').style.display = 'none';
                }
              </script>";
    }
}
add_action('wp_footer', 'display_waitlist_feedback');


//Adds a box to the right column and to the main column on the Events edit screens
function timetable_add_events_custom_box() 
{
	$timetable_events_settings = timetable_events_settings();
    add_meta_box(
        "event_hours",
        esc_html__("Event hours", 'timetable'),
        "timetable_inner_events_custom_box_side",
        $timetable_events_settings["slug"],
		"normal"
    );
	add_meta_box( 
        "event_config",
        esc_html__("Options", 'timetable'),
        "timetable_inner_events_custom_box_main",
        $timetable_events_settings["slug"],
		"normal",
		"high"
    );
}
add_action("add_meta_boxes", "timetable_add_events_custom_box");
//backwards compatible (before WP 3.0)
//add_action("admin_init", "timetable_add_custom_box", 1);

//get event hour details
function timetable_get_event_hour_details()
{
	global $wpdb;
	$query = $wpdb->prepare("SELECT * FROM `" . $wpdb->prefix . "event_hours` AS t1 LEFT JOIN {$wpdb->posts} AS t2 ON t1.weekday_id=t2.ID WHERE t1.event_id='%d' AND t1.event_hours_id='%d'", $_POST["post_id"], $_POST["id"]);
	$event_hour = $wpdb->get_row($query);
	$event_hour->start = date("H:i", strtotime($event_hour->start));
	$event_hour->end = date("H:i", strtotime($event_hour->end));
	echo "event_hour_start" . json_encode($event_hour) . "event_hour_end";
	exit();
}
add_action('wp_ajax_get_event_hour_details', 'timetable_get_event_hour_details');

function timetable_delete_event_bookings()
{
	$result = array(
		'error' => 0,
		'msg' => '',
	);
	
	global $wpdb;
	$event_id = (isset($_POST["event_id"]) ? $_POST["event_id"] : '');
	$booking_weekday_id = (isset($_POST["booking_weekday_id"]) ? $_POST["booking_weekday_id"] : '');
	
	$query = "";
	$query_args = array();
	
	$query .= 
	"SELECT booking_id FROM `" . $wpdb->prefix . "event_hours_booking` 
	WHERE event_hours_id IN (
		SELECT event_hours_id 
		FROM `" . $wpdb->prefix . "event_hours`
		WHERE event_id=%d";
	$query_args[] = $event_id;
	if((int)$booking_weekday_id)
	{
		$query .= " AND weekday_id=%d";
		$query_args[] = $booking_weekday_id;
	}
	$query .= ")";
	
	$query = $wpdb->prepare($query, $query_args);
	$bookings_ids = $wpdb->get_col($query);
	
	for($i=0, $max_i=count($bookings_ids); $i<$max_i; $i++)
	{
		TT_DB::deleteBooking($bookings_ids[$i]);
	}

	timetable_ajax_response($result);
}
add_action('wp_ajax_delete_event_bookings', 'timetable_delete_event_bookings');

// Prints the box content
function timetable_inner_events_custom_box_side($post) 
{
	global $wpdb;
	//Use nonce for verification
	wp_nonce_field(plugin_basename( __FILE__ ), "timetable_events_noncename");

	//The actual fields for data entry
	$query = "SELECT * FROM `" . $wpdb->prefix . "event_hours` AS t1 LEFT JOIN {$wpdb->posts} AS t2 ON t1.weekday_id=t2.ID WHERE t1.event_id='" . $post->ID . "' ORDER BY t2.menu_order, t1.start, t1.end";
	$event_hours = $wpdb->get_results($query);
	$event_hours_count = count($event_hours);
	
	//get weekdays
	$query = "SELECT ID, post_title FROM {$wpdb->posts}
			WHERE 
			post_type='timetable_weekdays'
			AND post_status='publish'
			ORDER BY menu_order";
	$weekdays = $wpdb->get_results($query);
	
	//get booking details
	$args = array(
		'event_id' => $post->ID,
	);
	$bookings = TT_DB::getBookings($args);
	$bookings_array = array();
	for($i=0, $max_i=count($bookings); $i<$max_i; $i++)
	{
		$bookings_array[$bookings[$i]['event_hours_id']][] = $bookings[$i];
	}

	echo '
	<ul id="event_hours_list"' . (!$event_hours_count ? ' style="display: none;"' : '') . '>';
		for($i=0; $i<$event_hours_count; $i++)
		{
			$booking_count=(isset($bookings_array[$event_hours[$i]->event_hours_id]) ? count($bookings_array[$event_hours[$i]->event_hours_id]) : 0);
			//get day by id
			$current_day = get_post($event_hours[$i]->weekday_id);
			echo '<li id="event_hours_' . esc_attr($event_hours[$i]->event_hours_id) . '">' . $current_day->post_title . ' ' . date("H:i", strtotime($event_hours[$i]->start)) . '-' . date("H:i", strtotime($event_hours[$i]->end)) . '<img class="operation_button delete_button delete_event_hour" src="' . plugins_url('/admin/images/delete.png', __FILE__) . '" alt="del" /><img class="operation_button edit_button" src="' . plugins_url('/admin/images/edit.png', __FILE__) . '" alt="edit" /><img class="operation_button edit_hour_event_loader" src="' . plugins_url('/admin/images/ajax-loader.gif', __FILE__) . '" alt="loader" />';
			if($event_hours[$i]->tooltip!="" || $event_hours[$i]->before_hour_text!="" || $event_hours[$i]->after_hour_text!="" || $event_hours[$i]->category!="" || $event_hours[$i]->available_places!="")
			{
				echo '<div>';
				if($event_hours[$i]->tooltip!="")
					echo '<br /><strong>' . esc_html__('Tooltip', 'timetable') . ':</strong> ' . $event_hours[$i]->tooltip;
				if($event_hours[$i]->before_hour_text!="")
					echo '<br /><strong>' . esc_html__('Description 1', 'timetable') . ':</strong> ' . $event_hours[$i]->before_hour_text;
				if($event_hours[$i]->after_hour_text!="")
					echo '<br /><strong>' . esc_html__('Description 2', 'timetable') . ':</strong> ' . $event_hours[$i]->after_hour_text;
				if($event_hours[$i]->available_places!=0)
					echo '<br /><strong>' . esc_html__('Available slots', 'timetable') . ':</strong> ' . ($booking_count>0 ? $event_hours[$i]->available_places-$booking_count . '/' : '') . $event_hours[$i]->available_places;
				if($event_hours[$i]->available_places!=0 && $event_hours[$i]->slots_per_user!=0)
					echo '<br /><strong>' . esc_html__('Slots per user', 'timetable') . ':</strong> ' . $event_hours[$i]->slots_per_user;
				if($booking_count)
				{
					echo '<br><a href="#" class="show_hide_bookings">' . esc_html__('Show/Hide booked users', 'timetable') . '</a>
						<ul class="booking_list">';
					foreach($bookings_array[$event_hours[$i]->event_hours_id] as $booking)
					{
						echo '<li id="booking_id_' . esc_attr($booking['booking_id']) . '">';
						if($booking['user_id'])
						{
							echo sprintf(wp_kses(__('<a href="%s">%s</a> on %s', 'timetable'), array("a" => array("href" => array()))), get_edit_user_link($booking['user_id']), $booking['user_name'], $booking['booking_datetime']);
						}
						elseif($booking['guest_id'])
						{
							echo sprintf(esc_html__('Guest: %s on %s', 'timetable'), $booking['guest_name'], $booking['booking_datetime']);
						}
						echo	'<img class="operation_button delete_button delete_booking" src="' . plugins_url('/admin/images/delete.png', __FILE__) . '" alt="del" />
							</li>';
					}
					echo '</ul>';
				}
				if($event_hours[$i]->category!="")
					echo '<br /><strong>' . esc_html__('Category', 'timetable') . ':</strong> ' . $event_hours[$i]->category;
				echo '</div>';
			}
			echo '</li>';
		}
	echo '
	</ul>
	<table id="event_hours_table">
		<tr>
			<td>
				<label for="weekday_id">' . esc_html__('Timetable column', 'timetable') . ':</label>
			</td>
			<td>
				<select name="weekday_id" id="weekday_id">';
				foreach($weekdays as $weekday)
					echo '<option value="' . esc_attr($weekday->ID) . '">' . esc_html($weekday->post_title) . '</option>';
	echo '		</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="start_hour">' . esc_html__('Start hour', 'timetable') . ':</label>
			</td>
			<td>
				<input size="5" maxlength="5" type="text" id="start_hour" name="start_hour" value="" />
				<span class="description">hh:mm</span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="end_hour">' . esc_html__('End hour', 'timetable') . ':</label>
			</td>
			<td>
				<input size="5" maxlength="5" type="text" id="end_hour" name="end_hour" value="" />
				<span class="description">hh:mm</span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="before_hour_text">' . esc_html__('Description 1', 'timetable') . ':</label>
			</td>
			<td>
				<textarea id="before_hour_text" name="before_hour_text"></textarea>
			</td>
		</tr>
		<tr>
			<td>
				<label for="after_hour_text">' . esc_html__('Description 2', 'timetable') . ':</label>
			</td>
			<td>
				<textarea id="after_hour_text" name="after_hour_text"></textarea>
			</td>
		</tr>
		<tr>
			<td>
				<label for="tooltip">' . esc_html__('Tooltip', 'timetable') . ':</label>
			</td>
			<td>
				<textarea id="tooltip" name="tooltip"></textarea>
			</td>
		</tr>
		<tr>
			<td>
				<label for="event_hour_category">' . esc_html__('Category', 'timetable') . ':</label>
			</td>
			<td>
				<input type="text" id="event_hour_category" name="event_hour_category" value="" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="available_places">' . esc_html__('Available slots', 'timetable') . ':</label>
			</td>
			<td>
				<input type="text" id="available_places" name="available_places" value="" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="slots_per_user">' . esc_html__('Slots per user', 'timetable') . ':</label>
			</td>
			<td>
				<input type="text" id="slots_per_user" name="slots_per_user" value="" />
			</td>
		</tr>
		<tr>
			<td colspan="2" style="text-align: right;">
				<input id="add_event_hours" type="button" class="button" value="' . esc_attr__("Add", 'timetable') . '" />
				<input type="hidden" id="event_hours_id" name="event_hours_id" value="0"/>
			</td>
		</tr>
	</table>
	<table id="event_bookings_table">
		<tr>
			<td colspan="2">
				<h3>' . esc_html__('Delete event bookings', 'timetable') . '</h3>
			</td>
		</tr>
		<tr>
			<td>
				<label for="booking_weekday_id">' . esc_html__('Timetable column', 'timetable') . ':</label>
			</td>
			<td>
				<select name="booking_weekday_id" id="booking_weekday_id">
					<option value="all">' . esc_html__('All', 'timetable') . '</option>';
	
				foreach($weekdays as $weekday)
					echo '<option value="' . esc_attr($weekday->ID) . '">' . esc_html($weekday->post_title) . '</option>';
	echo '		</select>
			</td>
		</tr>
		<tr>	
			<td colspan="2" style="text-align: right;">
				<input type="hidden" id="event_id" name="event_id" value="' . esc_attr($post->ID) . '"/>
				<input id="delete_event_bookings" type="button" class="button" value="' . esc_attr__("Delete", 'timetable') . '" />
			</td>
		</tr>
	</table>
	';
	//Reset Query
	wp_reset_query();
}

function timetable_inner_events_custom_box_main($post)
{
	//Use nonce for verification
	wp_nonce_field(plugin_basename( __FILE__ ), "timetable_events_noncename");
	
	//The actual fields for data entry
	$timetable_disable_url = get_post_meta($post->ID, "timetable_disable_url", true);
	echo '
	<table>
		<tr>
			<td>
				<label for="color">' . esc_html__('Subtitle', 'timetable') . ':</label>
			</td>
			<td>
				<input class="regular-text" type="text" id="subtitle" name="subtitle" value="' . esc_attr(get_post_meta($post->ID, "timetable_subtitle", true)) . '" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="color">' . esc_html__('Timetable box background color', 'timetable') . ':</label>
			</td>
			<td>
				<span class="color_preview" style="background-color: #' . (get_post_meta($post->ID, "timetable_color", true)!="" ? esc_attr(get_post_meta($post->ID, "timetable_color", true)) : 'transparent') . '"></span>
				<input class="regular-text color" type="text" id="color" name="color" value="' . esc_attr(get_post_meta($post->ID, "timetable_color", true)) . '" data-default-color="transparent" />
				<span class="description">' . esc_html__('Required when \'Timetable box hover color\' isn\'t empty', 'timetable') . '</span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="color">' . esc_html__('Timetable box hover background color', 'timetable') . ':</label>
			</td>
			<td>
				<span class="color_preview" style="background-color: #' . (get_post_meta($post->ID, "timetable_hover_color", true)!="" ? esc_attr(get_post_meta($post->ID, "timetable_hover_color", true)) : 'transparent') . '"></span>
				<input class="regular-text color" type="text" id="hover_color" name="hover_color" value="' . esc_attr(get_post_meta($post->ID, "timetable_hover_color", true)) . '" data-default-color="transparent" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="text_color">' . esc_html__('Timetable box text color', 'timetable') . ':</label>
			</td>
			<td>
				<span class="color_preview" style="background-color: #' . (get_post_meta($post->ID, "timetable_text_color", true)!="" ? esc_attr(get_post_meta($post->ID, "timetable_text_color", true)) : 'transparent') . '"></span>
				<input class="regular-text color" type="text" id="text_color" name="text_color" value="' . esc_attr(get_post_meta($post->ID, "timetable_text_color", true)) . '" data-default-color="transparent" />
				<span class="description">' . esc_html__('Required when \'Timetable box hover text color\' isn\'t empty', 'timetable') . '</span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="text_color">' . esc_html__('Timetable box hover text color', 'timetable') . ':</label>
			</td>
			<td>
				<span class="color_preview" style="background-color: #' . (get_post_meta($post->ID, "timetable_hover_text_color", true)!="" ? esc_attr(get_post_meta($post->ID, "timetable_hover_text_color", true)) : 'transparent') . '"></span>
				<input class="regular-text color" type="text" id="hover_text_color" name="hover_text_color" value="' . esc_attr(get_post_meta($post->ID, "timetable_hover_text_color", true)) . '" data-default-color="transparent" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="text_color">' . esc_html__('Timetable box hours text color', 'timetable') . ':</label>
			</td>
			<td>
				<span class="color_preview" style="background-color: #' . (get_post_meta($post->ID, "timetable_hours_text_color", true)!="" ? esc_attr(get_post_meta($post->ID, "timetable_hours_text_color", true)) : 'transparent') . '"></span>
				<input class="regular-text color" type="text" id="hours_text_color" name="hours_text_color" value="' . esc_attr(get_post_meta($post->ID, "timetable_hours_text_color", true)) . '" data-default-color="transparent" />
				<span class="description">' . esc_html__('Required when \'Timetable box hover hours text color\' isn\'t empty', 'timetable') . '</span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="text_color">' . esc_html__('Timetable box hover hours text color', 'timetable') . ':</label>
			</td>
			<td>
				<span class="color_preview" style="background-color: #' . (get_post_meta($post->ID, "timetable_hours_hover_text_color", true)!="" ? esc_attr(get_post_meta($post->ID, "timetable_hours_hover_text_color", true)) : 'transparent') . '"></span>
				<input class="regular-text color" type="text" id="hours_hover_text_color" name="hours_hover_text_color" value="' . esc_attr(get_post_meta($post->ID, "timetable_hours_hover_text_color", true)) . '" data-default-color="transparent" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="color">' . esc_html__('Timetable custom URL', 'timetable') . ':</label>
			</td>
			<td>
				<input class="regular-text" type="text" id="timetable_custom_url" name="timetable_custom_url" value="' . esc_attr(get_post_meta($post->ID, "timetable_custom_url", true)) . '" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="color">' . esc_html__('Disable timetable event URL', 'timetable') . ':</label>
			</td>
			<td>
				<select name="timetable_disable_url">
					<option value="0"' . (!(int)$timetable_disable_url ? ' selected="selected"' : '') . '>' . esc_html__("No", 'timetable') . '</option>
					<option value="1"' . ((int)$timetable_disable_url ? ' selected="selected"' : '') . '>' . esc_html__("Yes", 'timetable') . '</option>
				</select>
			</td>
		</tr>
	</table>';
}

//When the post is saved, saves our custom data
function timetable_save_events_postdata($post_id) 
{
	global $wpdb;
	$query = "";
	//verify if this is an auto save routine. 
	//if it is our form has not been submitted, so we dont want to do anything
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
		return;

	//verify this came from the our screen and with proper authorization,
	//because save_post can be triggered at other times
	if (isset($_POST['timetable_events_noncename']) && !wp_verify_nonce($_POST['timetable_events_noncename'], plugin_basename( __FILE__ )) || !isset($_POST['timetable_events_noncename']))
		return;
	
	//Check permissions
	if(!current_user_can('edit_post', $post_id))
		return;
	
	//OK, we're authenticated: we need to find and save the data
	
	if(isset($_POST["weekday_ids"]))
	{
		$hours_count = count($_POST["weekday_ids"]);
		for($i=0; $i<$hours_count; $i++)
		{
			$slots_per_user = $_POST["slots_per_user_array"][$i];
			if($slots_per_user<1)
				$slots_per_user = 1;
			if($slots_per_user>$_POST["available_places_array"][$i])
				$slots_per_user = $_POST["available_places_array"][$i];
			
			$event_hour_id = (isset($_POST["event_hours_ids"][$i]) ? $_POST["event_hours_ids"][$i] : 0);
			$weekday_id = $_POST["weekday_ids"][$i];
			$start_hour = $_POST["start_hours"][$i];
			$end_hour = $_POST["end_hours"][$i];
			$tooltip = stripslashes($_POST["tooltips"][$i]);
			$before_hour_text = stripslashes($_POST["before_hour_texts"][$i]);
			$after_hour_text = stripslashes($_POST["after_hour_texts"][$i]);
			$event_hours_category = $_POST["event_hours_category"][$i];
			$available_places = $_POST["available_places_array"][$i];
			
			if(!($event_hour_id>0))
			{
				$query = $wpdb->prepare(
					"INSERT INTO `" . $wpdb->prefix . "event_hours`(event_id, weekday_id, start, end, tooltip, before_hour_text, after_hour_text, category, available_places, slots_per_user) VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)", 
					$post_id, $weekday_id, $start_hour, $end_hour, $tooltip, $before_hour_text, $after_hour_text, $event_hours_category, $available_places, $slots_per_user);
			}
			else
			{
				//update only if the available_places is equal or grater than the count of existing bookings
				$booking_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(booking_id) FROM `" . $wpdb->prefix . "event_hours_booking` WHERE event_hours_id=%d", $event_hour_id));
				if($available_places>=$booking_count)
				{
					$query = $wpdb->prepare(
						"UPDATE `" . $wpdb->prefix . "event_hours` SET weekday_id=%s, start=%s, end=%s, tooltip=%s, before_hour_text=%s, after_hour_text=%s, category=%s, available_places=%s, slots_per_user=%s WHERE event_hours_id=%s", 
						$weekday_id,  $start_hour, $end_hour, $tooltip, $before_hour_text, $after_hour_text, $event_hours_category, $available_places, $slots_per_user, $event_hour_id);
				}
				else
				{
					add_filter("redirect_post_location", "timetable_set_warning_available_places");
				}
			}
			
			if(strlen($query))
				$wpdb->query($query);
		}
	}
	//removing data if needed
	if(isset($_POST["delete_event_hours_ids"]))
	{
		$delete_event_hours_ids_count = count($_POST["delete_event_hours_ids"]);
		if($delete_event_hours_ids_count)
		{
			$wpdb->query("DELETE FROM `" . $wpdb->prefix . "event_hours` WHERE event_hours_id IN(" . implode(",", esc_sql($_POST["delete_event_hours_ids"])) . ")");
			$wpdb->query("DELETE FROM `" . $wpdb->prefix . "event_hours_booking` WHERE event_hours_id IN(" . implode(",", esc_sql($_POST["delete_event_hours_ids"])) . ")");
		}
	}
	if(isset($_POST["delete_booking_ids"]))
	{
		for($i=0, $max_i=count($_POST["delete_booking_ids"]); $i<$max_i; $i++)
			TT_DB::deleteBooking($_POST["delete_booking_ids"][$i]);
	}
	
	//post meta
	update_post_meta($post_id, "timetable_subtitle", $_POST["subtitle"]);
	update_post_meta($post_id, "timetable_color", $_POST["color"]);
	update_post_meta($post_id, "timetable_hover_color", $_POST["hover_color"]);
	update_post_meta($post_id, "timetable_text_color", $_POST["text_color"]);
	update_post_meta($post_id, "timetable_hover_text_color", $_POST["hover_text_color"]);
	update_post_meta($post_id, "timetable_hours_text_color", $_POST["hours_text_color"]);
	update_post_meta($post_id, "timetable_hours_hover_text_color", $_POST["hours_hover_text_color"]);	
	update_post_meta($post_id, "timetable_custom_url", $_POST["timetable_custom_url"]);
	update_post_meta($post_id, "timetable_disable_url", $_POST["timetable_disable_url"]);
}
add_action("save_post", "timetable_save_events_postdata");

function timetable_delete_events($post_id)
{
	global $wpdb;
	//delete event hour bookings associated with the event
	$query = $wpdb->prepare("DELETE FROM `" . $wpdb->prefix . "event_hours_booking` WHERE event_hours_id IN (
		SELECT event_hours_id FROM `" . $wpdb->prefix . "event_hours` WHERE event_id=%d
	)", $post_id);
	$wpdb->query($query);
	
	//delete event hours associated with the event
	$query = $wpdb->prepare("DELETE FROM `" . $wpdb->prefix . "event_hours` WHERE event_id=%d", $post_id);
	$wpdb->query($query);	
}
add_action("delete_post", "timetable_delete_events");

//custom events items list
function timetable_events_edit_columns($columns)
{
	$new_columns = array(  
		"cb" => "<input type=\"checkbox\" />",  
		"title" => _x('Title', 'post type singular name', 'timetable'),
		"events_category" => esc_html__('Categories', 'timetable'),
		"date" => esc_html__('Date', 'timetable')
	);    

	return array_merge($new_columns, $columns);  
}  
$timetable_events_settings = timetable_events_settings();
add_filter("manage_edit-" . $timetable_events_settings["slug"] . "_columns", "timetable_events_edit_columns"); 
function timetable_manage_events_posts_custom_column($column)
{
	global $post;
	switch ($column)
	{
		case "events_category":
			$events_category_list = (array)get_the_terms($post->ID, "events_category");
			foreach($events_category_list as $events_category)
			{
				if(empty($events_category->slug))
					continue;
				echo '<a href="' . esc_url(admin_url("edit.php?post_type=events&events_category=" . $events_category->slug)) . '">' . $events_category->name . '</a>' . (end($events_category_list)!=$events_category ? ", " : "");;
			}
			break;
	}
}
add_action("manage_" . $timetable_events_settings["slug"] . "_posts_custom_column", "timetable_manage_events_posts_custom_column");

function timetable_filter_events_by_taxonomies($post_type)
{
	$timetable_events_settings = timetable_events_settings();
	//Apply this only on a specific post type
	if($timetable_events_settings["slug"]!==$post_type)
		return;

	//A list of taxonomy slugs to filter by
	$taxonomies = array('events_category');

	foreach($taxonomies as $taxonomy_slug)
	{
		// Retrieve taxonomy data
		$taxonomy_obj = get_taxonomy($taxonomy_slug);
		$taxonomy_name = $taxonomy_obj->labels->name;

		// Retrieve taxonomy terms
		$terms = get_terms( $taxonomy_slug );

		// Display filter HTML
		echo "<select name='" . esc_attr($taxonomy_slug) . "' id='" . esc_attr($taxonomy_slug) . "' class='postform'>";
		echo '<option value="">' . sprintf(esc_html__('Show All %s', 'timetable'), $taxonomy_name) . '</option>';
		foreach($terms as $term)
		{
			printf('<option value="%1$s" %2$s>%3$s (%4$s)</option>', $term->slug, ((isset($_GET[$taxonomy_slug]) && ($_GET[$taxonomy_slug]==$term->slug)) ? ' selected="selected"' : ''), $term->name, $term->count);
		}
		echo '</select>';
	}
}
add_action('restrict_manage_posts', 'timetable_filter_events_by_taxonomies', 10, 2);

function timetable_set_warning_available_places($location)
{
	return add_query_arg("timetable_warning", "available_places", $location);
}

function timetable_warning_available_places()
{
	echo "
	<div class='notice notice-error'>
		<p>" . esc_html__("Error: Available slots value must be equal or greater than the number of existing bookings.", "timetable") . "</p>
	</div>";
}

?>