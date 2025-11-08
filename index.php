<?php
/**
 * LocalhostIndex - Security Hardened Version
 * A beautiful localhost homepage for local development environments.
 *
 * SECURITY: This tool is for LOCAL DEVELOPMENT ONLY.
 * Set 'enable_security' => true if deploying on shared/public servers.
 */

// Configuration constants
define( 'CSRF_TOKEN_LENGTH', 32 );        // Length of CSRF token in bytes
define( 'MYSQL_TIMEOUT', 2 );             // MySQL connection timeout in seconds
define( 'RECENT_ITEMS_LIMIT', 10 );       // Number of recent items to display (extended)
define( 'RECENT_ITEMS_PREVIEW', 2 );      // Number of recent items in preview (folded)
define( 'CACHE_TTL', 60 );                // Cache time-to-live in seconds

$options = [
	/**
	 * Set the theme
	 * Available themes: bluey, sunny, forest, retro, matrix, nebula, sundown, monochrome, dark, light
	 */
	'theme' => 'bluey',

	/**
	 * Exclude files or folders
	 * use wildcard pattern. eg: ['.git*', '*.exe', '*.sh', '*.php*', '*.png']
	 */
	'exclude' => [ '.DS_Store', '.localized', '*.php*', '*.png' ],

	/**
	 * Add extra tools
	 * [label] => '[link of the tool]
	 * eg: 'phpMyAdmin' => 'http://localhost/phpMyAdmin'
	 * Note: phpinfo link will automatically include CSRF token if enabled
	 */
	'extras' => [
		'phpinfo()' => '?phpinfo=1',
		'PhpMyAdmin()' => 'phpMyAdmin'
	],

	/**
	 * Set one or more favicon file names to look for (relative to this directory).
	 * Provide a string or an array; the first existing file will be used.
	 */
	'favicon' => [ 'favicon.ico', 'favicon.png', 'favicon.svg' ],

	/**
	 * MySQL detection options
	 * - 'bin' => array|string of explicit mysql/mysqld binary paths/commands
	 * - 'connection' => [
	 *       'host' => '127.0.0.1',
	 *       'user' => 'root',
	 *       'password' => 'secret',
	 *       'database' => null,
	 *       'port' => 3306,
	 *       'socket' => null,
	 *       'timeout' => 2
	 *   ]
	 */
	'mysql' => [],

	/**
	 * Display Settings
	 * Customize how files and folders are displayed
	 */
	'display' => [
		// Show file sizes in directory listing
		'show_file_sizes' => true,
		// Default sort order: 'name', 'date', 'size'
		'default_sort' => 'name',
		// Enable caching (improves performance with many files)
		'enable_cache' => true,
	],

	/**
	 * Security Settings
	 * Enable these for shared or public-facing servers
	 */
	'security' => [
		// Enable authentication (recommended for non-localhost)
		'enable_authentication' => false,
		// Simple password protection (use strong password if enabled)
		'password' => '',  // Leave empty to disable, or set a password hash via password_hash('your_password', PASSWORD_DEFAULT)
		// Disable phpinfo for security
		'disable_phpinfo' => false,
		// Enable CSRF protection
		'enable_csrf' => true,
		// Validate all paths against traversal
		'strict_path_validation' => true,
	]
];

// Security: Session management
session_start();

// Security: Generate CSRF token
if( !isset( $_SESSION['csrf_token'] ) ){
	$_SESSION['csrf_token'] = bin2hex( random_bytes( CSRF_TOKEN_LENGTH ) );
}

// Security: Simple authentication check
if( !empty( $options['security']['enable_authentication'] ) && !empty( $options['security']['password'] ) ){
	if( !isset( $_SESSION['authenticated'] ) ){
		// Handle login form
		if( isset( $_POST['password'] ) && isset( $_POST['csrf_token'] ) ){
			if( hash_equals( $_SESSION['csrf_token'], $_POST['csrf_token'] ) ){
				if( password_verify( $_POST['password'], $options['security']['password'] ) ){
					$_SESSION['authenticated'] = true;
					header( 'Location: ' . $_SERVER['PHP_SELF'] );
					exit;
				} else {
					$login_error = 'Invalid password';
				}
			}
		}

		// Show login form
		if( !isset( $_SESSION['authenticated'] ) ){
			?>
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="utf-8">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title>LocalhostIndex - Login</title>
				<style>
					body { font-family: monospace; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #102252; color: #f4f6ff; margin: 0; }
					.login-box { background: rgba(255,255,255,0.05); padding: 40px; border-radius: 8px; max-width: 350px; }
					h1 { margin-top: 0; font-size: 18px; letter-spacing: 2px; }
					input { width: 100%; padding: 12px; margin: 10px 0; border: none; border-radius: 4px; font-family: monospace; box-sizing: border-box; }
					button { width: 100%; padding: 12px; background: #d9e009; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-family: monospace; }
					button:hover { opacity: 0.9; }
					.error { color: #ff6b6b; margin: 10px 0; font-size: 13px; }
				</style>
			</head>
			<body>
				<div class="login-box">
					<h1>LOCALHOSTINDEX</h1>
					<?php if( isset( $login_error ) ): ?>
						<div class="error"><?= htmlspecialchars( $login_error, ENT_QUOTES, 'UTF-8' ); ?></div>
					<?php endif; ?>
					<form method="post">
						<input type="password" name="password" placeholder="Password" required autofocus>
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars( $_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8' ); ?>">
						<button type="submit">Login</button>
					</form>
				</div>
			</body>
			</html>
			<?php
			exit;
		}
	}
}

// Security: Validate and sanitize paths
function validatePath( $filename, $strict = true ) {
	// Prevent path traversal
	if( strpos( $filename, '..' ) !== false || strpos( $filename, '/' ) !== false || strpos( $filename, '\\' ) !== false ){
		return false;
	}

	// Additional strict validation
	if( $strict ){
		$realPath = realpath( __DIR__ . '/' . $filename );
		if( $realPath === false || strpos( $realPath, __DIR__ ) !== 0 ){
			return false;
		}
	}

	return true;
}

// display phpinfo with CSRF protection
if( isset( $_GET['phpinfo'] ) && $_GET['phpinfo'] === '1' ){
	// Check if phpinfo is disabled
	if( !empty( $options['security']['disable_phpinfo'] ) ){
		http_response_code( 403 );
		die( 'phpinfo() has been disabled for security.' );
	}

	// CSRF protection
	if( !empty( $options['security']['enable_csrf'] ) ){
		if( !isset( $_GET['token'] ) || !hash_equals( $_SESSION['csrf_token'], $_GET['token'] ) ){
			http_response_code( 403 );
			die( 'Invalid security token.' );
		}
	}

	phpinfo();
	exit;
}

/**
 * Detect runtime version by sourcing user's shell profile
 * This ensures version managers (pyenv, nvm, rbenv, etc.) are loaded
 */
function detectRuntimeVersion( $command, $versionFlag = '--version', $pattern = '/(\d+\.\d+[\.\d]*)/' ) {
	$disableFunctions = array_map(
		'trim',
		explode( ',', strtolower( (string) ini_get( 'disable_functions' ) ) )
	);
	$shellAvailable = function_exists( 'shell_exec' ) && !in_array( 'shell_exec', $disableFunctions, true );

	if( !$shellAvailable ){
		return null;
	}

	// Find user's shell profile to source version managers
	$homeDir = getenv( 'HOME' );
	$profiles = [ '.zshrc', '.bashrc', '.bash_profile', '.profile' ];
	$sourceCmd = '';

	if( $homeDir ){
		foreach( $profiles as $profile ){
			$profilePath = $homeDir . '/' . $profile;
			if( file_exists( $profilePath ) ){
				$sourceCmd = "source $profilePath 2>/dev/null && ";
				break;
			}
		}
	}

	// Execute command with sourced environment
	$cmd = "bash -c '" . $sourceCmd . escapeshellarg( $command ) . " " . $versionFlag . "' 2>/dev/null";
	$output = trim( (string) shell_exec( $cmd ) );

	if( $output === '' ){
		return null;
	}

	if( preg_match( $pattern, $output, $matches ) ){
		return $matches[1];
	}

	return null;
}

/**
 * Auto-detect installed runtimes and their versions
 */
function detectRuntimes() {
	$runtimes = [];

	// Web Server
	if( function_exists( 'apache_get_version' ) ){
		$versionString = apache_get_version();
		if( $versionString !== false ){
			$parts = explode( '/', $versionString );
			if( isset( $parts[1] ) ){
				$runtimes['Apache'] = explode( ' ', $parts[1] )[0];
			}
		}
	}

	// PHP (always available)
	$runtimes['PHP'] = explode( '-', phpversion() )[0];

	// MySQL/MariaDB
	$mysqlVersion = detectMysqlVersion( [] );
	if( $mysqlVersion && $mysqlVersion !== 'Unknown' ){
		$runtimes['MySQL'] = $mysqlVersion;
	}

	// Python
	$pythonVersion = detectRuntimeVersion( 'python3', '--version', '/Python (\d+\.\d+\.\d+)/' );
	if( !$pythonVersion ){
		$pythonVersion = detectRuntimeVersion( 'python', '--version', '/Python (\d+\.\d+\.\d+)/' );
	}
	if( $pythonVersion ){
		$runtimes['Python'] = $pythonVersion;
	}

	// pip
	$pipVersion = detectRuntimeVersion( 'pip3', '--version', '/pip (\d+\.\d+[\.\d]*)/' );
	if( !$pipVersion ){
		$pipVersion = detectRuntimeVersion( 'pip', '--version', '/pip (\d+\.\d+[\.\d]*)/' );
	}
	if( $pipVersion ){
		$runtimes['pip'] = $pipVersion;
	}

	// Node.js
	$nodeVersion = detectRuntimeVersion( 'node', '--version', '/v?(\d+\.\d+\.\d+)/' );
	if( $nodeVersion ){
		$runtimes['Node.js'] = $nodeVersion;
	}

	// npm
	$npmVersion = detectRuntimeVersion( 'npm', '--version' );
	if( $npmVersion ){
		$runtimes['npm'] = $npmVersion;
	}

	// Ruby
	$rubyVersion = detectRuntimeVersion( 'ruby', '--version', '/ruby (\d+\.\d+\.\d+)/' );
	if( $rubyVersion ){
		$runtimes['Ruby'] = $rubyVersion;
	}

	// Go
	$goVersion = detectRuntimeVersion( 'go', 'version', '/go(\d+\.\d+[\.\d]*)/' );
	if( $goVersion ){
		$runtimes['Go'] = $goVersion;
	}

	// Composer
	$composerVersion = detectRuntimeVersion( 'composer', '--version', '/Composer version (\d+\.\d+\.\d+)/' );
	if( !$composerVersion ){
		$composerVersion = detectRuntimeVersion( 'composer', '--version', '/(\d+\.\d+\.\d+)/' );
	}
	if( $composerVersion ){
		$runtimes['Composer'] = $composerVersion;
	}

	// Git
	$gitVersion = detectRuntimeVersion( 'git', '--version', '/git version (\d+\.\d+[\.\d]*)/' );
	if( $gitVersion ){
		$runtimes['Git'] = $gitVersion;
	}

	// Docker
	$dockerVersion = detectRuntimeVersion( 'docker', '--version', '/Docker version (\d+\.\d+\.\d+)/' );
	if( $dockerVersion ){
		$runtimes['Docker'] = $dockerVersion;
	}

	return $runtimes;
}

// AJAX endpoint for extended runtime detection
if( isset( $_GET['action'] ) && $_GET['action'] === 'detect_runtimes' ){
	// Verify CSRF token if enabled
	if( !empty( $options['security']['enable_csrf'] ) ){
		if( !isset( $_GET['token'] ) || !hash_equals( $_SESSION['csrf_token'], $_GET['token'] ) ){
			http_response_code( 403 );
			die( json_encode( [ 'error' => 'Invalid token' ] ) );
		}
	}

	header( 'Content-Type: application/json' );
	echo json_encode( detectRuntimes() );
	exit;
}

// Detect basic info only (fast - no shell sourcing)
function detectBasicInfo() {
	$info = [];

	// Web Server
	if( function_exists( 'apache_get_version' ) ){
		$versionString = apache_get_version();
		if( $versionString !== false ){
			$parts = explode( '/', $versionString );
			if( isset( $parts[1] ) ){
				$info['Apache'] = explode( ' ', $parts[1] )[0];
			}
		}
	}

	// PHP (always available)
	$info['PHP'] = explode( '-', phpversion() )[0];

	// MySQL/MariaDB (relatively fast)
	$mysqlVersion = detectMysqlVersion( [] );
	if( $mysqlVersion && $mysqlVersion !== 'Unknown' ){
		$info['MySQL'] = $mysqlVersion;
	}

	return $info;
}

// Load basic info only on page load
$info = detectBasicInfo();

// match a given filename against an array of patterns
function filenameMatch( $patternArray, $filename ) {
	if( empty( $patternArray ) ){
		return false;
	}
	foreach( $patternArray as $pattern ){
		if( fnmatch( $pattern, $filename ) ){
			return true;
		}
	}
	return false;
}

/**
 * Attempt to detect the MySQL server version using CLI tools or optional connection details.
 */
function detectMysqlVersion( $mysqlOptions = [] ) {
	$mysqlOptions = is_array( $mysqlOptions ) ? $mysqlOptions : [];

	$disable_functions = array_map(
		'trim',
		explode( ',', strtolower( (string) ini_get( 'disable_functions' ) ) )
	);
	$shell_available = function_exists( 'shell_exec' ) && !in_array( 'shell_exec', $disable_functions, true );

	$binary_candidates = [];
	if( !empty( $mysqlOptions['bin'] ) ){
		$binary_candidates = array_merge(
			$binary_candidates,
			is_array( $mysqlOptions['bin'] ) ? $mysqlOptions['bin'] : [ $mysqlOptions['bin'] ]
		);
	}
	if( !empty( $mysqlOptions['binary'] ) ){
		$binary_candidates = array_merge(
			$binary_candidates,
			is_array( $mysqlOptions['binary'] ) ? $mysqlOptions['binary'] : [ $mysqlOptions['binary'] ]
		);
	}

	$default_binaries = [
		'mysql',
		'mysqld',
		'/usr/local/bin/mysql',
		'/usr/bin/mysql',
		'/opt/homebrew/bin/mysql',
		'/usr/local/mysql/bin/mysql',
		'/opt/local/bin/mysql',
		'/usr/local/mysql/bin/mysqld',
		'/usr/sbin/mysqld',
		'/usr/libexec/mysqld'
	];

	if( $shell_available ){
		foreach( [ 'command -v mysql', 'which mysql', 'command -v mysqld', 'which mysqld' ] as $probe ){
			$probe_result = trim( (string) shell_exec( $probe . ' 2>/dev/null' ) );
			if( $probe_result !== '' ){
				$binary_candidates[] = $probe_result;
			}
		}
	}

	$binary_candidates = array_values(
		array_unique(
			array_filter(
				array_merge( $binary_candidates, $default_binaries ),
				static fn( $binary ) => is_string( $binary ) && trim( $binary ) !== ''
			)
		)
	);

	if( $shell_available ){
		foreach( $binary_candidates as $binary ){
			$binary = trim( (string) $binary );
			if( $binary === '' ){
				continue;
			}
			$escaped_binary = escapeshellarg( $binary );
			$output = trim( (string) shell_exec( $escaped_binary . ' --version 2>/dev/null' ) );
			if( $output === '' ){
				continue;
			}
			if( preg_match( '/Distrib\\s+([0-9.]+)/i', $output, $matches ) ){
				return $matches[1];
			}
			if( preg_match( '/Ver\\s+([0-9.]+)/i', $output, $matches ) ){
				return $matches[1];
			}
		}
	}

	$connection = $mysqlOptions['connection'] ?? [];

	$host = $connection['host'] ?? $connection['hostname'] ?? ini_get( 'mysqli.default_host' );
	$port = $connection['port'] ?? ini_get( 'mysqli.default_port' );
	$socket = $connection['socket'] ?? ini_get( 'mysqli.default_socket' );
	$user = $connection['user'] ?? $connection['username'] ?? ini_get( 'mysqli.default_user' );
	$password = $connection['password'] ?? $connection['pass'] ?? $connection['pw'] ?? ini_get( 'mysqli.default_pw' );
	$database = $connection['database'] ?? $connection['dbname'] ?? null;
	$timeout = isset( $connection['timeout'] ) ? (int) $connection['timeout'] : MYSQL_TIMEOUT;

	$env_user = getenv( 'DB_USER' )
		?: getenv( 'MYSQL_USER' )
		?: getenv( 'JAWSDB_USERNAME' )
		?: getenv( 'JAWSDB_USER' )
		?: getenv( 'CLEARDB_USERNAME' );
	$env_password = getenv( 'DB_PASSWORD' )
		?: getenv( 'MYSQL_PASSWORD' )
		?: getenv( 'JAWSDB_PASSWORD' )
		?: getenv( 'CLEARDB_PASSWORD' );
	$env_host = getenv( 'DB_HOST' )
		?: getenv( 'MYSQL_HOST' )
		?: getenv( 'JAWSDB_HOST' )
		?: getenv( 'CLEARDB_HOST' );
	$env_db = getenv( 'DB_NAME' )
		?: getenv( 'MYSQL_DATABASE' )
		?: getenv( 'MYSQL_DB' )
		?: getenv( 'JAWSDB_DATABASE' )
		?: getenv( 'CLEARDB_DATABASE' );
	$env_port = getenv( 'DB_PORT' )
		?: getenv( 'MYSQL_PORT' )
		?: getenv( 'JAWSDB_PORT' )
		?: getenv( 'CLEARDB_PORT' );

	if( empty( $user ) && !empty( $env_user ) ){
		$user = $env_user;
	}
	if( $password === null && $env_password !== false ){
		$password = $env_password;
	}
	if( empty( $host ) && !empty( $env_host ) ){
		$host = $env_host;
	}
	if( empty( $database ) && !empty( $env_db ) ){
		$database = $env_db;
	}
	if( empty( $port ) && !empty( $env_port ) ){
		$port = $env_port;
	}

	$database_url = getenv( 'DATABASE_URL' ) ?: getenv( 'JAWSDB_URL' ) ?: getenv( 'CLEARDB_DATABASE_URL' );
	if( !empty( $database_url ) ){
		$url_parts = parse_url( $database_url );
		if( is_array( $url_parts ) ){
			if( empty( $user ) && !empty( $url_parts['user'] ) ){
				$user = $url_parts['user'];
			}
			if( $password === null && isset( $url_parts['pass'] ) ){
				$password = $url_parts['pass'];
			}
			if( empty( $host ) && !empty( $url_parts['host'] ) ){
				$host = $url_parts['host'];
			}
			if( empty( $port ) && !empty( $url_parts['port'] ) ){
				$port = $url_parts['port'];
			}
			if( empty( $database ) && !empty( $url_parts['path'] ) ){
				$database = ltrim( $url_parts['path'], '/' );
			}
		}
	}

	$host = $host !== '' ? $host : null;
	$port = !empty( $port ) ? (int) $port : null;
	$socket = !empty( $socket ) ? $socket : null;
	$password = $password !== null ? (string) $password : '';

	if( !empty( $user ) || $socket !== null ){
		$mysqli = mysqli_init();
		if( $mysqli ){
			if( defined( 'MYSQLI_OPT_CONNECT_TIMEOUT' ) ){
				mysqli_options( $mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout );
			}
			// Suppress connection errors as this is detection, not critical functionality
			$connected = @mysqli_real_connect(
				$mysqli,
				$host,
				$user,
				$password,
				$database,
				$port,
				$socket
			);
			if( $connected ){
				$server_info = mysqli_get_server_info( $mysqli );
				mysqli_close( $mysqli );
				if( !empty( $server_info ) ){
					if( preg_match( '/([0-9]+\\.[0-9]+\\.[0-9]+)/', $server_info, $matches ) ){
						return $matches[1];
					}
					return $server_info;
				}
			}
		}
	}

	$client_info = mysqli_get_client_info();
	$client_info = preg_replace( '/^mysqlnd\\s*/i', '', $client_info );
	return $client_info !== '' ? $client_info : 'Unknown';
}

/**
 * Convert bytes into a human-friendly string.
 */
function humanFileSize( $bytes, $decimals = 1 ) {
	if( !is_numeric( $bytes ) || $bytes <= 0 ){
		return '0 B';
	}
	$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
	$factor = floor( ( strlen( (string) $bytes ) - 1 ) / 3 );
	$factor = min( $factor, count( $units ) - 1 );
	$value = $bytes / pow( 1024, $factor );
	return number_format( $value, $decimals ) . ' ' . $units[ $factor ];
}

/**
 * Return a compact relative time string for a timestamp.
 */
function formatRelativeTime( $timestamp ) {
	if( !is_numeric( $timestamp ) || $timestamp <= 0 ){
		return 'unknown';
	}
	$diff = time() - $timestamp;
	if( $diff < 0 ){
		return 'just now';
	}
	$units = [
		[ 31536000, 'yr' ],
		[ 2592000, 'mo' ],
		[ 604800, 'wk' ],
		[ 86400, 'd' ],
		[ 3600, 'h' ],
		[ 60, 'm' ],
	];
	foreach( $units as [ $seconds, $label ] ){
		if( $diff >= $seconds ){
			$value = floor( $diff / $seconds );
			return $value . $label . ' ago';
		}
	}
	return max( 1, $diff ) . 's ago';
}

/**
 * Get cached directory listing or scan directory
 */
function getDirectoryListing( $options ) {
	$cacheEnabled = $options['display']['enable_cache'] ?? true;
	$cacheKey = 'directory_listing_cache';
	$cacheFile = sys_get_temp_dir() . '/' . md5( __DIR__ ) . '_localhostindex.cache';

	// Check cache
	if( $cacheEnabled && file_exists( $cacheFile ) ){
		$cacheData = @file_get_contents( $cacheFile );
		if( $cacheData !== false ){
			$cache = @unserialize( $cacheData );
			if( is_array( $cache ) && isset( $cache['timestamp'], $cache['data'] ) ){
				if( ( time() - $cache['timestamp'] ) < CACHE_TTL ){
					return $cache['data'];
				}
			}
		}
	}

	// Scan directory
	$directoryList = [];
	if( $handle = opendir( './' ) ){
		while( false !== ( $item = readdir( $handle ) ) ){
			if( $item == '..' || $item == '.' || filenameMatch( $options['exclude'], $item ) ){
				continue;
			}

			// Security: Validate path against traversal attacks
			$strictValidation = $options['security']['strict_path_validation'] ?? true;
			if( !validatePath( $item, $strictValidation ) ){
				continue;
			}

			$path = __DIR__ . '/' . $item;
			$isDir = is_dir( $path );
			$itemType = $isDir ? 'dir' : 'file';

			$itemData = [
				'name' => $item,
				'type' => $itemType,
				'mtime' => filemtime( $path ),
			];

			// Add file size for files
			if( !$isDir ){
				$itemData['size'] = filesize( $path );
			}

			$directoryList[] = $itemData;
		}
		closedir( $handle );
	}

	// Cache the results
	if( $cacheEnabled ){
		$cacheData = serialize([
			'timestamp' => time(),
			'data' => $directoryList
		]);
		@file_put_contents( $cacheFile, $cacheData );
	}

	return $directoryList;
}

// read all items in the ./ dir
$directoryList = getDirectoryListing( $options );

// Sort directory based on user preference or default
$defaultSort = $options['display']['default_sort'] ?? 'name';
$sortOrder = $_GET['sort'] ?? $defaultSort;

usort( $directoryList, static function ( $a, $b ) use ( $sortOrder ) {
	// Directories always first
	if( $a['type'] !== $b['type'] ){
		return $a['type'] === 'dir' ? -1 : 1;
	}

	// Then sort by specified field
	switch( $sortOrder ){
		case 'date':
			return $b['mtime'] <=> $a['mtime'];
		case 'size':
			$aSize = $a['size'] ?? 0;
			$bSize = $b['size'] ?? 0;
			return $bSize <=> $aSize;
		case 'name':
		default:
			return strcasecmp( $a['name'], $b['name'] );
	}
});

$projectCount = 0;
$fileCount = 0;
$latestItem = null;
$latestMtime = 0;
$recentItems = [];

foreach( $directoryList as $item ){
	$isDir = ( $item['type'] === 'dir' );
	$projectCount += $isDir ? 1 : 0;
	$fileCount += $isDir ? 0 : 1;

	$mtime = $item['mtime'] ?? 0;
	if( $mtime > 0 ){
		$recentItems[] = $item;
		if( $mtime > $latestMtime ){
			$latestMtime = $mtime;
			$latestItem = $item;
		}
	}
}

// Stats: Preview (folded) and Extended (expanded)
// Preview: Projects, Disk free, OS
// Expanded Section 1 (Project Stats): Projects, Files, Last update
// Expanded Section 2 (System Stats): Disk free, Total disk, Memory, CPU, Uptime, OS
// Expanded Section 3 (PHP Stats): PHP memory limit, PHP max upload, OPcache status
// Expanded Section 4 (Server Stats): Apache connections, Active ports, Running processes
$statsPreview = [];
$statsProjectExpanded = [];
$statsSystemExpanded = [];
$statsPHPExpanded = [];
$statsServerExpanded = [];

// Get disk info first (used in both preview and system expanded)
$diskTotal = disk_total_space( __DIR__ );
$diskFree = disk_free_space( __DIR__ );

// Preview stats (only visible when folded)
if( $projectCount > 0 ){
	$statsPreview['Projects'] = number_format( $projectCount );
}
if( $diskTotal !== false && $diskTotal > 0 && $diskFree !== false ){
	$freePercent = round( ( $diskFree / $diskTotal ) * 100 );
	$statsPreview['Disk free'] = humanFileSize( $diskFree ) . ' (' . $freePercent . '%)';
}

// Project Stats (expanded section 1): Projects, Files, Last update
if( $projectCount > 0 ){
	$statsProjectExpanded['Projects'] = number_format( $projectCount );
}
if( $fileCount > 0 ){
	$statsProjectExpanded['Files'] = number_format( $fileCount );
}
if( $latestItem ){
	$statsProjectExpanded['Last update'] = formatRelativeTime( $latestMtime ) . ' · ' . $latestItem['name'];
}

// System Stats (expanded section 2): Disk free, Total disk, Memory, CPU, Uptime, OS
if( $diskTotal !== false && $diskTotal > 0 && $diskFree !== false ){
	$freePercent = round( ( $diskFree / $diskTotal ) * 100 );
	$statsSystemExpanded['Disk free'] = humanFileSize( $diskFree ) . ' (' . $freePercent . '%)';
}
if( $diskTotal !== false && $diskTotal > 0 ){
	$statsSystemExpanded['Total disk'] = humanFileSize( $diskTotal );
}

// Memory
if( PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux' ){
	$memInfo = @shell_exec( PHP_OS_FAMILY === 'Darwin' ? 'sysctl hw.memsize' : 'free -b | grep Mem' );
	if( $memInfo ){
		if( PHP_OS_FAMILY === 'Darwin' ){
			if( preg_match( '/hw\.memsize:\s+(\d+)/', $memInfo, $matches ) ){
				$statsSystemExpanded['Memory'] = humanFileSize( (int)$matches[1] );
			}
		} else {
			if( preg_match( '/Mem:\s+(\d+)/', $memInfo, $matches ) ){
				$statsSystemExpanded['Memory'] = humanFileSize( (int)$matches[1] );
			}
		}
	}
}

// CPU Info
if( PHP_OS_FAMILY === 'Darwin' ){
	$cpuModel = @shell_exec( 'sysctl -n machdep.cpu.brand_string' );
	$cpuCores = @shell_exec( 'sysctl -n hw.ncpu' );
	if( $cpuModel && $cpuCores ){
		$cpuModel = trim( $cpuModel );
		$cpuCores = trim( $cpuCores );
		$statsSystemExpanded['CPU'] = $cpuCores . ' cores';
	}
} elseif( PHP_OS_FAMILY === 'Linux' ){
	$cpuInfo = @shell_exec( 'nproc' );
	if( $cpuInfo ){
		$statsSystemExpanded['CPU'] = trim( $cpuInfo ) . ' cores';
	}
}

// Uptime
if( PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux' ){
	$uptime = @shell_exec( 'uptime' );
	if( $uptime && preg_match( '/up\s+(.+?),\s+\d+\s+user/', $uptime, $matches ) ){
		$statsSystemExpanded['Uptime'] = trim( $matches[1] );
	}
}

// OS Version (in both preview and system expanded)
if( PHP_OS_FAMILY === 'Darwin' ){
	$osVersion = @shell_exec( 'sw_vers -productVersion' );
	if( $osVersion ){
		$version = trim( $osVersion );
		$versionParts = explode( '.', $version );
		$majorVersion = (int)$versionParts[0];

		// macOS version names
		$versionNames = [
			15 => 'Sequoia',
			14 => 'Sonoma',
			13 => 'Ventura',
			12 => 'Monterey',
			11 => 'Big Sur',
			10 => 'Catalina/Mojave/High Sierra'
		];

		$versionName = $versionNames[$majorVersion] ?? 'macOS';
		$osDisplayValue = "macOS {$version} ({$versionName})";
		$statsPreview['OS'] = $osDisplayValue;
		$statsSystemExpanded['OS'] = $osDisplayValue;
	}
} elseif( PHP_OS_FAMILY === 'Linux' ){
	$osVersion = @shell_exec( "lsb_release -ds 2>/dev/null || cat /etc/*release 2>/dev/null | grep PRETTY_NAME | cut -d= -f2 | tr -d '\"'" );
	if( $osVersion ){
		$osValue = trim( explode( "\n", $osVersion )[0] );
		$statsPreview['OS'] = $osValue;
		$statsSystemExpanded['OS'] = $osValue;
	}
}

// PHP Stats
$statsPHPExpanded['PHP memory limit'] = ini_get( 'memory_limit' );
$statsPHPExpanded['PHP max upload'] = ini_get( 'upload_max_filesize' );

// OPcache status
if( function_exists( 'opcache_get_status' ) ){
	$opcache = @opcache_get_status( false );
	if( $opcache && isset( $opcache['opcache_enabled'] ) ){
		if( $opcache['opcache_enabled'] ){
			$hits = $opcache['opcache_statistics']['hits'] ?? 0;
			$misses = $opcache['opcache_statistics']['misses'] ?? 0;
			$total = $hits + $misses;
			$hitRate = $total > 0 ? round( ( $hits / $total ) * 100, 1 ) : 0;
			$statsPHPExpanded['OPcache'] = "Enabled ({$hitRate}% hit rate)";
		} else {
			$statsPHPExpanded['OPcache'] = 'Disabled';
		}
	}
} else {
	$statsPHPExpanded['OPcache'] = 'Not available';
}

// Server Stats
// Apache connections
if( function_exists( 'apache_get_modules' ) ){
	$apacheStatus = @shell_exec( "netstat -an | grep -E ':(80|443)' | grep ESTABLISHED | wc -l" );
	if( $apacheStatus !== null ){
		$statsServerExpanded['Apache connections'] = trim( $apacheStatus );
	}
}

// Active ports
$activePorts = @shell_exec( "netstat -an | grep LISTEN | awk '{print \$4}' | grep -oE '[0-9]+\$' | sort -u | wc -l" );
if( $activePorts !== null ){
	$statsServerExpanded['Active ports'] = trim( $activePorts );
}

// Running processes
if( PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux' ){
	$apacheProc = @shell_exec( "ps aux | grep -E 'httpd|apache2' | grep -v grep | wc -l" );
	$mysqlProc = @shell_exec( "ps aux | grep -E 'mysqld' | grep -v grep | wc -l" );
	$nodeProc = @shell_exec( "ps aux | grep -E 'node' | grep -v grep | wc -l" );

	$procCounts = [];
	if( $apacheProc !== null && (int)trim( $apacheProc ) > 0 ){
		$procCounts[] = 'Apache: ' . trim( $apacheProc );
	}
	if( $mysqlProc !== null && (int)trim( $mysqlProc ) > 0 ){
		$procCounts[] = 'MySQL: ' . trim( $mysqlProc );
	}
	if( $nodeProc !== null && (int)trim( $nodeProc ) > 0 ){
		$procCounts[] = 'Node: ' . trim( $nodeProc );
	}

	if( !empty( $procCounts ) ){
		$statsServerExpanded['Running processes'] = implode( ', ', $procCounts );
	}
}

usort( $recentItems, static fn( $a, $b ) => $b['mtime'] <=> $a['mtime'] );
$recentItems = array_slice( $recentItems, 0, RECENT_ITEMS_LIMIT );
$recentItems = array_map(
	static function( $item ) {
		$mtime = $item['mtime'];
		return [
			'name' => $item['name'],
			'type' => $item['type'],
			'mtime' => $mtime,
			'relative' => formatRelativeTime( $mtime ),
			'absolute' => date( 'M j, Y H:i', $mtime ),
		];
	},
	$recentItems
);

// Split recent items into preview and extended
$recentPreview = array_slice( $recentItems, 0, RECENT_ITEMS_PREVIEW );
$recentExtended = array_slice( $recentItems, RECENT_ITEMS_PREVIEW );

$themes = [
	'light' => [
		'label' => 'Light',
		'background' => '#f8f9fb',
		'accent' => '#0066ff',
		'secondary' => '#2f2f2f',
		'text' => '#11131d',
		'title' => '#05070c',
		'muted' => '#5c6375',
		'input_bg' => 'rgba(0, 0, 0, 0.05)',
		'input_focus_bg' => 'rgba(0, 0, 0, 0.08)',
		'input_text' => '#1f2332',
	],
	'bluey' => [
		'label' => 'Bluey',
		'background' => '#102252',
		'accent' => '#d9e009',
		'secondary' => '#efffc1',
		'text' => '#f4f6ff',
		'title' => 'rgba(255, 255, 255, 0.85)',
		'muted' => 'rgba(255, 255, 255, 0.65)',
		'input_bg' => 'rgba(255, 255, 255, 0.06)',
		'input_focus_bg' => 'rgba(204, 204, 204, 0.05)',
		'input_text' => '#e3e7ff',
	],
	'sunny' => [
		'label' => 'Sunny',
		'background' => '#A94907',
		'accent' => '#FFB703',
		'secondary' => '#FFE5A8',
		'text' => '#fff4e6',
		'title' => 'rgba(255, 255, 255, 0.88)',
		'muted' => 'rgba(255, 255, 255, 0.7)',
		'input_bg' => 'rgba(255, 255, 255, 0.08)',
		'input_focus_bg' => 'rgba(255, 255, 255, 0.15)',
		'input_text' => '#fff7ed',
	],
	'forest' => [
		'label' => 'Forest',
		'background' => '#0D2F26',
		'accent' => '#2EC4B6',
		'secondary' => '#C6F7E2',
		'text' => '#e6fff2',
		'title' => 'rgba(255, 255, 255, 0.85)',
		'muted' => 'rgba(255, 255, 255, 0.7)',
		'input_bg' => 'rgba(255, 255, 255, 0.06)',
		'input_focus_bg' => 'rgba(204, 204, 204, 0.05)',
		'input_text' => '#e9fff7',
	],
	'retro' => [
		'label' => 'Retro',
		'background' => '#041b05',
		'accent' => '#39ff14',
		'secondary' => '#9dff7a',
		'text' => '#d8ffd9',
		'title' => 'rgba(217, 255, 223, 0.9)',
		'muted' => 'rgba(204, 255, 214, 0.7)',
		'input_bg' => 'rgba(255, 255, 255, 0.05)',
		'input_focus_bg' => 'rgba(255, 255, 255, 0.1)',
		'input_text' => '#e1ffe3',
	],
	'matrix' => [
		'label' => 'Matrix',
		'background' => '#020d06',
		'accent' => '#00ff9c',
		'secondary' => '#b1ffc9',
		'text' => '#d6ffe4',
		'title' => 'rgba(214, 255, 228, 0.9)',
		'muted' => 'rgba(190, 255, 219, 0.7)',
		'input_bg' => 'rgba(255, 255, 255, 0.05)',
		'input_focus_bg' => 'rgba(255, 255, 255, 0.1)',
		'input_text' => '#defeea',
	],
	'nebula' => [
		'label' => 'Nebula',
		'background' => '#0d0221',
		'accent' => '#ff5c8d',
		'secondary' => '#5defff',
		'text' => '#f6e8ff',
		'title' => 'rgba(245, 232, 255, 0.88)',
		'muted' => 'rgba(230, 210, 255, 0.7)',
		'input_bg' => 'rgba(255, 255, 255, 0.06)',
		'input_focus_bg' => 'rgba(204, 204, 204, 0.05)',
		'input_text' => '#f8f0ff',
	],
	'sundown' => [
		'label' => 'Sundown',
		'background' => '#2b0b0e',
		'accent' => '#ff7f50',
		'secondary' => '#ffd9a0',
		'text' => '#ffe6db',
		'title' => 'rgba(255, 228, 213, 0.88)',
		'muted' => 'rgba(255, 212, 192, 0.72)',
		'input_bg' => 'rgba(255, 255, 255, 0.08)',
		'input_focus_bg' => 'rgba(255, 255, 255, 0.14)',
		'input_text' => '#ffece2',
	],
	'monochrome' => [
		'label' => 'Mono',
		'background' => '#121212',
		'accent' => '#f5c400',
		'secondary' => '#d1d1d1',
		'text' => '#f2f2f2',
		'title' => '#ffffff',
		'muted' => 'rgba(240, 240, 240, 0.65)',
		'input_bg' => 'rgba(255, 255, 255, 0.05)',
		'input_focus_bg' => 'rgba(255, 255, 255, 0.1)',
		'input_text' => '#f6f6f6',
	],
	'dark' => [
		'label' => 'Dark',
		'background' => '#050608',
		'accent' => '#3a9bff',
		'secondary' => '#c7d6ff',
		'text' => '#eef2ff',
		'title' => '#ffffff',
		'muted' => 'rgba(238, 242, 255, 0.62)',
		'input_bg' => 'rgba(255, 255, 255, 0.05)',
		'input_focus_bg' => 'rgba(255, 255, 255, 0.12)',
		'input_text' => '#f0f3ff',
	],
];

$themeOrder = array_keys( $themes );
$defaultThemeKey = array_key_exists( $options['theme'], $themes ) ? $options['theme'] : $themeOrder[0];
$theme = $themes[ $defaultThemeKey ];
$defaultThemeIndex = array_search( $defaultThemeKey, $themeOrder, true );

$themesForClient = [];
foreach( $themeOrder as $key ){
	$themesForClient[ $key ] = [
		'label' => $themes[ $key ]['label'],
		'background' => $themes[ $key ]['background'],
		'accent' => $themes[ $key ]['accent'],
		'secondary' => $themes[ $key ]['secondary'],
		'text' => $themes[ $key ]['text'] ?? '#ffffff',
		'title' => $themes[ $key ]['title'] ?? 'rgba(255, 255, 255, 0.8)',
		'muted' => $themes[ $key ]['muted'] ?? 'rgba(255, 255, 255, 0.65)',
		'inputBg' => $themes[ $key ]['input_bg'] ?? 'rgba(255, 255, 255, 0.06)',
		'inputFocusBg' => $themes[ $key ]['input_focus_bg'] ?? 'rgba(204, 204, 204, 0.05)',
		'inputText' => $themes[ $key ]['input_text'] ?? '#dfdfdf',
	];
}

$faviconCandidates = $options['favicon'] ?? [];
if( is_string( $faviconCandidates ) ){
	$faviconCandidates = [ $faviconCandidates ];
}
$faviconCandidates = array_filter(
	array_map( static fn( $candidate ) => ltrim( trim( (string) $candidate ), '/' ), $faviconCandidates )
);

$faviconHref = '';
foreach( $faviconCandidates as $candidate ){
	$faviconPath = __DIR__ . '/' . $candidate;
	if( file_exists( $faviconPath ) ){
		$faviconHref = $candidate;
		break;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php if( !empty( $faviconHref ) ): ?>
        <link rel="icon" href="<?= htmlspecialchars( $faviconHref, ENT_QUOTES, 'UTF-8' ); ?>">
    <?php endif; ?>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        :root {
            --color-bkg: <?=$theme['background']?>;
            --color-accent: <?=$theme['accent']?>;
            --color-secondary: <?=$theme['secondary']?>;
            --color-text: <?=$theme['text'] ?? '#ffffff'?>;
            --color-title: <?=$theme['title'] ?? 'rgba(255, 255, 255, 0.85)'?>;
            --color-muted: <?=$theme['muted'] ?? 'rgba(255, 255, 255, 0.65)'?>;
            --input-bg: <?=$theme['input_bg'] ?? 'rgba(255, 255, 255, 0.06)'?>;
            --input-focus-bg: <?=$theme['input_focus_bg'] ?? 'rgba(204, 204, 204, 0.05)'?>;
            --input-text: <?=$theme['input_text'] ?? '#dfdfdf'?>;

            /* Layout Variables */
            --spacing-unit: 8px;
            --card-padding: 12px;
            --card-radius: 6px;
            --card-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            --card-bg: rgba(255, 255, 255, 0.02);
            --container-max-width: 1400px;
            --grid-gap: 32px;
            --body-padding-top: 24px;
            --body-padding-bottom: 24px;
            --body-padding-x: clamp(16px, 3vw, 32px);
        }

        html {
            height: 100%;
        }

        body {
            font-family: monospace;
            background: var(--color-bkg);
            color: var(--color-text);
            margin: 0;
            padding: var(--body-padding-top) var(--body-padding-x) var(--body-padding-bottom);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            overflow-x: hidden;
        }

        h2 {
            font-size: 16px;
            color: var(--color-title);
            font-weight: normal;
            margin-top: 0;
            margin-bottom: 16px;
            letter-spacing: 3px;
        }

        .theme {
            margin-bottom: calc(var(--spacing-unit) * 2);
            padding: var(--card-padding);
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }

        .theme-toggle {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .theme-toggle button {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: none;
            padding: 0;
            background: rgba(255, 255, 255, 0.18);
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }

        .theme-toggle button::before {
            content: '';
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: var(--theme-accent, rgba(255, 255, 255, 0.45));
            opacity: 0.55;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .theme-toggle button::after {
            content: '';
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .theme-toggle button[aria-pressed="true"] {
            background: var(--color-accent);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
            transform: translateY(-1px);
        }

        .theme-toggle button[aria-pressed="true"]::before {
            opacity: 1;
            transform: scale(0.92);
        }

        .theme-toggle button[aria-pressed="true"]::after {
            opacity: 0.25;
        }

        .theme-toggle button:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.8);
            outline-offset: 2px;
        }

        main {
            display: grid;
            grid-template-columns: minmax(0, 2.4fr) minmax(280px, 1fr);
            gap: var(--grid-gap);
            width: 100%;
            max-width: var(--container-max-width);
            margin: 0 auto;
            padding: 0;
            color: var(--color-text);
        }

        main > .projects {
            min-width: 0;
            display: flex;
            flex-direction: column;
            height: calc(100vh - var(--body-padding-top) - var(--body-padding-bottom));
        }

        main > .projects .projects-header {
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        main > .projects h2 {
            flex-shrink: 0;
            margin-bottom: 0;
        }

        .sort-buttons {
            display: flex;
            gap: 6px;
        }

        .sort-btn {
            background: var(--input-bg);
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            color: var(--color-muted);
            cursor: pointer;
            font-family: monospace;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .sort-btn:hover {
            background: var(--input-focus-bg);
            color: var(--color-text);
        }

        .sort-btn.active {
            background: var(--color-accent);
            color: var(--color-bkg);
            font-weight: bold;
        }

        .sort-btn:focus-visible {
            outline: 2px solid var(--color-accent);
            outline-offset: 2px;
        }

        main > .projects .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        main > aside {
            min-width: 280px;
        }


        .info,
        .stats,
        .actions,
        .recent,
        .tools {
            margin-bottom: calc(var(--spacing-unit) * 2);
            padding: var(--card-padding);
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: calc(var(--spacing-unit) * 2);
        }

        .section-header h2 {
            margin-bottom: 0;
        }

        .toggle-btn {
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            color: var(--color-accent);
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
        }

        .toggle-btn:hover {
            background: var(--input-focus-bg);
            border-color: var(--color-accent);
            transform: scale(1.1);
        }

        .extended-section {
            margin-top: calc(var(--spacing-unit) * 2);
            padding-top: calc(var(--spacing-unit) * 2);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info .table > div,
        .stats .table > div,
        .system-stats .table > div {
            margin: 7px 0;
            color: var(--color-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .info .table > div > span,
        .stats .table > div > span {
        }

        .info .table > div > span:first-child,
        .stats .table > div > span:first-child,
        .system-stats .table > div > span:first-child {
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 11px;
            color: var(--color-muted);
        }

        .info .table > div > span:last-child,
        .stats .table > div > span:last-child,
        .system-stats .table > div > span:last-child {
            flex-shrink: 0;
            font-weight: bold;
            padding: 0 3px;
            border-radius: 3px;
            text-align: right;
        }

        .stats .table > div > span:last-child {
            color: var(--color-secondary);
            font-weight: 600;
        }

        .system-stats .table > div > span:last-child {
            color: var(--color-accent);
            font-weight: 600;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .action-btn {
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: var(--color-text);
            padding: 10px 12px;
            font-family: monospace;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .action-btn span {
            font-size: 14px;
        }

        .action-btn:hover {
            background: var(--input-focus-bg);
            border-color: var(--color-accent);
            transform: translateY(-1px);
        }

        .action-btn:active {
            transform: translateY(0);
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .expand-btn {
            width: 100%;
            margin-top: 12px;
            padding: 8px 12px;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: var(--color-accent);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .expand-btn:hover {
            background: var(--input-focus-bg);
            border-color: var(--color-accent);
            transform: translateY(-1px);
        }

        .expand-btn:active {
            transform: translateY(0);
        }

        .expand-btn.loading {
            cursor: wait;
            opacity: 0.6;
        }

        .expand-btn.expanded {
            display: none;
        }

        #extended-info .table {
            margin-top: 0;
            padding-top: 0;
        }

        #extended-info .table > div {
            margin: 7px 0;
            color: var(--color-muted);
            text-align: left;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        #extended-info .table > div > span:first-child {
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 11px;
            color: var(--color-muted);
        }

        #extended-info .table > div > span:last-child {
            flex-shrink: 0;
            font-weight: bold;
            padding: 0 3px;
            border-radius: 3px;
            text-align: right;
        }

        .recent ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .recent ul li {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 13px;
            padding: 6px 0;
            color: var(--color-muted);
        }

        .recent ul li .name {
            color: var(--color-secondary);
            margin-right: 16px;
            word-break: break-word;
            text-decoration: none;
            border-radius: 3px;
            padding: 2px 4px;
            transition: all 0.2s ease;
        }

        .recent ul li .name:hover,
        .recent ul li .name:focus {
            background-color: var(--color-secondary);
            color: var(--color-bkg);
            outline: none;
        }

        .recent ul li.dir .name {
            color: var(--color-accent);
            font-weight: bold;
        }

        .recent ul li.dir .name:hover,
        .recent ul li.dir .name:focus {
            background-color: var(--color-accent);
            color: var(--color-bkg);
            outline: none;
        }

        .recent ul li small {
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--color-muted);
            white-space: nowrap;
            margin-left: auto;
        }

        .projects {
            padding: var(--card-padding);
            max-width: 100%;
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }

        .projects .content {
            position: relative;
            height: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .projects .content input {
            background: var(--input-bg);
            border: none;
            padding: 9px 15px;
            margin-bottom: 10px;
            width: calc(100% - 30px);
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
            color: var(--input-text);
            height: 15px;
            transition: all 0.3s ease;
        }

        .projects .content input::placeholder {
            color: var(--color-muted);
        }

        .projects .content input:focus {
            outline: 2px solid var(--color-accent);
            outline-offset: 2px;
            background: var(--input-focus-bg);
        }

        .projects ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            flex: 1;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .projects ul::-webkit-scrollbar {
            display: none;
        }

        .projects ul li {
            margin: 8px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .projects ul li.hidden {
            display: none;
        }

        .projects ul li a {
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            padding: 2px 4px;
            flex: 1;
            min-width: 0;
            transition: all 0.2s ease;
        }

        .projects ul li .file-size {
            font-size: 11px;
            color: var(--color-muted);
            white-space: nowrap;
            font-weight: normal;
        }

        .projects ul li.dir a {
            color: var(--color-accent);
            font-weight: bold;
        }

        .projects ul li.dir a:hover,
        .projects ul li.dir a:focus {
            background-color: var(--color-accent);
            color: var(--color-bkg);
            outline: none;
        }

        .projects ul li.file a {
            color: var(--color-secondary);
        }

        .projects ul li.file a:hover,
        .projects ul li.file a:focus {
            background-color: var(--color-secondary);
            color: var(--color-bkg);
            outline: none;
        }

        .tools ul {
            list-style-type: none;
            padding: 0;
            margin-left: 4px;
        }

        .tools ul li::before {
            content: '↪ ';
        }

        .tools ul li {
            margin: 6px 0;
        }

        .tools ul li a {
            color: var(--color-accent);
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
            padding: 2px 4px;
            transition: all 0.2s ease;
        }

        .tools ul li a:hover {
            background-color: var(--color-accent);
            color: var(--color-bkg);
        }

        @media screen and (max-width: 900px) {
            main {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 500px) {
            :root {
                --body-padding-x: 12px;
                --grid-gap: 24px;
            }

            .theme-toggle {
                gap: 8px;
            }

            .theme-toggle button {
                width: 16px;
                height: 16px;
            }

            main > aside {
                order: 2;
                min-width: 0;
            }

            .projects ul li {
                margin: 12px 0;
            }

            .projects ul li a {
                padding: 12px 8px;
                font-size: 14px;
                min-height: 44px;
                display: flex;
                align-items: center;
            }

            .recent ul li {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
                padding: 8px 0;
            }

            .recent ul li .name {
                padding: 8px 12px;
                min-height: 44px;
                display: flex;
                align-items: center;
            }

            .recent ul li small {
                margin-left: 0;
            }

            .tools ul li a {
                padding: 12px 8px;
                min-height: 44px;
                display: flex;
                align-items: center;
                font-size: 15px;
            }

            .sort-btn {
                padding: 12px 16px;
                font-size: 14px;
                min-height: 44px;
            }
        }
    </style>
</head>

<body>
<main>
    <div class="projects">
        <div class="projects-header">
            <h2>projects</h2>
            <div class="sort-buttons">
                <button class="sort-btn <?= $sortOrder === 'name' ? 'active' : '' ?>" data-sort="name" title="Sort by name">A-Z</button>
                <button class="sort-btn <?= $sortOrder === 'date' ? 'active' : '' ?>" data-sort="date" title="Sort by date">📅</button>
                <button class="sort-btn <?= $sortOrder === 'size' ? 'active' : '' ?>" data-sort="size" title="Sort by size">💾</button>
            </div>
        </div>
        <div class="content">
            <input type="text" placeholder="Search" class="search">
            <ul>
				<?php foreach( $directoryList as $item ):
					$showSize = $options['display']['show_file_sizes'] ?? true;
					$size = isset( $item['size'] ) && $showSize ? humanFileSize( $item['size'] ) : '';
				?>
                    <li class="<?= $item['type'] ?>" data-name="<?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?>">
                        <a target="_blank" href="<?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?>">
							<?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?>
                        </a>
						<?php if( $size ): ?>
                            <span class="file-size"><?= $size ?></span>
						<?php endif; ?>
                    </li>
				<?php endforeach; ?>
            </ul>
        </div>
    </div>
    <aside>
        <div class="theme">
            <h2>theme</h2>
            <div class="theme-toggle" role="group" aria-label="Theme selector">
                <?php foreach( $themeOrder as $idx => $key ): ?>
                <button
                    type="button"
                    data-theme-key="<?= htmlspecialchars( $key, ENT_QUOTES, 'UTF-8' ); ?>"
                    aria-pressed="<?= $idx === $defaultThemeIndex ? 'true' : 'false'; ?>"
                    aria-label="<?= htmlspecialchars( $themes[ $key ]['label'], ENT_QUOTES, 'UTF-8' ); ?>"
                    style="--theme-accent: <?= htmlspecialchars( $themes[ $key ]['accent'], ENT_QUOTES, 'UTF-8' ); ?>;"
                ></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="info">
            <div class="section-header">
                <h2>info</h2>
                <button class="toggle-btn" data-target="info-extended" data-ajax="runtimes" aria-label="Expand runtimes">+</button>
            </div>
            <div class="table" id="basic-info">
				<?php foreach( $info as $label => $value ): ?>
                    <div class="<?= $label ?>">
                        <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                        <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                    </div>
				<?php endforeach; ?>
            </div>
            <div id="info-extended" class="extended-section" style="display: none;">
                <div id="extended-info"></div>
            </div>
        </div>
		<?php if( !empty( $options['extras'] ) ): ?>
            <div class="tools">
                <h2>extras</h2>
                <ul>
					<?php foreach( $options['extras'] as $label => $link ):
						// Add CSRF token to phpinfo links if CSRF is enabled
						$final_link = $link;
						if( !empty( $options['security']['enable_csrf'] ) && strpos( $link, 'phpinfo' ) !== false ){
							$separator = strpos( $link, '?' ) !== false ? '&' : '?';
							$final_link = $link . $separator . 'token=' . urlencode( $_SESSION['csrf_token'] );
						}
					?>
                        <li><a target="_blank" href="<?= htmlspecialchars( $final_link, ENT_QUOTES, 'UTF-8' ); ?>"><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></a></li>
					<?php endforeach; ?>
                </ul>
            </div>
		<?php endif; ?>
		<?php if( !empty( $statsPreview ) ): ?>
            <div class="stats">
                <div class="section-header">
                    <h2>stats</h2>
                    <button class="toggle-btn" data-target="stats-extended" data-preview="stats-preview" aria-label="Expand stats">+</button>
                </div>
                <div id="stats-preview" class="table">
					<?php foreach( $statsPreview as $label => $value ): ?>
                        <div>
                            <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                            <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                        </div>
					<?php endforeach; ?>
                </div>
				<?php if( !empty( $statsProjectExpanded ) || !empty( $statsSystemExpanded ) || !empty( $statsPHPExpanded ) || !empty( $statsServerExpanded ) ): ?>
                <div id="stats-extended" style="display: none;">
					<?php if( !empty( $statsProjectExpanded ) ): ?>
                        <div class="table">
							<?php foreach( $statsProjectExpanded as $label => $value ): ?>
                                <div>
                                    <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                                    <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                                </div>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
					<?php if( !empty( $statsSystemExpanded ) ): ?>
                        <div class="table" style="margin-top: calc(var(--spacing-unit) * 2); padding-top: calc(var(--spacing-unit) * 2); border-top: 1px solid rgba(255, 255, 255, 0.1);">
							<?php foreach( $statsSystemExpanded as $label => $value ): ?>
                                <div>
                                    <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                                    <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                                </div>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
					<?php if( !empty( $statsPHPExpanded ) ): ?>
                        <div class="table" style="margin-top: calc(var(--spacing-unit) * 2); padding-top: calc(var(--spacing-unit) * 2); border-top: 1px solid rgba(255, 255, 255, 0.1);">
							<?php foreach( $statsPHPExpanded as $label => $value ): ?>
                                <div>
                                    <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                                    <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                                </div>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
					<?php if( !empty( $statsServerExpanded ) ): ?>
                        <div class="table" style="margin-top: calc(var(--spacing-unit) * 2); padding-top: calc(var(--spacing-unit) * 2); border-top: 1px solid rgba(255, 255, 255, 0.1);">
							<?php foreach( $statsServerExpanded as $label => $value ): ?>
                                <div>
                                    <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                                    <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                                </div>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>
				<?php endif; ?>
            </div>
		<?php endif; ?>
            <div class="actions">
                <div class="section-header">
                    <h2>actions</h2>
                    <button class="toggle-btn" data-target="actions-extended" aria-label="Expand actions">+</button>
                </div>
                <div class="action-buttons">
                    <button class="action-btn" data-action="clear-cache" title="Clear PHP opcache">
                        <span>🗑️</span> Clear Cache
                    </button>
                    <button class="action-btn" data-action="restart-apache" title="Restart Apache web server">
                        <span>🔄</span> Restart Apache
                    </button>
                </div>
                <div id="actions-extended" style="display: none; margin-top: calc(var(--spacing-unit) * 2);">
                    <div class="action-buttons">
                        <button class="action-btn" data-action="restart-mysql" title="Restart MySQL database">
                            <span>🔄</span> Restart MySQL
                        </button>
                        <button class="action-btn" data-action="view-logs" title="View Apache error log">
                            <span>📋</span> View Logs
                        </button>
                    </div>
                </div>
            </div>
		<?php if( !empty( $recentPreview ) ): ?>
            <div class="recent">
                <div class="section-header">
                    <h2>recent</h2>
                    <button class="toggle-btn" data-target="recent-extended" aria-label="Expand recent">+</button>
                </div>
                <ul>
					<?php foreach( $recentPreview as $item ): ?>
                        <li class="<?= htmlspecialchars( $item['type'], ENT_QUOTES, 'UTF-8' ); ?>">
                            <a target="_blank" href="<?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?>" class="name">
								<?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?>
                            </a>
                            <small title="<?= htmlspecialchars( $item['absolute'], ENT_QUOTES, 'UTF-8' ); ?>"><?= htmlspecialchars( $item['relative'], ENT_QUOTES, 'UTF-8' ); ?></small>
                        </li>
					<?php endforeach; ?>
                </ul>
				<?php if( !empty( $recentExtended ) ): ?>
                <div id="recent-extended" style="display: none; margin-top: calc(var(--spacing-unit) * 2);">
                    <ul>
						<?php foreach( $recentExtended as $item ): ?>
                            <li class="<?= htmlspecialchars( $item['type'], ENT_QUOTES, 'UTF-8' ); ?>">
                                <a target="_blank" href="<?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?>" class="name">
									<?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?>
                                </a>
                                <small title="<?= htmlspecialchars( $item['absolute'], ENT_QUOTES, 'UTF-8' ); ?>"><?= htmlspecialchars( $item['relative'], ENT_QUOTES, 'UTF-8' ); ?></small>
                            </li>
						<?php endforeach; ?>
                    </ul>
                </div>
				<?php endif; ?>
            </div>
		<?php endif; ?>
    </aside>
</main>
<script>
    const themeData = <?= json_encode( $themesForClient, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
    const themeOrder = <?= json_encode( array_values( $themeOrder ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
    const defaultThemeKey = <?= json_encode( $defaultThemeKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
    const themeButtons = Array.from(document.querySelectorAll('.theme-toggle button'));
    const rootStyle = document.documentElement.style;
    const THEME_STORAGE_KEY = 'directoryTheme';

    const updateButtonState = (activeKey) => {
        themeButtons.forEach((button) => {
            const isActive = button.dataset.themeKey === activeKey;
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const applyTheme = (key, { persist = true } = {}) => {
        const selectedTheme = themeData[key];
        if (!selectedTheme) {
            return;
        }

        rootStyle.setProperty('--color-bkg', selectedTheme.background);
        rootStyle.setProperty('--color-accent', selectedTheme.accent);
        rootStyle.setProperty('--color-secondary', selectedTheme.secondary);
        rootStyle.setProperty('--color-text', selectedTheme.text ?? '#ffffff');
        rootStyle.setProperty('--color-title', selectedTheme.title ?? 'rgba(255, 255, 255, 0.85)');
        rootStyle.setProperty('--color-muted', selectedTheme.muted ?? 'rgba(255, 255, 255, 0.65)');
        rootStyle.setProperty('--input-bg', selectedTheme.inputBg ?? 'rgba(255, 255, 255, 0.06)');
        rootStyle.setProperty('--input-focus-bg', selectedTheme.inputFocusBg ?? 'rgba(204, 204, 204, 0.05)');
        rootStyle.setProperty('--input-text', selectedTheme.inputText ?? '#dfdfdf');

        updateButtonState(key);

        if (persist) {
            try {
                localStorage.setItem(THEME_STORAGE_KEY, key);
            } catch (err) {
                // ignore storage failures
            }
        }
    };

    const resolveInitialTheme = () => {
        try {
            const storedKey = localStorage.getItem(THEME_STORAGE_KEY);
            if (storedKey && Object.prototype.hasOwnProperty.call(themeData, storedKey)) {
                return storedKey;
            }
        } catch (err) {
            // ignore storage failures
        }
        return defaultThemeKey;
    };

    const initialThemeKey = resolveInitialTheme();
    applyTheme(initialThemeKey, { persist: false });

    const focusButtonByOffset = (currentKey, offset) => {
        const currentIndex = themeOrder.indexOf(currentKey);
        if (currentIndex === -1) {
            return;
        }
        const nextIndex = (currentIndex + offset + themeOrder.length) % themeOrder.length;
        const nextKey = themeOrder[nextIndex];
        const nextButton = themeButtons.find((button) => button.dataset.themeKey === nextKey);
        if (nextButton) {
            nextButton.focus();
            applyTheme(nextKey);
        }
    };

    themeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.themeKey;
            if (key) {
                applyTheme(key);
            }
        });

        button.addEventListener('keydown', (event) => {
            const key = button.dataset.themeKey;
            if (!key) {
                return;
            }
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                focusButtonByOffset(key, -1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                focusButtonByOffset(key, 1);
            }
        });
    });

    const searchInput = document.querySelector('input.search');
    const projectContent = document.querySelector('.projects .content ul');

    searchInput.focus();
    searchInput.addEventListener('keyup', (e) => {
        let val = searchInput.value.trim();

        projectContent.querySelectorAll('li').forEach((el) => {
            // jump to the first displayed dir/file on enter
            if (e.keyCode == 13 && val != '') {
                const firstResult = projectContent.querySelector('li:not(.hidden) a');
                const loc = firstResult.getAttribute('href');
                searchInput.value = '';
                window.location = loc;
            }

            if (val == '') {
                el.classList.remove('hidden');
            } else if (el.innerText.indexOf(val) <= -1) {
                el.classList.add('hidden');
            }
        });
    });

    // Sort button handlers
    const sortButtons = document.querySelectorAll('.sort-btn');
    sortButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const sortType = btn.dataset.sort;
            const url = new URL(window.location);
            url.searchParams.set('sort', sortType);
            window.location.href = url.toString();
        });
    });

    // Toggle buttons for stats, actions, and info
    let runtimesLoaded = false;
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const target = document.getElementById(targetId);
            const ajax = this.getAttribute('data-ajax');
            const previewId = this.getAttribute('data-preview');

            if (target) {
                if (target.style.display === 'none') {
                    // Expanding
                    target.style.display = 'block';
                    this.textContent = '−';
                    this.setAttribute('aria-label', 'Collapse section');

                    // Hide preview if it exists
                    if (previewId) {
                        const preview = document.getElementById(previewId);
                        if (preview) {
                            preview.style.display = 'none';
                        }
                    }

                    // Load runtimes via AJAX if needed
                    if (ajax === 'runtimes' && !runtimesLoaded) {
                        const extendedInfo = document.getElementById('extended-info');
                        if (extendedInfo) {
                            extendedInfo.innerHTML = '<div style="text-align:center;padding:10px;color:var(--color-muted);">loading...</div>';

                            const url = new URL(window.location.href);
                            url.searchParams.set('action', 'detect_runtimes');
<?php if( !empty( $options['security']['enable_csrf'] ) ): ?>
                            url.searchParams.set('token', '<?= $_SESSION['csrf_token'] ?>');
<?php endif; ?>

                            fetch(url.toString())
                                .then(response => response.json())
                                .then(data => {
                                    const basicKeys = ['Apache', 'PHP', 'MySQL'];
                                    const extended = Object.entries(data)
                                        .filter(([key]) => !basicKeys.includes(key))
                                        .map(([label, value]) => {
                                            const escapedLabel = label.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                            const escapedValue = value.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                            return `<div><span>${escapedLabel}</span><span>${escapedValue}</span></div>`;
                                        })
                                        .join('');

                                    extendedInfo.innerHTML = extended ? `<div class="table">${extended}</div>` : '<div style="text-align:center;padding:10px;color:var(--color-muted);">No additional runtimes detected</div>';
                                    runtimesLoaded = true;
                                })
                                .catch(error => {
                                    console.error('Failed to load runtimes:', error);
                                    extendedInfo.innerHTML = '<div style="text-align:center;padding:10px;color:var(--color-muted);">⚠ Failed to load</div>';
                                });
                        }
                    }
                } else {
                    // Collapsing
                    target.style.display = 'none';
                    this.textContent = '+';
                    this.setAttribute('aria-label', 'Expand section');

                    // Show preview if it exists
                    if (previewId) {
                        const preview = document.getElementById(previewId);
                        if (preview) {
                            preview.style.display = 'block';
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>
