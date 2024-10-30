<?php

class HashViewer {
	private static $instance = null;

	private $twig;
	private $slugs;

	public static function get_instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self;
		return self::$instance;
	}

	private function __construct() {

		# Load Composer plugins
		require_once(HASHVIEWER_PLUGIN_DIR. '/vendor/autoload.php');
		# Load Instagram-PHP-API
		require_once(HASHVIEWER_PLUGIN_DIR. '/vendor/Instagram.class.php');

		$loader = new Twig_Loader_Filesystem(HASHVIEWER_PLUGIN_DIR . '/views/');
		$this->twig = new Twig_Environment($loader);

		// Add default actions
		add_action( 'admin_init', array($this, 'admin_init') );
		add_action( 'admin_menu', array($this, 'admin_menu_setup'));
		add_action( 'wp_enqueue_styles', array( $this, 'register_frontend_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_scripts' ) );

		$this->slugs = array(
			'main' => 'hashviewer_main',
			'new_competition' => 'hashviewer_new_competition',
			'browse' => 'hashviewer_browse'
		);
	}

	/**
	 * Initial setup
	 */
	// Frontend
	public function register_frontend_scripts() {
		wp_enqueue_script( 'hashviewer_script', HASHVIEWER_PLUGIN_URL . 'js/main.js' );
	}
	public function register_frontend_styles() {
		wp_enqueue_style( 'hashviewer-style', HASHVIEWER_PLUGIN_URL . 'css/main.css' );
		wp_enqueue_style( 'bootstrap-style', HASHVIEWER_PLUGIN_URL . 'css/bootstrap.min.css' );
	}

	// Admin
	public function admin_menu_setup() {
		add_menu_page( "HashViewer", "HashViewer", 'manage_options', 
			$this->slugs['main'], array($this, 'all_competitions'), HASHVIEWER_PLUGIN_URL . '/img/menu_icon.png' );
		add_submenu_page( $this->slugs['main'], "HashViewer - Browse", "All Competitions", 'manage_options',
			$this->slugs['main']); // main menu item
		add_submenu_page( "hashviewer_main", "HashViewer - New competition", "New competition", 'manage_options',
			$this->slugs['new_competition'], array($this, 'new_competition') );
		add_submenu_page( "hashviewer_main", "HashViewer - Browse", "Browse Instagram", 'manage_options',
			$this->slugs['browse'], array($this, 'browse') );
	}
	
	public function admin_init() {
		wp_enqueue_script( 'hashviewer_script', HASHVIEWER_PLUGIN_URL . 'js/main.js', array('jquery') );


		wp_register_style( 'bootstrap-style', HASHVIEWER_PLUGIN_URL . 'css/bootstrap.min.css' );
		wp_register_style( 'hashviewer-style', HASHVIEWER_PLUGIN_URL . 'css/main.css' );

		wp_enqueue_style( 'bootstrap-style' );
		wp_enqueue_style( 'hashviewer-style' );

	}

	/**
	 * Views
	 */
	public function all_competitions() {
		if ( $_SERVER["REQUEST_METHOD"] == "POST" ){
			if ($_POST["action"] == "delete-competition" && isset($_POST["compId"])){
				$this->deleteCompetition($_POST["compId"]);
				echo $this->twig->render('competition_action.twig.html', array(
					"action"		=> "deleted",
					"return_url" => get_admin_url() . 'admin.php?page=' . $this->slugs['main']
				));
			}
		} else {
			$data = array(
				"plugin_url"	=> HASHVIEWER_PLUGIN_URL,
				"new_comp_url"	=> get_admin_url() . 'admin.php?page=' . $this->slugs['new_competition'], 
				"competitions" 	=> $this->getAllCompetitions(),
				"browse_url"	=> get_admin_url() . 'admin.php?page=' . $this->slugs['browse'] 
			);
			echo $this->twig->render('all_competitions.twig.html', $data);
		}
	}
	public function new_competition() {

		if ( $_SERVER["REQUEST_METHOD"] == "POST" ){
			if ($_POST["action"] == "create-competition"){
				$this->createNewCompetition();
				$return_url = "";
				echo $this->twig->render('competition_action.twig.html', array(
					"action"		=> "created",
					"return_url"	=> get_admin_url() . 'admin.php?page=' . $this->slugs['main']
				));
			}
		} else {
			$data = array(
				"plugin_url"	=> HASHVIEWER_PLUGIN_URL,
				"admin_url"		=> get_admin_url(),
				"request_url"	=> $_SERVER['REQUEST_URI'],
				"all_comps_url"	=> get_admin_url() . 'admin.php?page=' . $this->slugs['main']
			);
			echo $this->twig->render('new_competition.twig.html', $data);
		}
	}


	public function browse() {
		
		$data = array();
		if (isset($_GET['compId'])) {
			$data['comp'] = $this->getCompetition($_GET['compId']); //TODO: sanitize input
		}

		echo $this->twig->render('browse.twig.html', $data);
	}


	/**
	 * Installation functions
	 */
	public function plugin_activate() {
		if (get_option( "ihw_db_version", "Missing" ) == "Missing") {
			$this->db_install();
			add_option( "ihw_db_version", "0.2");
		}
	}

	public function plugin_deactivate() {
		delete_option("ihw_db_version" );
	}
	public function plugin_uninstall() {
		if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    		exit();
    	$this->db_uninstall();
	}
	/**
	 * DB functions 
	 */
	public function getCompetition($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "hashviewer_competition";	
		$sql = "SELECT active, title, startTime, endTime, hashtags, winnerSubmissionId 
				FROM $table_name
				WHERE id='$id';";
		$rows = $wpdb->get_results( $sql );

		if ( isset($rows[0]) ){
			return $rows[0];
		} else {
			return NULL;
		}
	}

	public function deleteCompetition($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "hashviewer_competition";	
		$rows = $wpdb->delete( $table_name , array( 'id' => $id ));

		if ( isset($rows[0]) ){
			return $rows[0];
		} else {
			return NULL;
		}
	}

	public function getAllCompetitions() {
		global $wpdb;
		$table_name = $wpdb->prefix . "hashviewer_competition";	
		$sql = "SELECT id, active, title, startTime, endTime, hashtags, winnerSubmissionId 
				FROM $table_name;";

		$rows = $wpdb->get_results( $sql );
		return $rows;
	}

	public static function createNewCompetition() {
		// TODO: sanitize input
		$title = (isset($_POST['title'])) ? $_POST['title'] : "" ;
		$hashtags = (isset($_POST['hashtags'])) ? $_POST['hashtags'] : "" ;
		$startTime = (isset($_POST['startDay'])) ? $_POST['startDay'] : "" ;
		$endTime = (isset($_POST['endDay'])) ? $_POST['endDay'] : "" ;



		global $wpdb;
		$table_name = $wpdb->prefix . "hashviewer_competition";	

		//$startTime = strtotime($startTime);
		//$endTime = strtotime($endTime);


		$competition = array( 
			'title'		=> $title, 
			'hashtags' 	=> $hashtags, 
			'startTime'	=> $startTime . " 00:00:00",
			'endTime' 	=> $endTime . " 23:59:59",
			'active' 	=> 0
		);
		$affected_rows = $wpdb->insert( $table_name, $competition );
		return $affected_rows;
	}

	private function db_install() {
		global $wpdb;

		// come back to me if having \only\ 9 999 999 competitions is a problem
		$competition_table_name = $wpdb->prefix . "hashviewer_competition";		
		$competition_sql = "CREATE TABLE " . $competition_table_name . "(
			id 					mediumint(9) NOT NULL AUTO_INCREMENT,
			title 				VARCHAR(50) NOT NULL,
			active 				BOOL,
			startTime			DATETIME,
			endTime				DATETIME,
			hashtags			VARCHAR(255),
			winnerSubmissionId	mediumint(12),
			PRIMARY KEY (id)
		) CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		// ... and each of those competitions have over 1000 approved submission
		$submission_table_name = $wpdb->prefix . "hashviewer_submission";
		$submission_sql = "CREATE TABLE " . $submission_table_name . "(
			id 					mediumint(12) NOT NULL AUTO_INCREMENT,
			instagramUsername 	varchar(30), -- 30 is a limitation from Instagram
			instagramMediaID 	VARCHAR(255),
			instagramImage 		VARCHAR(255),
			tags 				VARCHAR(255),
			caption 			TEXT,
			approved 			Bool,
			createdAt 			DATETIME, -- The time when the image was uploaded to Instagram 
			PRIMARY KEY (id)
		) CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $submission_sql );
		dbDelta( $competition_sql );
	}

	/**
	 * Utilities
	 */
	public function filter_hashtag($tag) {

		return preg_match("\W*\w*", $tag);
	}
}
