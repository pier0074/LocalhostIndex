# LocalhostIndex

A beautiful, self-contained localhost homepage for local development environments. Display all your projects, server info, and quick links in one elegant interface.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-MIT-green)

## Features

- 📂 **Directory Browser** - Automatically scans and displays all projects/folders
- 🎨 **10 Beautiful Themes** - Switch between bluey, sunny, forest, retro, matrix, nebula, sundown, mono, dark, and light
- 🔍 **Instant Search** - Filter projects with live search and Enter-to-navigate
- 📊 **Server Dashboard** - Shows Apache, PHP, and MySQL versions
- 📈 **Statistics** - Project count, file count, disk usage, and last update info
- ⏱️ **Recent Activity** - Displays 5 most recently modified files/folders
- 🔗 **Quick Links** - Configurable shortcuts to phpMyAdmin, phpinfo, or custom tools
- 📱 **Responsive Design** - Works beautifully on desktop and mobile
- ♿ **Accessible** - Keyboard navigation and ARIA labels
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
    // Choose a theme: bluey, pinky, purply, sunny, forest, retro, matrix, nebula, sundown, monochrome, dark, light
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

## Security Considerations

⚠️ **Important**: This tool is designed for **local development only**.

### For Local Use Only
- No authentication by default
- Exposes phpinfo() via `?phpinfo=1`
- Shows directory structure
- Displays server configuration

### If Deploying Publicly
**DO NOT use on public servers without:**
1. Adding authentication (HTTP Basic Auth or password protection)
2. Removing or protecting phpinfo() endpoint
3. Restricting access via `.htaccess` or firewall
4. Validating all file paths against traversal attacks

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

## Version History

### v1.0.0 (2025-01-08)
- Initial release
- 10 color themes with localStorage persistence
- MySQL/Apache/PHP version detection
- Directory scanning with exclusion patterns
- Search functionality
- Recent files tracking
- Statistics dashboard
- Responsive design
- Accessibility features

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
