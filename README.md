# LocalhostIndex

A beautiful, self-contained localhost homepage for local development environments. Display all your projects, server info, and quick links in one elegant interface.

![Version](https://img.shields.io/badge/version-1.7.2-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-MIT-green)
![GitHub stars](https://img.shields.io/github/stars/pier0074/LocalhostIndex?style=social)
![GitHub forks](https://img.shields.io/github/forks/pier0074/LocalhostIndex?style=social)
![Last Commit](https://img.shields.io/github/last-commit/pier0074/LocalhostIndex)
![Downloads](https://img.shields.io/github/downloads/pier0074/LocalhostIndex/total)

## Features

- 📂 **Directory Browser** - Automatically scans and displays all projects/folders
- 🎨 **10 Beautiful Themes** - Switch between bluey, sunny, forest, retro, matrix, nebula, sundown, mono, dark, and light
- 🔍 **Instant Search** - Filter projects with live search and Enter-to-navigate
- 🔀 **Instant Sorting** - Client-side sort by name, date, or size with no page reload ✨ NEW
- 🔄 **Reverse Sort** - Click any sort button twice to reverse order (A-Z ↔ Z-A) ✨ NEW
- 📁 **Folder Sizes** - Actual folder sizes calculated recursively ✨ NEW
- 💾 **File Sizes** - Display file sizes in human-readable format
- ⚡ **Smart Caching** - 60-second cache for improved performance
- 🔧 **Multi-Runtime Detection** - Auto-detects Python, Node.js, Ruby, Go, Docker and more
- 📊 **Server Dashboard** - Shows Apache, PHP, MySQL, and all detected development tools
- 📈 **System Statistics** - Total disk, Memory, CPU cores, Uptime, OS version with names (macOS Sonoma, etc.) ✨ NEW
- 🎛️ **Quick Actions** - Restart Apache/MySQL, Clear Cache, View Logs with one click ✨ NEW
- 📁 **Collapsible Sections** - All sections expand/collapse with +/- toggle buttons ✨ NEW
- 🔎 **Preview Mode** - See key info at a glance, expand for full details ✨ NEW
- ⏱️ **Recent Activity** - Displays 2 recent items (folded) or 10 items (expanded)
- 🔗 **Quick Links** - Configurable shortcuts to phpMyAdmin, phpinfo, or custom tools
- 📱 **Responsive Design** - Works beautifully on desktop and mobile
- ♿ **Accessible** - Keyboard navigation and ARIA labels
- 🔐 **Security Hardened** - CSRF protection, path validation, optional authentication
- 💾 **Theme Persistence** - Remembers your theme preference via localStorage

## Installation

### Quick Start

1. Download `index.php`
2. Drop it into your localhost root directory (e.g., `/htdocs`, `/www`, or `/Sites`)
3. Navigate to `http://localhost/` in your browser

That's it! No dependencies, no build process, no configuration required.

### Requirements

- PHP 7.4 or higher
- Apache web server (for Apache version detection)
- Optional: MySQL/MariaDB (for database version detection)

## Configuration

Edit the `$options` array at the top of `index.php`:

```php
$options = [
    // Choose a theme: bluey, sunny, forest, retro, matrix, nebula, sundown, monochrome, dark, light
    'theme' => 'bluey',

    // Exclude files or folders using wildcard patterns
    'exclude' => [ '.DS_Store', '.localized', '*.php*', '*.png' ],

    // Add extra tool links
    'extras' => [
        'phpinfo()' => '?phpinfo=1',
        'PhpMyAdmin()' => 'phpMyAdmin'
    ],

    // Set favicon file(s) to look for
    'favicon' => [ 'favicon.ico', 'favicon.png', 'favicon.svg' ],

    // MySQL detection options
    'mysql' => [
        // Explicit binary paths (optional)
        'bin' => ['/usr/local/bin/mysql'],

        // Direct connection (optional)
        'connection' => [
            'host' => '127.0.0.1',
            'user' => 'root',
            'password' => '',
            'port' => 3306,
        ]
    ],

    // Display settings (v1.2.0+)
    'display' => [
        'show_file_sizes' => true,      // Show file sizes
        'default_sort' => 'name',       // Default sort: name, date, size
        'enable_cache' => true,         // Enable 60s cache
    ],

    // Security settings (v1.1.0+)
    'security' => [
        'enable_authentication' => false,
        'password' => '',               // Bcrypt hash
        'disable_phpinfo' => false,
        'enable_csrf' => true,
        'strict_path_validation' => true,
    ]
];
```

## Themes

10 carefully crafted color schemes:

| Theme | Description |
|-------|-------------|
| **bluey** | Deep blue with lime accents (default) |
| **light** | Clean light mode |
| **sunny** | Warm orange and yellow |
| **forest** | Teal and forest green |
| **retro** | Classic terminal green |
| **matrix** | Cyberpunk neon green |
| **nebula** | Purple and pink space vibes |
| **sundown** | Sunset coral tones |
| **monochrome** | Black and white with gold |
| **dark** | Pure dark mode |

Click the theme dots in the top-right to switch instantly. Your preference is saved automatically.

## Usage Tips

### Search & Navigation
- Type to filter projects instantly
- Press **Enter** to open the first matching result
- Click any project/file to open in new tab

### Keyboard Shortcuts
- **Arrow Left/Right** - Navigate between theme buttons
- **Tab** - Move through interface elements
- **Enter** - Quick-open first search result

### MySQL Detection
The script attempts to detect MySQL version using multiple strategies:
1. Shell commands (`mysql --version`, `mysqld --version`)
2. Common binary paths
3. Environment variables (`DB_USER`, `MYSQL_HOST`, etc.)
4. Database URL parsing (`DATABASE_URL`)
5. Direct mysqli connection (if credentials provided)
6. Falls back to mysqli client info

## Customization

### Exclude Files
Add patterns to hide files/folders from the list:

```php
'exclude' => [ '.git*', 'node_modules', '*.log', '.env' ]
```

### Add Custom Links
Add shortcuts to frequently used tools:

```php
'extras' => [
    'Adminer' => 'adminer.php',
    'MailHog' => 'http://localhost:8025',
    'Logs' => 'logs/',
]
```

### Change Default Theme
Set your preferred default theme:

```php
'theme' => 'matrix'  // or any other theme name
```

## Security Features (v1.1.0+)

LocalhostIndex now includes **built-in security options** for shared or public environments.

### Security Configuration

Configure security settings in the `$options['security']` array:

```php
'security' => [
    // Enable password authentication
    'enable_authentication' => false,

    // Set password hash (generate with: password_hash('your_password', PASSWORD_DEFAULT))
    'password' => '',

    // Disable phpinfo() for security
    'disable_phpinfo' => false,

    // Enable CSRF token protection (recommended)
    'enable_csrf' => true,

    // Strict path validation against traversal attacks
    'strict_path_validation' => true,
]
```

### Enabling Authentication

To enable password protection:

1. Generate a password hash:
   ```php
   echo password_hash('your_secure_password', PASSWORD_DEFAULT);
   ```

2. Update your configuration:
   ```php
   'security' => [
       'enable_authentication' => true,
       'password' => '$2y$10$...',  // paste your hash here
   ]
   ```

### Security Features

✅ **CSRF Protection** - Protects phpinfo() and sensitive endpoints
✅ **Path Traversal Prevention** - Validates all file paths
✅ **Password Authentication** - Optional login system
✅ **Session Management** - Secure session handling
✅ **Input Sanitization** - All user input is escaped
✅ **Reduced Error Suppression** - Better error handling

### For Local Use Only (Default Configuration)

By default (all security features disabled):
- No authentication required
- phpinfo() accessible via `?phpinfo=1&token=<csrf_token>`
- Shows directory structure
- Displays server configuration

**Recommended for**: Local development (localhost, 127.0.0.1)

### For Shared/Public Servers

**Enable these settings**:
```php
'security' => [
    'enable_authentication' => true,
    'password' => '<your_hash>',
    'disable_phpinfo' => true,
    'enable_csrf' => true,
    'strict_path_validation' => true,
]
```

**Additional recommendations**:
- Use HTTPS
- Configure firewall rules
- Use `.htaccess` IP restrictions
- Regular security audits

## Browser Compatibility

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Opera 76+

## Troubleshooting

### MySQL Version Shows "Unknown"
- Ensure MySQL/MariaDB is installed and running
- Add explicit binary path in `$options['mysql']['bin']`
- Provide connection credentials in `$options['mysql']['connection']`

### Apache Version Not Detected
- Ensure Apache is running
- Check if `apache_get_version()` is available in your PHP build

### Themes Not Persisting
- Check if localStorage is enabled in browser
- Try clearing browser cache
- Ensure you're not in private/incognito mode

## Development

### File Structure
```
LocalhostIndex/
├── index.php       # Single-file application (intentional)
├── README.md       # This file
└── .gitignore      # Git ignore rules
```

### Why Single File?
The entire application is intentionally in one `index.php` file for:
- **Portability** - Drop anywhere and it works
- **Simplicity** - No build process or dependencies
- **Zero Config** - Works out of the box
- **Easy Updates** - Replace one file to upgrade

## Contributing

Contributions are welcome! Please ensure:
- Code remains in a single `index.php` file
- Follows existing code style
- Maintains backward compatibility
- Updates README.md with new features

## License

MIT License - feel free to use, modify, and distribute.

## Credits

Developed with ❤️ for the local development community.

---

**Note**: This is a development tool. Use responsibly and never deploy to production without proper security measures.
