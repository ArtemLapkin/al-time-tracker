<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.linkedin.com/in/artemlapkin/
 * @since      1.0.0
 *
 * @package    Al_Time_Tracker
 * @subpackage Al_Time_Tracker/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Al_Time_Tracker
 * @subpackage Al_Time_Tracker/admin
 * @author     Artem Lapkin <laplin.artem@gmail.com>
 */
class Al_Time_Tracker_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    private $option_name;


    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

        $this->option_name = 'al_time_tracker';

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Al_Time_Tracker_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Al_Time_Tracker_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/al-time-tracker-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Al_Time_Tracker_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Al_Time_Tracker_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/al-time-tracker-admin.js', array( 'jquery' ), $this->version, false );
	}

    /**
     * Add page to wp-admin
     */
    public function page_wp_admin() {
        add_menu_page('Time Tracker', 'Time Tracker', 'read', __FILE__, array($this, 'settings_page_callback'));
    }

    public function settings_page_callback() {
        echo '<div class="wrap">';
        if(current_user_can('manage_options')){
            $this->page_admin_callback();
        }else{
            $this->page_subscriber_callback();
        }
        echo '</div>';
    }


    /**
     * Print page for admin
     */
    public function page_admin_callback() {
        $users_with_time_records = $this->get_all_users_with_time_records();

        echo '<h2>'.__('Time Tracker' , 'al-time-tracker').'</h2>';

        $months = $this->get_months_previous();

        if(empty($users_with_time_records)){
            echo '<div class="wrap">';
            _e('No users with work time records', 'al-time-tracker');
            echo '</div>';
            return;
        }



        $current_month = (int)date('m');
        $current_month_timestamp = isset($_GET['month']) ? $_GET['month'] : mktime(0, 0, 0, $current_month, 1);
        $user_ID = isset($_GET['user_id']) ? $_GET['user_id'] : $users_with_time_records[0]->ID;

        echo '<div class="al-form-wrap">';
        echo '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">';
        echo '<input type="hidden" name="page" value="al-time-tracker/admin/class-al-time-tracker-admin.php" />';
        echo '<label for="user-id">Username</label>';
        echo '<select name="user_id" id="user-id" class="wp-heading-inline">';
        foreach ($users_with_time_records as $user) {
            $selected = selected($user_ID, $user->ID, false);
            echo '<option value="'.$user->ID.'" ' . $selected . '>'.ucfirst($user->data->user_login).'</option>';
        }
        echo '</select>';

        echo '<label for="month">Month</label>';
        echo '<select name="month" id="month" class="wp-heading-inline">';
        foreach ($months as $timestamp => $month) {
            $selected = selected($current_month_timestamp, $timestamp, false);
            echo '<option value="' . $timestamp . '" ' . $selected . '>' . $month . '</option>';
        }
        echo '</select>';

        echo '<input class="button button-primary wp-heading-inline" type="submit" name="submit" value="Search">';

        $user_data = $this->get_user_work_time_for_month($user_ID, $current_month_timestamp);

        if(!is_array($user_data) || !empty($user_data)){
            echo '<input class="button button-primary al-pull-right" type="submit" name="al_get_csv" value="Get CSV">';
        }

        echo '</form>';
        echo '</div>';


        echo '<div class="wrap">';

            $this->print_data($user_data);
        echo '</div>';
    }

    /**
     * Print page for Subscriber
     */
    public function page_subscriber_callback() {

        $this->form_handler_subscriber();

        $is_timetracking = $this->is_timetracking();

        $disabled_end = ' disabled="disabled"';
        $disabled_start = '';

        if($is_timetracking){
            $disabled_start = ' disabled="disabled"';
            $disabled_end = '';
        }

        echo '<h2>'.__('Time Tracker' , 'al-time-tracker').'</h2>';

        echo '<div class="al-form-wrap">';
        echo '<form method="post">';
        echo '<input class="button button-primary" type="submit" name="action" value="start" '.$disabled_start.'>';
        echo '<input class="button" type="submit" name="action" value="stop" '.$disabled_end.'>';
        echo '</form>';
        echo '</div>';

        $user_data = $this->get_user_work_time_two_month();

        echo '<div class="wrap">';
        $this->print_data($user_data);
        echo '</div>';
    }

    /**
     * Handles Time Adding form
     * @return bool|void
     */
    public function form_handler_subscriber() {
        // if not admin
        if(!is_user_logged_in() || current_user_can('manage_options')) return false;

        $post_action = $this->get_post_action();
        if(!$post_action) return;


        $user_ID = get_current_user_id();
        $user_data = $this->get_user_work_time($user_ID);
        if($post_action == 'start'){
            if(is_array($user_data) && !empty($user_data)){
                $end = end($user_data);
                if(isset($end['end']) && $end['end'] == false){
                    _e('Time is already tracking now', 'al-time-tracker');
                    return;
                }
            }else{
                $user_data = array();
            }
            $new = array('start' => time(),'end' => '');

            $user_data[] = $new;

            $this->update_user_work_time($user_ID, $user_data);
        }else{

            if(empty($user_data)) return;

            $end = end($user_data);
            if(isset($end['end']) && $end['end'] != false){
                _e('Time is not tracking now', 'al-time-tracker');
                return;
            }
            $user_data[key($user_data)]['end'] = time();

            $this->update_user_work_time($user_ID, $user_data);
        }
    }

    /**
     * Returns true if time is tracking now for logged user
     * @return bool
     */
    public function is_timetracking() {
        $user_ID = get_current_user_id();
        $user_data = $this->get_user_work_time($user_ID);
        if(!is_array($user_data) || empty($user_data)) return false;

        $end = end($user_data);

        return $end['end'] == false;
    }

    /**
     * Returns $_POST['action'] start/stop/ FALSE
     * @return bool
     */
    public function get_post_action() {
        if(!isset($_POST['action'])) return false;

        $post_action = $_POST['action'];
        $values = array('start', 'stop');

        if(!in_array($post_action, $values)) return false;

        return $post_action;
    }

    /**
     * Returns all userm work time
     * @param bool $user_id
     * @return array|mixed
     */
    public function get_user_all_work_time($user_id = false) {

        $user_id = $user_id == false ? get_current_user_id() : $user_id;

        return $this->get_user_work_time($user_id);
    }

    /**
     * Returns user work for prev 2 months
     * @return array
     */
    public function get_user_work_time_two_month() {
        $user_data = $this->get_user_all_work_time();
        $two_months_timestamp = strtotime('-2 months');
        $out = array();
        foreach ($user_data as $user_datum) {
            if($user_datum['start'] < $two_months_timestamp) continue;
            $out[] = $user_datum;
        }
        return $out;
    }

    /**
     * Prints User work time in a table
     * @param $data
     */
    public function print_data($data) {

        if(!is_array($data) || empty($data)){
            _e('No time records', 'al-time-tracker');
            return;
        }

        echo '<table class="wp-list-table widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Start') . '</th>';
        echo '<th>' . __('End') . '</th>';
        echo '<th>' . __('Total Time') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '</tbody>';
        foreach ($data as $user_datum) {
            $end = $user_datum['end'];
            $start = $this->get_date_formatted($user_datum['start']);
            if($end == ''){
                $end = __('Running', 'al-time-tracker');
                $diff = '...';
            }else{
                $end =  $this->get_date_formatted($end);

                $diff_sec = $user_datum['end'] - $user_datum['start'];
                $diff = $this->get_hours_from_seconds($diff_sec);
            }
            echo '<tr>';
            echo '<td>' . $start . '</td>';
            echo '<td>' . $end . '</td>';
            echo '<td>' . $diff  . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Gets user meta and returns it
     * @param $user_ID
     * @return array|mixed
     */
    public function get_user_work_time($user_ID) {
//	    delete_user_meta($user_ID, $this->option_name);
        $data = get_user_meta($user_ID, $this->option_name, true);
        $data = is_array($data) ? $data : array();
        return $data;
    }

    /**
     * updates user meta
     * @param $user_ID
     * @param $user_data
     * @return bool|int
     */
    public function update_user_work_time($user_ID, $user_data) {
        return update_user_meta($user_ID, $this->option_name, $user_data);
    }

    /**
     * Returns formatted date string
     * @param $timestamp
     * @return false|string
     */
    public function get_date_formatted($timestamp) {
        if(!$timestamp) return '';
        return date('Y:m:j H:i:s', $timestamp);
    }

    /**
     * Returns work hours
     * @param $seconds
     * @return false|string
     */
    public function get_hours_from_seconds($seconds) {
        // if somebody worked more than one day ( if its possible)
        if($seconds > 86399){
            return 'day ' . gmdate("j, H:i:s", $seconds);
        }
        return gmdate("H:i:s", $seconds);
    }

    /**
     * Get all users that have time records
     */
    public function get_all_users_with_time_records() {
        $args = array(
            'role'         => 'subscriber',
            'meta_key'     => $this->option_name,
            'meta_value'   => '',
            'meta_compare' => '!=',
        );
        return get_users( $args );
    }

    /**
     * Get -6 monts +6months
     * @return array
     */
    public function get_months_previous() {

        $months= array();


        $start    = new DateTime('11 months ago');

        $start->modify('first day of this month');
        $end      = new DateTime();
        $interval = new DateInterval('P1M');
        $period   = new DatePeriod($start, $interval, $end);
        foreach ($period as $dt) {
            $months[strtotime($dt->format('F Y'))] = $dt->format('F');
        }

        return $months;
    }

    /**
     * Get time records for user for particular month
     * @param $user_ID
     * @param $month
     * @return array
     */
    public function get_user_work_time_for_month($user_ID, $month) {

        $user_data = $this->get_user_work_time($user_ID);
        if(empty($user_data)) return array();

        $month_next = strtotime("+1 month", $month);

        $out = array();

        foreach ($user_data as $user_datum) {
            if($user_datum['start'] >= $month && $user_datum['start'] > $month_next || $user_datum['start'] < $month) continue;
            $out[] = $user_datum;
        }

        return $out;
    }

    /**
     * Fires after click on 'get csv' button
     */
    public function get_csv() {

        if(!isset($_GET['al_get_csv'])) return;

        $user_ID = isset($_GET['user_id']) ? $_GET['user_id'] : false;
        $month = isset($_GET['month']) ? $_GET['month'] : false;

        $data = $this->get_user_work_time_for_month($user_ID, $month);

        if(empty($data)) wp_die(__('No records', 'al-time-tracker'));

        $this->get_formatted_array_for_csv($data, $user_ID, $month);

    }

    /**
     * returns an array ready for converting to CSV
     * @param $arr
     * @param $user_ID
     * @param $month
     * @return mixed
     */
    public function get_formatted_array_for_csv($arr, $user_ID, $month) {
        if(empty($arr)) return $arr;

        $keys = array_keys($arr[0]);
        $out = array($keys);
        foreach ($arr as $item) {
            $out[] = array_map(array($this, 'get_date_formatted'), array_values($item));
        }


        $userdata = get_userdata($user_ID);
        $user_login = $userdata->data->user_login;

        $month = date('Y-m', $month);

        $csv_path = plugin_dir_path(dirname(__FILE__)) . 'csv/';
        $file_name = $user_login . '-' . $month . '.csv';
        $file_path = $csv_path . $file_name;
        $fp = fopen($file_path, 'w');
        foreach ($out as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        $csv_url = plugin_dir_url(dirname(__FILE__)). 'csv/';
        $file_url = $csv_url . $file_name;

//        wp_redirect($file_url);


        header('Content-type:  application/csv');
        header('Content-Length: ' . filesize($file_path));
        header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
        readfile($file_url);


        register_shutdown_function('unlink', $file_path);
    }

}
