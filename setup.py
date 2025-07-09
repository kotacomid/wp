#!/usr/bin/env python3
"""
Setup script for Library Bot
This script helps with installation and initial configuration
"""

import subprocess
import sys
import os
from pathlib import Path

def install_requirements():
    """Install required packages"""
    print("📦 Installing required packages...")
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"])
        print("✅ Successfully installed requirements")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Failed to install requirements: {e}")
        return False

def check_python_version():
    """Check if Python version is compatible"""
    print("🐍 Checking Python version...")
    version = sys.version_info
    if version.major == 3 and version.minor >= 7:
        print(f"✅ Python {version.major}.{version.minor}.{version.micro} is compatible")
        return True
    else:
        print(f"❌ Python {version.major}.{version.minor}.{version.micro} is not compatible")
        print("Please install Python 3.7 or higher")
        return False

def create_directories():
    """Create necessary directories"""
    print("📁 Creating directories...")
    
    # Create download directory
    download_dir = Path.home() / 'Downloads' / 'LibraryBot'
    download_dir.mkdir(parents=True, exist_ok=True)
    print(f"✅ Created download directory: {download_dir}")
    
    # Config directory will be created by the bot itself
    print("✅ Directory structure ready")
    return True

def test_installation():
    """Test if the bot can be imported and run"""
    print("🧪 Testing installation...")
    try:
        from library_bot import LibraryBot
        print("✅ Library Bot imported successfully")
        
        # Test config creation
        bot = LibraryBot()
        print("✅ Configuration system working")
        
        return True
    except ImportError as e:
        print(f"❌ Failed to import Library Bot: {e}")
        return False
    except Exception as e:
        print(f"❌ Error during testing: {e}")
        return False

def show_usage_examples():
    """Show some usage examples"""
    print("\n🚀 Library Bot is ready!")
    print("=" * 50)
    print("\n📖 Quick Start Examples:")
    print()
    print("1. Interactive mode (recommended for beginners):")
    print("   python library_bot.py --interactive")
    print()
    print("2. Quick search:")
    print("   python library_bot.py --search \"python programming\"")
    print()
    print("3. Save credentials:")
    print("   python library_bot.py --save-credentials zlib your-email@example.com")
    print()
    print("4. Search with specific services:")
    print("   python library_bot.py --search \"machine learning\" --services zlib annas")
    print()
    print("5. Run in headless mode:")
    print("   python library_bot.py --search \"data science\" --headless")
    print()
    print("📚 For more examples, check:")
    print("   - LIBRARY_BOT_README.md")
    print("   - example_usage.py")
    print()
    print("🆘 Get help:")
    print("   python library_bot.py --help")
    print()

def check_browser_availability():
    """Check if supported browsers are available"""
    print("🌐 Checking browser availability...")
    
    browsers = {
        'chrome': ['google-chrome', 'chrome', 'chromium'],
        'firefox': ['firefox'],
        'edge': ['microsoft-edge', 'edge']
    }
    
    available_browsers = []
    
    for browser_name, commands in browsers.items():
        for cmd in commands:
            try:
                subprocess.run([cmd, '--version'], 
                             capture_output=True, 
                             check=True, 
                             timeout=5)
                available_browsers.append(browser_name)
                print(f"✅ {browser_name.capitalize()} found")
                break
            except (subprocess.CalledProcessError, FileNotFoundError, subprocess.TimeoutExpired):
                continue
    
    if not available_browsers:
        print("⚠️  No supported browsers found!")
        print("Please install one of: Chrome, Firefox, or Edge")
        return False
    else:
        print(f"✅ Available browsers: {', '.join(available_browsers)}")
        return True

def main():
    """Run the setup process"""
    print("🔧 Library Bot Setup")
    print("=" * 30)
    
    steps = [
        ("Checking Python version", check_python_version),
        ("Checking browser availability", check_browser_availability),
        ("Installing requirements", install_requirements),
        ("Creating directories", create_directories),
        ("Testing installation", test_installation)
    ]
    
    for step_name, step_func in steps:
        print(f"\n{step_name}...")
        if not step_func():
            print(f"❌ Setup failed at: {step_name}")
            print("Please fix the issue and run setup again.")
            sys.exit(1)
    
    show_usage_examples()
    
    print("🎉 Setup completed successfully!")
    print("You can now use the Library Bot. Happy reading! 📚")

if __name__ == '__main__':
    main()