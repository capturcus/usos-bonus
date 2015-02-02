<?php
/**
 * @package project_db
 * @version 1.0
 */
/*
Plugin Name: Usos Bonus
Plugin URI: nope.com
Description: Wtyczka do WSL, która dodaje bonusy z usosa
Author: Marcin Parafiniuk
Version: 1.0
Author URI: http://students.mimuw.edu.pl/~mp347235
*/

register_activation_hook(__FILE__, 'init_db');

function init_db() {
	global $wpdb;
	$wpdb->query("CREATE TABLE alerty (
  alerty_id int(11) NOT NULL AUTO_INCREMENT,
  data date NOT NULL,
  tresc varchar(255) NOT NULL,
  identifier int(11) NOT NULL,
  PRIMARY KEY (alerty_id)
);");

$wpdb->query("
CREATE TABLE deklaracje (
  ID bigint(20) NOT NULL AUTO_INCREMENT,
  description varchar(255) NOT NULL,
  identifier int(11) NOT NULL,
  PRIMARY KEY (ID)
);");

$wpdb->query("
CREATE TABLE homework_groups (
  group_id int(11) NOT NULL,
  identifier int(11) NOT NULL
);");

$wpdb->query("
CREATE TABLE point_nodes (
  node_id int(11) NOT NULL,
  type varchar(255) NOT NULL,
  name varchar(255) DEFAULT NULL,
  pkt float DEFAULT NULL,
  comment varchar(255) DEFAULT NULL,
  parent int(11) DEFAULT NULL,
  identifier int(11) NOT NULL,
  przedmiot_id varchar(255) NOT NULL
);");

$wpdb->query("
CREATE TABLE prace_domowe (
  prace_domowe_id int(11) NOT NULL AUTO_INCREMENT,
  praca_content longtext NOT NULL,
  identifier int(11) NOT NULL,
  task_number int(11) NOT NULL,
  PRIMARY KEY (prace_domowe_id)
);");

$wpdb->query("
CREATE TABLE przedmioty (
  przedmioty_id varchar(255) NOT NULL,
  nazwa varchar(255) NOT NULL,
  ID int(11) DEFAULT NULL,
  PRIMARY KEY (przedmioty_id),
  KEY przedmioty_wp_users (ID)
);");

$wpdb->query("
CREATE TABLE wydarzenia (
  wydarzenie_id int(11) NOT NULL AUTO_INCREMENT,
  data timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  opis varchar(255) NOT NULL,
  identifier int(11) NOT NULL,
  has_alert tinyint(1) NOT NULL,
  PRIMARY KEY (wydarzenie_id)
);");

$wpdb->query("
CREATE TABLE wyroznienia (
  ID int(11) NOT NULL,
  wyroznienie_id int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (wyroznienie_id)
);");

$wpdb->query("
CREATE TABLE zajecia (
  zajecia_id int(11) NOT NULL AUTO_INCREMENT,
  opis varchar(255) DEFAULT NULL,
  typ varchar(255) DEFAULT NULL,
  przedmioty_id varchar(255) NOT NULL,
  start_time varchar(255) NOT NULL,
  end_time varchar(255) NOT NULL,
  identifier int(11) NOT NULL,
  PRIMARY KEY (zajecia_id)
);");

}

function getCurrentIdentifier() {
	global $wpdb;
	$current_user = wp_get_current_user();
	$results = $wpdb->get_results("select identifier, user_id from wp_wslusersprofiles where user_id = {$current_user->id};", ARRAY_N);
	if(isset($results[0][0]))
		return $results[0][0];
	else
		return 0;
}

function makeTable($sql) {
	global $wpdb;
	$results = $wpdb->get_results($sql, ARRAY_N);
	showTable($results);
}

add_action( 'admin_menu', 'settings_menu' );

function my_tweaked_admin_bar() {
	global $wp_admin_bar;
	global $wpdb;
	$current_user = wp_get_current_user();
	$results = $wpdb->get_results("select ID, count(ID) as cnt from wyroznienia where ID = {$current_user->ID} group by ID;", ARRAY_N);
	$str = 'Moje odznaki: ' . (isset($results[0][1]) ? $results[0][1] : 0);
	$args = array(
		'id'    => 'odznaki',
		'title' => $str
	);
	$wp_admin_bar->add_node( $args );
}

add_action( 'wp_before_admin_bar_render', 'my_tweaked_admin_bar' );

function settings_menu() {
	add_options_page( 'DB Project Settings', 'Dodaj wydarzenie', 'read', 'db_settings_slug', 'display_settings' );
	add_options_page( 'DB Project Settings', 'Ustawienia grup prac domowych', 'read', 'db_homework_slug', 'display_homework_settings' );
	add_options_page( 'DB Project Settings', 'Wyślij maila do grupy', 'manage_options', 'db_send_mail_slug', 'display_send_mail' );
	add_options_page( 'DB Project Settings', 'Przyznaj odznakę', 'manage_options', 'db_award_badge_slug', 'display_award_badge' );
}

function display_award_badge() {
	echo '<div class="wrap">';
	echo 'Komu chcesz przyznać odznakę?<table>';
	global $wpdb;
	$results = $wpdb->get_results("select ID, display_name from wp_users;");
	foreach ($results as $value) {
		echo "<tr><td>{$value->display_name}</td><td><a href=../wp-admin/admin-post.php?action=award_badge&id={$value->ID}>Odznacz!</a></td></tr>";
	}
	echo '</table></div>';
}

function display_send_mail() {
	echo '<div class="wrap">';
	echo '<form action="../wp-admin/admin-post.php" method="post">';
	echo	'<input type="hidden" name="action" value="send_mail">';
	echo	'Numer grupy: <input type="text" name="group_num"><br>';
	echo	'Temat: <input type="text" name="subject">';
	echo	'<br>Treść:<br><textarea name="body" rows=10 cols=80></textarea><br>';
	echo 	'<input type="submit" value="Wyślij!">';
	echo '</form>';
	echo '</div>';
}

function display_settings() {
	echo '<div class="wrap">';
	echo '<p>Dodaj nowe wydarzenie.</p>';
	echo '<form action="../wp-admin/admin-post.php" method="post">';
	echo	'<input type="hidden" name="action" value="add_data">';
	echo	'<p>Data:</p>';
	echo	'<input type="date" name="date">';
	echo	'<input type="time" name="time">';
	echo	'<p>Wydarzenie:<br></p>';
	echo	'<input type="text" name="wydarzenie"><br>';
	echo	'Ustawić alert?';
	echo	'<input type="checkbox" name="alert"><br>';
	echo 	'<input type="submit" value="Wyślij!">';
	echo '</form>';
	echo '</div>';
}

function display_homework_settings() {
	echo '<div class="wrap">';
	echo '<p>Dodaj grupę prac domowych.</p>';
	echo '<form action="../wp-admin/admin-post.php" method="post">';
	echo	'<input type="hidden" name="action" value="edit_hw_group">';
	echo	'Nowa grupa prac domowych: ';
	echo	'<input type="text" name="hw_group"><br>';
	echo 	'<input type="submit" value="Wyślij!">';
	echo '</form>';
	echo '</div>';
}

add_action( 'admin_post_add_data', 'prefix_admin_add_data' );
add_action( 'admin_post_post_hw', 'prefix_admin_post_hw' );
add_action( 'admin_post_edit_hw_group', 'prefix_admin_edit_hw_group' );
add_action( 'admin_post_add_declaration', 'prefix_admin_add_declaration' );
add_action( 'admin_post_send_mail', 'prefix_admin_send_mail' );
add_action( 'admin_post_award_badge', 'prefix_admin_award_badge' );

function prefix_admin_award_badge() {
	global $wpdb;
	$id = $_GET["id"]; //przekazywanie id przez geta nie jest aż _takie_ głupie, wordpress do pewnego stopnia pilnuje uprawnień
	$wpdb->get_results("insert into wyroznienia (ID) values ({$id});");
	$url = get_home_url();
	header("Location: {$url}");
}

function prefix_admin_send_mail() {
	global $wpdb;
	$group_id = $_POST["group_num"];
	$emails = $wpdb->get_results(
		"select user_email from homework_groups join wp_wslusersprofiles on homework_groups.identifier = wp_wslusersprofiles.identifier
		join wp_users on wp_wslusersprofiles.user_id = wp_users.ID where homework_groups.group_id = {$group_id};"
	);
	$finalEmails = array();
	foreach ($emails as $value) {
		$finalEmails[] = $value->user_email;
	}

	wp_mail($finalEmails, $_POST["subject"], $_POST["body"]);

	$url = get_home_url();
	header("Location: {$url}");
}

function prefix_admin_add_declaration() {
	$notes_name = $_POST["notes_name"];
	$id = getCurrentIdentifier();
	global $wpdb;
	$wpdb->get_results("insert into deklaracje (description, identifier) values (\"{$notes_name}\", {$id});");
	$url = get_home_url();
	header("Location: {$url}");
}

function prefix_admin_edit_hw_group() {
	global $wpdb;
	$id = getCurrentIdentifier();
	$hw_group = $_POST["hw_group"];
	$wpdb->get_results("insert into homework_groups values ({$hw_group}, {$id});");
	$url = get_home_url();
	header("Location: {$url}");
}

function prefix_admin_add_data() {
	global $wpdb;
	$id = getCurrentIdentifier();
	$date = $_POST["date"];
	$date .= " ";
	$date .= $_POST["time"];
	$wydarzenie = $_POST["wydarzenie"];
	$has_alert = ($_POST["alert"] == "on" ? 1 : 0);
	$results = $wpdb->get_results(
		"insert into wydarzenia (data, opis, identifier, has_alert) values (\"{$date}\", \"{$wydarzenie}\", {$id}, {$has_alert}); "
	);
	$url = get_home_url();
	header("Location: {$url}");
}

add_action( 'init', 'register_shortcodes');

function register_shortcodes(){
   add_shortcode('db_calendar', 'db_calendar');
   add_shortcode('db_post_homework', 'db_post_homework');
   add_shortcode('db_show_homework', 'db_show_homework');
   add_shortcode('db_notes_declarations', 'db_notes_declarations');
   add_shortcode('db_show_points', 'db_show_points');
   add_shortcode('db_posted_homework', 'db_posted_homework');
}

function db_posted_homework() {
	global $wpdb;
	$wpdb->show_errors();
	$results = $wpdb->get_results("select display_name, praca_content, task_number from
		prace_domowe join wp_wslusersprofiles on prace_domowe.identifier = wp_wslusersprofiles.identifier join wp_users on wp_wslusersprofiles.user_id = wp_users.ID;");
	foreach ($results as $hw) {
		echo $hw->display_name;
		echo ", praca nr ";
		echo $hw->task_number;
		echo "<br>";
		echo $hw->praca_content;
		echo "<hr>";
	}
}

function showNode($node_id){
	$id = getCurrentIdentifier();
	global $wpdb;
	$results = $wpdb->get_results("select * from point_nodes where node_id = {$node_id} and identifier = {$id};", ARRAY_N);
	if($results[0][1] == "oc")
		return;
	echo "<li>";
	echo $results[0][2];
	if($results[0][1] == "pkt"){
		echo " - ";
		echo $results[0][3];
		if($results[0][4] != ""){
			echo "; KOMENTARZ: ";
			echo $results[0][4];
		}
	}
	$results = $wpdb->get_results("select * from point_nodes where parent = {$node_id} and identifier = {$id};");
	if(count($results) != 0){
		echo "<ul>";
		foreach ($results as $value) {
			showNode($value->node_id);
		}
		echo "</ul>";
	}
	echo "</li>";
}

function showTree($pid, $node_id) {
	global $wpdb;
	$id = getCurrentIdentifier();

	$results = $wpdb->get_results("select nazwa from point_nodes left join przedmioty on przedmiot_id = przedmioty_id where node_id = {$node_id} and identifier = {$id};", ARRAY_N);

	if(isset($results[0][0]))
		echo ($results[0][0]);
	else
		return;

	showNode($node_id);

	echo "<br>";
	echo "<hr>";
}

function db_show_points() {
	$id = getCurrentIdentifier();
	global $wpdb;
	$results = $wpdb->get_results("select przedmiot_id, node_id from point_nodes where identifier = {$id} and type like \"root|1\";");
	foreach ($results as $value) {
		showTree($value->przedmiot_id, $value->node_id);
	}
	echo "<h1>STARE PRZEDMIOTY</h1>";
	echo "<hr>";
	$results = $wpdb->get_results("select przedmiot_id, node_id from point_nodes where identifier = {$id} and type like \"root|0\";");
	foreach ($results as $value) {
		showTree($value->przedmiot_id, $value->node_id);
	}
}

function db_notes_declarations() {
	global $wpdb;
	$id = getCurrentIdentifier();
	$results = $wpdb->get_results(
		"select deklaracje.ID as ID, deklaracje.description as description, display_name, post_title, deklaracja_post_id from deklaracje
		join wp_wslusersprofiles on deklaracje.identifier = wp_wslusersprofiles.identifier
		join wp_users on wp_users.ID = wp_wslusersprofiles.user_id
		left join
		(select
		post_title,
		m1.meta_value as deklaracja_id,
		wp_posts.ID as deklaracja_post_id
		from wp_posts
		join wp_postmeta as m1 on (m1.post_id = wp_posts.ID and m1.meta_key = \"deklaracja_id\")) as temp
		on temp.deklaracja_id = deklaracje.ID
		;
	");

	echo '<table>';
	echo "<tr><td><b>Osoba</b></td><td><b>Notatki</b></td><td><b>ID deklaracji</b></td><td><b>Post</b></td></tr>";
	foreach ($results as $row) {
		$temp_post = "";
		$permalink = get_permalink($row->deklaracja_post_id);
		$temp_post = "<a href={$permalink}>{$row->post_title}</a>";
		echo "<tr><td>{$row->display_name}</td><td>{$row->description}</td><td>{$row->ID}</td><td>{$temp_post}</td></tr>";
	}
	echo '</table>';

	echo '<div class="wrap">';
	echo '<p>Zdeklaruj się do napisania notatek:<br></p>';
	echo '<form action="./wp-admin/admin-post.php" method="post">';
	echo	'<input type="hidden" name="action" value="add_declaration">';
	echo	'Jakie to notatki: ';
	echo	'<input type="text" name="notes_name"><br>';
	echo 	'<input type="submit" value="Wyślij!">';
	echo '</form>';
	echo '</div>';
}

function db_show_homework() {
	global $wpdb;
	$id = getCurrentIdentifier();
	$wpdb->show_errors();
	$response = $wpdb->get_results("
		select *, count(prace_domowe.prace_domowe_id) as cnt from
		(select
		post_title,
		m1.meta_value as homework_group,
		m2.meta_value as homework_number,
		str_to_date(m3.meta_value, '%d.%m.%Y') as homework_deadline,
		wp_posts.ID as homework_post
		from wp_posts
		join wp_postmeta as m1 on (m1.post_id = wp_posts.ID and m1.meta_key = \"homework_group\")
		join wp_postmeta as m2 on (m2.post_id = wp_posts.ID and m2.meta_key = \"homework_number\")
		join wp_postmeta as m3 on (m3.post_id = wp_posts.ID and m3.meta_key = \"homework_deadline\")
		) as temp
		left join prace_domowe on prace_domowe.identifier = {$id} and homework_number = prace_domowe.task_number
		where datediff(homework_deadline, now()) > 0 and homework_group in (select group_id from homework_groups where identifier = {$id})
		group by post_title
		;
		");
	echo "Twoje prace domowe:<br><table>";
	echo "<b><tr><td><b>Tytuł</b></td><td><b>Numer grupy</b></td><td><b>Numer pracy domowej</b></td><td><b>Deadline</b></td><td><b>Ilość wysłanych prac</b></td></tr>";
	$vals = array("post_title", "homework_group", "homework_number", "homework_deadline", "cnt");
	foreach ($response as $row) {
		echo "<tr>";
		foreach($vals as $val) {
			echo "<td>";
			if($val != "post_title")
				echo $row->$val;
			else
			{
				$link = get_permalink($row->homework_post);
				echo "<a href={$link}>{$row->$val}</a>";
			}
			echo "</td>";
		}
		echo "</tr>";
	}
	echo "</table>";
}

function db_post_homework() {
	echo '<div class="wrap">';
	echo '<form action="./wp-admin/admin-post.php" method="post">';
	echo	'<input type="hidden" name="action" value="post_hw">';
	echo	'Numer pracy domowej: <input type="text" name="task_num"><br>';
	echo	'<textarea name="hw_content" rows=10 cols=80>Wpisz treść swych wypocin tutaj.</textarea><br>';
	echo 	'<input type="submit" value="Wyślij!">';
	echo '</form>';
	echo '</div>';
}

function prefix_admin_post_hw() {
	global $wpdb;
	$id = getCurrentIdentifier();
	$content = $_POST["hw_content"];
	$task_num = $_POST["task_num"];
	$wpdb->get_results("insert into prace_domowe (praca_content, identifier, task_number) values (\"{$content}\", {$id}, {$task_num});");
	$url = get_home_url();
	header("Location: {$url}");
}

function addToCalendarArray(&$calendar_array, $d, $i, $nazwa, $has_alert){
	if($has_alert == 1){
		$nazwa = "<b>".$nazwa."</b>";
	}
	if(empty($calendar_array[$d][$i]))
		$calendar_array[$d][$i] = $nazwa;
	else
		$calendar_array[$d][$i] = $calendar_array[$d][$i] . "<br>" . $nazwa;
}

function db_calendar() {
	$ret = "";
	global $wpdb;
	$id = getCurrentIdentifier();
	$results = $wpdb->get_results("select nazwa, start_time, end_time from zajecia left join przedmioty on zajecia.przedmioty_id = przedmioty.przedmioty_id where zajecia.identifier = {$id} order by start_time;");
	$calendar_array = array();
	$calendar_array[0][0] = "";
	$dt = new DateTime();
	$dt->setTime(0,0);
	foreach ($results as $obj) { 
		$ot = date_create_from_format("Y-m-d H:i:s", $obj->start_time);
		$dd = date_diff($dt, $ot);
		if($dd->h < 10){
			addToCalendarArray($calendar_array, $dd->d, 0, $obj->nazwa, 0);
		} else if ($dd->h < 12) {
			addToCalendarArray($calendar_array, $dd->d, 1, $obj->nazwa, 0);
		} else if ($dd->h < 14) {
			addToCalendarArray($calendar_array, $dd->d, 2, $obj->nazwa, 0);
		} else if ($dd->h < 16) {
			addToCalendarArray($calendar_array, $dd->d, 3, $obj->nazwa, 0);
		} else {
			addToCalendarArray($calendar_array, $dd->d, 4, $obj->nazwa, 0);
		}
	}
	$results = $wpdb->get_results("select opis, data, has_alert from wydarzenia where data between date(now()) and date_add(date(now()), interval 7 day) and identifier = {$id};");
	foreach ($results as $obj) { 
		$ot = date_create_from_format("Y-m-d H:i:s", $obj->data);
		$dd = date_diff($dt, $ot);
		if($dd->h < 10){
			addToCalendarArray($calendar_array, $dd->d, 0, $obj->opis, $obj->has_alert);
		} else if ($dd->h < 12) {
			addToCalendarArray($calendar_array, $dd->d, 1, $obj->opis, $obj->has_alert);
		} else if ($dd->h < 14) {
			addToCalendarArray($calendar_array, $dd->d, 2, $obj->opis, $obj->has_alert);
		} else if ($dd->h < 16) {
			addToCalendarArray($calendar_array, $dd->d, 3, $obj->opis, $obj->has_alert);
		} else {
			addToCalendarArray($calendar_array, $dd->d, 4, $obj->opis, $obj->has_alert);
		}
	}

	$daysOfWeek = array("", "Dzisiaj", "Jutro", date('Y-m-d', strtotime("+2 days")), date('Y-m-d', strtotime("+3 days")), date('Y-m-d', strtotime("+4 days")), date('Y-m-d', strtotime("+5 days")), date('Y-m-d', strtotime("+6 days")));
	echo "<table>";
	echo "<tr>";
	for ($i=0; $i <= 7; $i++) { 
		echo "<td><b>";
		echo $daysOfWeek[$i];
		echo "</b></td>";
	}
	echo "</tr>";
	for ($i=0; $i < 5; $i++) { 
		echo "<tr>";
		echo "<td><b>";
		echo 8+2*$i;
		echo ":00";
		echo "</b></td>";
		for ($j=0; $j < 7; $j++) {
			echo "<td>";
			echo $calendar_array[$j][$i];
			echo "</td>";
		}
		echo "</tr>";
	}
	echo "</table>";
}

function php_execute($html){
	if(strpos($html,"<"."?php")!==false)
	{ ob_start(); eval("?".">".$html);
		$html=ob_get_contents();
		ob_end_clean();
	}
	return $html;
}

function display_alerts() {
	global $wpdb;
	$id = getCurrentIdentifier();
	$results = $wpdb->get_results("select data, opis from wydarzenia where has_alert = 1 and datediff(data, now()) < 7 and datediff(data, now()) > 0 and identifier = {$id};");
	echo "<table>";
	foreach ($results as $value) {
		echo "<tr>";

		echo "<td>";
		echo $value->opis;
		echo "</td>";

		echo "<td>";
		echo $value->data;
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";
}

add_filter('widget_text','php_execute',100);

?>