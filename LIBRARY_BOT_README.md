# 📚 Library Bot - Multi-Platform Book Downloader

A comprehensive Python bot using Selenium for downloading books and extracting metadata from **Z-Library**, **Anna's Archive**, and **LibGen**. The bot supports login credential saving, automated search, and batch downloads with detailed metadata extraction.

## ✨ Features

- 🔍 **Multi-Platform Search**: Search across Z-Library, Anna's Archive, and LibGen simultaneously
- 🔐 **Secure Login**: Save encrypted login credentials for Z-Library and Anna's Archive
- 📥 **Automated Downloads**: Download books with progress tracking
- 📊 **Metadata Extraction**: Extract and save detailed book metadata (JSON/TXT formats)
- 🎨 **Interactive CLI**: Beautiful command-line interface with colors and progress bars
- ⚙️ **Configurable**: Customizable settings for browser, download directory, and more
- 🌐 **Multi-Browser Support**: Chrome, Firefox, and Edge support
- 👻 **Headless Mode**: Run without visible browser window
- 🔄 **Retry Logic**: Automatic retry on failed downloads

## 🚀 Installation

### Prerequisites

- Python 3.7 or higher
- Chrome, Firefox, or Edge browser installed

### Step 1: Install Dependencies

```bash
pip install -r requirements.txt
```

### Step 2: Verify Installation

```bash
python library_bot.py --help
```

## 📖 Usage

### Quick Start

```bash
# Interactive mode (recommended for beginners)
python library_bot.py --interactive

# Quick search
python library_bot.py --search "python programming"

# Search with specific services only
python library_bot.py --search "machine learning" --services zlib annas

# Run in headless mode
python library_bot.py --search "data science" --headless
```

### 🔐 Managing Credentials

#### Save Credentials
```bash
# Save Z-Library credentials
python library_bot.py --save-credentials zlib your-email@example.com

# Save Anna's Archive credentials
python library_bot.py --save-credentials annas your-email@example.com
```

#### Delete Credentials
```bash
# Delete saved credentials
python library_bot.py --delete-credentials zlib
python library_bot.py --delete-credentials annas
```

#### Login to Services
```bash
# Login to all available services
python library_bot.py --login

# Login to specific services
python library_bot.py --login zlib annas
```

### 🔍 Searching for Books

#### Command Line Search
```bash
# Basic search
python library_bot.py --search "artificial intelligence"

# Limit results
python library_bot.py --search "python" --limit 20

# Search specific services
python library_bot.py --search "machine learning" --services libgen annas
```

#### Interactive Mode Search
```bash
python library_bot.py --interactive

# In interactive mode:
📚 LibBot> search python programming
📚 LibBot> login zlib
📚 LibBot> settings
📚 LibBot> help
📚 LibBot> quit
```

### 📁 File Organization

The bot organizes files in your home directory:

```
~/Downloads/LibraryBot/          # Downloaded books
~/.library_bot/
├── credentials.json             # Encrypted credentials
├── key.key                     # Encryption key
└── settings.json               # Bot settings
```

### ⚙️ Configuration

#### Default Settings
- **Download Directory**: `~/Downloads/LibraryBot`
- **Browser**: Chrome
- **Headless Mode**: False
- **Wait Timeout**: 10 seconds
- **Download Timeout**: 300 seconds (5 minutes)
- **Auto Login**: True
- **Save Metadata**: True
- **Metadata Format**: JSON

#### Customize Settings
Edit the settings through the interactive mode or by modifying `~/.library_bot/settings.json`:

```json
{
  "download_dir": "/path/to/your/downloads",
  "browser": "firefox",
  "headless": true,
  "wait_timeout": 15,
  "download_timeout": 600,
  "max_retries": 5,
  "auto_login": true,
  "save_metadata": true,
  "metadata_format": "json"
}
```

## 🔧 Advanced Usage

### Command Line Arguments

```bash
python library_bot.py [OPTIONS]

Options:
  -s, --search TEXT             Search query
  --services [zlib|annas|libgen]  Services to search (default: all)
  -l, --limit INTEGER           Limit search results (default: 10)
  -i, --interactive            Run in interactive mode
  --login [zlib|annas]         Login to services
  --save-credentials SERVICE EMAIL  Save credentials
  --delete-credentials [zlib|annas]  Delete credentials
  --headless                   Run browser in headless mode
  --help                       Show help message
```

### Search Examples

```bash
# Academic papers
python library_bot.py --search "machine learning research" --services annas

# Programming books
python library_bot.py --search "python cookbook" --limit 15

# Scientific texts
python library_bot.py --search "quantum physics" --services libgen

# Fiction books
python library_bot.py --search "science fiction novels"
```

### Batch Operations

The bot automatically saves search results to JSON files for later processing:

```bash
# Search results are saved to:
~/Downloads/LibraryBot/search_results_[timestamp].json
```

## 🛡️ Security Features

- **Encrypted Credentials**: All login credentials are encrypted using Fernet encryption
- **Secure Storage**: Credentials are stored in a protected directory
- **No Plain Text**: Passwords are never stored in plain text
- **Automatic Cleanup**: Temporary files are automatically cleaned up

## 🌐 Supported Services

### Z-Library (Z-lib.gs)
- ✅ Login support
- ✅ Search functionality
- ✅ Download books
- ✅ Metadata extraction
- 📧 Requires account for full access

### Anna's Archive
- ✅ No login required (optional)
- ✅ Search functionality
- ✅ Multiple download mirrors
- ✅ Metadata extraction
- 🌍 Open access to academic papers

### Library Genesis (LibGen)
- ✅ No login required
- ✅ Search functionality
- ✅ Multiple download sources
- ✅ Metadata extraction
- 📚 Academic and scientific books

## 🐛 Troubleshooting

### Common Issues

#### Browser Not Starting
```bash
# Install browser drivers manually
pip install webdriver-manager --upgrade

# Try different browser
python library_bot.py --search "test" --headless
```

#### Download Failures
- Check internet connection
- Verify the site is accessible
- Try different browser (Firefox instead of Chrome)
- Run with `--headless` flag

#### Login Issues
- Verify credentials are correct
- Check if account is not suspended
- Some sites may require CAPTCHA solving (manual intervention needed)

#### Search Not Working
- Try different search terms
- Some services may be temporarily unavailable
- Check if the site structure has changed

### Getting Help

```bash
# Interactive mode help
python library_bot.py --interactive
📚 LibBot> help

# Command line help
python library_bot.py --help
```

## 📊 Metadata Information

The bot extracts comprehensive metadata for each book:

### Common Fields
- **Title**: Book title
- **Author**: Author name(s)
- **Year**: Publication year
- **Publisher**: Publishing house
- **Format**: File format (PDF, EPUB, MOBI, etc.)
- **Size**: File size
- **Language**: Book language
- **Pages**: Number of pages
- **ISBN**: International Standard Book Number

### Service-Specific Fields
- **Z-Library**: User ratings, download count
- **Anna's Archive**: Multiple source information
- **LibGen**: MD5 hash, DOI, edition information

## 🔄 Updates and Maintenance

The bot is designed to be resilient to website changes, but occasionally updates may be needed:

1. **Update Dependencies**:
   ```bash
   pip install -r requirements.txt --upgrade
   ```

2. **Check for Site Changes**: If a service stops working, it may be due to website structure changes

3. **Browser Driver Updates**: The bot automatically manages browser drivers, but manual updates may be needed

## ⚖️ Legal Notice

This bot is for educational and research purposes. Users are responsible for:

- Complying with terms of service of each platform
- Respecting copyright laws in their jurisdiction
- Using downloaded content legally and ethically
- Not redistributing copyrighted material

## 🤝 Contributing

Contributions are welcome! Areas for improvement:

- Additional library services
- Better error handling
- GUI interface
- Mobile app version
- Enhanced metadata extraction

## 📄 License

This project is provided as-is for educational purposes. Users are responsible for ensuring their usage complies with applicable laws and terms of service.

---

**Happy Reading! 📚✨**