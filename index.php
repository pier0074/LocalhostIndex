<?php
$options = [
	/**
	 * Set the theme
	 * Available themes: bluey, pinky, purply
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
	'mysql' => []
];

// display phpinfo
if( !empty( $_GET['phpinfo'] ) ){
	phpinfo();
	exit;
}

// server info
$apache_version = explode( ' ', explode( '/', apache_get_version() )[1] )[0];
$php_version = explode( '-', phpversion() )[0];


$mysql_version = detectMysqlVersion( $options['mysql'] ?? [] );

$info = [
	'Apache' => $apache_version,
	'PHP' => $php_version,
	'MySQL' => $mysql_version
];

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
			$probe_result = trim( (string) @shell_exec( $probe . ' 2>/dev/null' ) );
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
			$output = trim( (string) @shell_exec( $escaped_binary . ' --version 2>/dev/null' ) );
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
	$timeout = isset( $connection['timeout'] ) ? (int) $connection['timeout'] : 2;

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
		$url_parts = @parse_url( $database_url );
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
		$mysqli = @mysqli_init();
		if( $mysqli ){
			if( defined( 'MYSQLI_OPT_CONNECT_TIMEOUT' ) ){
				@mysqli_options( $mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout );
			}
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

// read all items in the ./ dir
$directory_list = [];
if( $handle = opendir( './' ) ){
	$item_list = [];
	while( false !== ( $item = readdir( $handle ) ) ){
		if( $item == '..' || $item == '.' || filenameMatch( $options['exclude'], $item ) ){
			continue;
		}

		$item_type = is_dir( $item ) ? 'dir' : 'file';
		$order = ( $item_type == 'dir' ) ? 1 : 0;
		$item_list[] = [
			'name' => $item,
			'type' => $item_type,
			'order' => $order
		];
	}
	usort(
		$item_list,
		static function ( $a, $b ) {
			if( $a['order'] === $b['order'] ){
				return strcasecmp( $a['name'], $b['name'] );
			}
			return $b['order'] <=> $a['order'];
		}
	);
	$directory_list = $item_list;
	closedir( $handle );
}

$project_count = 0;
$file_count = 0;
$latest_item = null;
$latest_mtime = 0;
$recent_items = [];

foreach( $directory_list as $item ){
	$is_dir = ( $item['type'] === 'dir' );
	$project_count += $is_dir ? 1 : 0;
	$file_count += $is_dir ? 0 : 1;

	$path = __DIR__ . '/' . $item['name'];
	$mtime = @filemtime( $path ) ?: 0;
	if( $mtime > 0 ){
		$recent_items[] = $item + [
			'mtime' => $mtime,
		];
		if( $mtime > $latest_mtime ){
			$latest_mtime = $mtime;
			$latest_item = $item;
		}
	}
}

$stats = [];
if( $project_count > 0 ){
	$stats['Projects'] = number_format( $project_count );
}
if( $file_count > 0 ){
	$stats['Files'] = number_format( $file_count );
}
if( $latest_item ){
	$stats['Last update'] = formatRelativeTime( $latest_mtime ) . ' · ' . $latest_item['name'];
}
$disk_total = @disk_total_space( __DIR__ );
$disk_free = @disk_free_space( __DIR__ );
if( $disk_total > 0 && $disk_free !== false ){
	$free_percent = round( ( $disk_free / $disk_total ) * 100 );
	$stats['Disk free'] = humanFileSize( $disk_free ) . ' (' . $free_percent . '%)';
}

usort( $recent_items, static fn( $a, $b ) => $b['mtime'] <=> $a['mtime'] );
$recent_items = array_slice( $recent_items, 0, 5 );
$recent_items = array_map(
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
	$recent_items
);

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

$theme_order = array_keys( $themes );
$default_theme_key = array_key_exists( $options['theme'], $themes ) ? $options['theme'] : $theme_order[0];
$theme = $themes[ $default_theme_key ];
$default_theme_index = array_search( $default_theme_key, $theme_order, true );

$themes_for_client = [];
foreach( $theme_order as $key ){
	$themes_for_client[ $key ] = [
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

$favicon_candidates = $options['favicon'] ?? [];
if( is_string( $favicon_candidates ) ){
	$favicon_candidates = [ $favicon_candidates ];
}
$favicon_candidates = array_filter(
	array_map( static fn( $candidate ) => ltrim( trim( (string) $candidate ), '/' ), $favicon_candidates )
);

$favicon_href = '';
foreach( $favicon_candidates as $candidate ){
	$favicon_path = __DIR__ . '/' . $candidate;
	if( file_exists( $favicon_path ) ){
		$favicon_href = $candidate;
		break;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php if( !empty( $favicon_href ) ): ?>
        <link rel="icon" href="<?= htmlspecialchars( $favicon_href, ENT_QUOTES, 'UTF-8' ); ?>">
    <?php endif; ?>
    <style>
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
        }

        body {
            font-family: monospace;
            background: var(--color-bkg);
            color: var(--color-text);
            margin: 0;
            padding: 80px 40px 50px;
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

        .theme-toggle {
            position: fixed;
            top: 24px;
            right: 24px;
            display: flex;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            z-index: 10;
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
            width: min(1100px, 95vw);
            margin: 8px auto 45px;
            display: flex;
            justify-content: center;
            gap: 36px;
            color: var(--color-text);
        }

        main > .projects {
            flex: 2.4;
            min-width: 0;
            display: flex;
            flex-direction: column;
            max-height: 600px;
        }

        main > .projects h2 {
            flex-shrink: 0;
        }

        main > .projects .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        main > aside {
            flex: 1.2;
            min-width: 280px;
        }


        .info,
        .stats {
            margin-bottom: 24px;
        }

        .info .table > div,
        .stats .table > div {
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
        .stats .table > div > span:first-child {
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 11px;
            color: var(--color-muted);
        }

        .info .table > div > span:last-child,
        .stats .table > div > span:last-child {
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

        .recent {
            margin-bottom: 24px;
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
        }

        .recent ul li.dir .name {
            color: var(--color-accent);
            font-weight: bold;
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
            padding-right: 40px;
            padding-left: 2px;
            max-width: 100%;
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
        }

        .projects ul li.hidden {
            display: none;
        }

        .projects ul li a {
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            padding: 2px 4px;
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

        .tools {
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
        }

        .tools ul li a:hover {
            background-color: var(--color-accent);
            color: var(--color-bkg);
        }

        @media screen and (max-width: 500px) {
            body {
                padding: 80px 12px 45px;
            }

            .theme-toggle {
                top: 16px;
                right: 16px;
                padding: 8px 12px;
                gap: 8px;
            }

            .theme-toggle button {
                width: 16px;
                height: 16px;
            }

            main {
                margin: 16px 0 45px;
                max-height: none;
                flex-direction: column;
                gap: 28px;
            }

            main > aside {
                order: 2;
                min-width: 0;
            }

            .projects {
                padding-left: 0;
                padding-right: 0;
            }

            .recent ul li {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .recent ul li small {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
<div class="theme-toggle" role="group" aria-label="Theme selector">
	<?php foreach( $theme_order as $idx => $key ): ?>
        <button
            type="button"
            data-theme-key="<?= htmlspecialchars( $key, ENT_QUOTES, 'UTF-8' ); ?>"
            aria-pressed="<?= $idx === $default_theme_index ? 'true' : 'false'; ?>"
            aria-label="<?= htmlspecialchars( $themes[ $key ]['label'], ENT_QUOTES, 'UTF-8' ); ?>"
            style="--theme-accent: <?= htmlspecialchars( $themes[ $key ]['accent'], ENT_QUOTES, 'UTF-8' ); ?>;"
        ></button>
	<?php endforeach; ?>
</div>
<main>
    <div class="projects">
        <h2>projects</h2>
        <div class="content">
            <input type="text" placeholder="Search" class="search">
            <ul>
				<?php foreach( $directory_list as $item ): ?>
                    <li class="<?= $item['type'] ?>">
                        <a target="_blank" href="<?= $item['name'] ?>">
							<?= $item['name']; ?>
                        </a>
                    </li>
				<?php endforeach; ?>
            </ul>
        </div>
    </div>
    <aside>
        <div class="info">
            <h2>info</h2>
            <div class="table">
				<?php foreach( $info as $label => $value ): ?>
                    <div class="<?= $label ?>">
                        <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                        <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>
		<?php if( !empty( $options['extras'] ) ): ?>
            <div class="tools">
                <h2>extras</h2>
                <ul>
					<?php foreach( $options['extras'] as $label => $link ): ?>
                        <li><a target="_blank" href="<?= htmlspecialchars( $link, ENT_QUOTES, 'UTF-8' ); ?>"><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></a></li>
					<?php endforeach; ?>
                </ul>
            </div>
		<?php endif; ?>
		<?php if( !empty( $stats ) ): ?>
            <div class="stats">
                <h2>stats</h2>
                <div class="table">
					<?php foreach( $stats as $label => $value ): ?>
                        <div>
                            <span><?= htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ); ?></span>
                            <span><?= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); ?></span>
                        </div>
					<?php endforeach; ?>
                </div>
            </div>
		<?php endif; ?>
		<?php if( !empty( $recent_items ) ): ?>
            <div class="recent">
                <h2>recent</h2>
                <ul>
					<?php foreach( $recent_items as $item ): ?>
                        <li class="<?= htmlspecialchars( $item['type'], ENT_QUOTES, 'UTF-8' ); ?>">
                            <span class="name"><?= htmlspecialchars( $item['name'], ENT_QUOTES, 'UTF-8' ); ?></span>
                            <small title="<?= htmlspecialchars( $item['absolute'], ENT_QUOTES, 'UTF-8' ); ?>"><?= htmlspecialchars( $item['relative'], ENT_QUOTES, 'UTF-8' ); ?></small>
                        </li>
					<?php endforeach; ?>
                </ul>
            </div>
		<?php endif; ?>
    </aside>
</main>
<script>
    const themeData = <?= json_encode( $themes_for_client, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
    const themeOrder = <?= json_encode( array_values( $theme_order ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
    const defaultThemeKey = <?= json_encode( $default_theme_key, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
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
</script>
</body>
</html>
