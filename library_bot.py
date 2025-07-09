#!/usr/bin/env python3
"""
Library Bot - A comprehensive bot for downloading books from Z-Library, Anna's Archive, and LibGen
Author: Assistant
Version: 1.0.0
"""

import argparse
import sys
import time
from pathlib import Path
import json
from colorama import init, Fore, Back, Style
from tqdm import tqdm
import logging

# Initialize colorama for cross-platform colored output
init(autoreset=True)

# Import our modules
from config import Config
from browser_handler import BrowserHandler
from zlib_handler import ZLibHandler
from annas_handler import AnnasHandler
from libgen_handler import LibGenHandler

class LibraryBot:
    def __init__(self):
        self.config = Config()
        self.browser = None
        self.zlib = None
        self.annas = None
        self.libgen = None
        self.logger = self._setup_logger()
    
    def _setup_logger(self):
        """Setup main logger"""
        logger = logging.getLogger('LibraryBot')
        logger.setLevel(logging.INFO)
        
        if not logger.handlers:
            handler = logging.StreamHandler()
            formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
            handler.setFormatter(formatter)
            logger.addHandler(handler)
        
        return logger
    
    def initialize_browser(self):
        """Initialize browser and handlers"""
        print(f"{Fore.CYAN}🚀 Initializing browser...{Style.RESET_ALL}")
        
        self.browser = BrowserHandler(self.config)
        if not self.browser.start_browser():
            print(f"{Fore.RED}❌ Failed to start browser{Style.RESET_ALL}")
            return False
        
        # Initialize service handlers
        self.zlib = ZLibHandler(self.browser, self.config)
        self.annas = AnnasHandler(self.browser, self.config)
        self.libgen = LibGenHandler(self.browser, self.config)
        
        print(f"{Fore.GREEN}✅ Browser initialized successfully{Style.RESET_ALL}")
        return True
    
    def close_browser(self):
        """Close browser"""
        if self.browser:
            self.browser.close_browser()
            print(f"{Fore.YELLOW}🔒 Browser closed{Style.RESET_ALL}")
    
    def save_credentials(self, service, username, password):
        """Save login credentials for a service"""
        try:
            self.config.save_credentials(service, username, password)
            print(f"{Fore.GREEN}✅ Credentials saved for {service}{Style.RESET_ALL}")
            return True
        except Exception as e:
            print(f"{Fore.RED}❌ Failed to save credentials: {e}{Style.RESET_ALL}")
            return False
    
    def delete_credentials(self, service):
        """Delete saved credentials for a service"""
        try:
            if self.config.delete_credentials(service):
                print(f"{Fore.GREEN}✅ Credentials deleted for {service}{Style.RESET_ALL}")
            else:
                print(f"{Fore.YELLOW}⚠️ No credentials found for {service}{Style.RESET_ALL}")
            return True
        except Exception as e:
            print(f"{Fore.RED}❌ Failed to delete credentials: {e}{Style.RESET_ALL}")
            return False
    
    def login_to_services(self, services=None):
        """Login to specified services"""
        if not services:
            services = ['zlib', 'annas']  # Default services that support login
        
        login_results = {}
        
        for service in services:
            print(f"{Fore.CYAN}🔑 Logging into {service}...{Style.RESET_ALL}")
            
            if service == 'zlib' and self.zlib:
                login_results[service] = self.zlib.login()
            elif service == 'annas' and self.annas:
                login_results[service] = self.annas.login()
            else:
                print(f"{Fore.YELLOW}⚠️ Service {service} not supported or not initialized{Style.RESET_ALL}")
                continue
            
            if login_results[service]:
                print(f"{Fore.GREEN}✅ Successfully logged into {service}{Style.RESET_ALL}")
            else:
                print(f"{Fore.RED}❌ Failed to login to {service}{Style.RESET_ALL}")
        
        return login_results
    
    def search_books(self, query, services=None, limit=10):
        """Search for books across specified services"""
        if not services:
            services = ['zlib', 'annas', 'libgen']
        
        all_results = {}
        
        for service in services:
            print(f"{Fore.CYAN}🔍 Searching {service} for: {query}{Style.RESET_ALL}")
            
            try:
                if service == 'zlib' and self.zlib:
                    results = self.zlib.search(query, limit)
                elif service == 'annas' and self.annas:
                    results = self.annas.search(query, limit)
                elif service == 'libgen' and self.libgen:
                    results = self.libgen.search(query, limit)
                else:
                    print(f"{Fore.YELLOW}⚠️ Service {service} not available{Style.RESET_ALL}")
                    continue
                
                all_results[service] = results
                print(f"{Fore.GREEN}✅ Found {len(results)} results in {service}{Style.RESET_ALL}")
                
            except Exception as e:
                print(f"{Fore.RED}❌ Error searching {service}: {e}{Style.RESET_ALL}")
                all_results[service] = []
        
        return all_results
    
    def download_book(self, book_info, service):
        """Download a specific book"""
        print(f"{Fore.CYAN}📥 Downloading: {book_info.get('title', 'Unknown Title')}{Style.RESET_ALL}")
        
        try:
            downloaded_file = None
            
            if service == 'zlib' and self.zlib:
                downloaded_file = self.zlib.download_book(book_info)
            elif service == 'annas' and self.annas:
                downloaded_file = self.annas.download_book(book_info)
            elif service == 'libgen' and self.libgen:
                downloaded_file = self.libgen.download_book(book_info)
            else:
                print(f"{Fore.RED}❌ Service {service} not available{Style.RESET_ALL}")
                return None
            
            if downloaded_file:
                print(f"{Fore.GREEN}✅ Successfully downloaded: {downloaded_file}{Style.RESET_ALL}")
                return downloaded_file
            else:
                print(f"{Fore.RED}❌ Download failed{Style.RESET_ALL}")
                return None
                
        except Exception as e:
            print(f"{Fore.RED}❌ Download error: {e}{Style.RESET_ALL}")
            return None
    
    def display_search_results(self, results):
        """Display search results in a formatted way"""
        for service, books in results.items():
            if not books:
                continue
                
            print(f"\n{Fore.MAGENTA}{'='*50}")
            print(f"{service.upper()} RESULTS ({len(books)} found)")
            print(f"{'='*50}{Style.RESET_ALL}")
            
            for i, book in enumerate(books, 1):
                print(f"\n{Fore.YELLOW}[{i}] {book.get('title', 'Unknown Title')}{Style.RESET_ALL}")
                
                if book.get('author'):
                    print(f"    👤 Author: {book['author']}")
                if book.get('year'):
                    print(f"    📅 Year: {book['year']}")
                if book.get('format'):
                    print(f"    📄 Format: {book['format']}")
                if book.get('size'):
                    print(f"    💾 Size: {book['size']}")
                if book.get('language'):
                    print(f"    🌐 Language: {book['language']}")
                if book.get('publisher'):
                    print(f"    🏢 Publisher: {book['publisher']}")
    
    def interactive_mode(self):
        """Run the bot in interactive mode"""
        print(f"{Fore.GREEN}🤖 Library Bot - Interactive Mode{Style.RESET_ALL}")
        print(f"{Fore.CYAN}Type 'help' for available commands{Style.RESET_ALL}")
        
        if not self.initialize_browser():
            return
        
        try:
            while True:
                command = input(f"\n{Fore.BLUE}📚 LibBot> {Style.RESET_ALL}").strip()
                
                if not command:
                    continue
                
                parts = command.split(' ', 1)
                cmd = parts[0].lower()
                args = parts[1] if len(parts) > 1 else ""
                
                if cmd == 'help':
                    self._show_help()
                elif cmd == 'search':
                    if args:
                        results = self.search_books(args)
                        self.display_search_results(results)
                    else:
                        print(f"{Fore.RED}❌ Please provide a search query{Style.RESET_ALL}")
                elif cmd == 'login':
                    services = args.split() if args else None
                    self.login_to_services(services)
                elif cmd == 'download':
                    print(f"{Fore.YELLOW}⚠️ Use search first, then select a book to download{Style.RESET_ALL}")
                elif cmd == 'settings':
                    self._show_settings()
                elif cmd == 'quit' or cmd == 'exit':
                    break
                else:
                    print(f"{Fore.RED}❌ Unknown command: {cmd}{Style.RESET_ALL}")
                    print(f"{Fore.CYAN}Type 'help' for available commands{Style.RESET_ALL}")
        
        except KeyboardInterrupt:
            print(f"\n{Fore.YELLOW}👋 Goodbye!{Style.RESET_ALL}")
        finally:
            self.close_browser()
    
    def _show_help(self):
        """Show help information"""
        help_text = f"""
{Fore.GREEN}📚 Library Bot Commands:{Style.RESET_ALL}

{Fore.YELLOW}search <query>{Style.RESET_ALL}     - Search for books across all services
{Fore.YELLOW}login [services]{Style.RESET_ALL}   - Login to services (zlib, annas)
{Fore.YELLOW}settings{Style.RESET_ALL}           - Show current settings
{Fore.YELLOW}help{Style.RESET_ALL}               - Show this help message
{Fore.YELLOW}quit/exit{Style.RESET_ALL}          - Exit the bot

{Fore.CYAN}Examples:{Style.RESET_ALL}
  search python programming
  login zlib
  login zlib annas
"""
        print(help_text)
    
    def _show_settings(self):
        """Show current settings"""
        print(f"\n{Fore.MAGENTA}⚙️ Current Settings:{Style.RESET_ALL}")
        for key, value in self.config.settings.items():
            print(f"  {key}: {value}")

def main():
    parser = argparse.ArgumentParser(description='Library Bot - Download books from multiple sources')
    parser.add_argument('--search', '-s', help='Search query')
    parser.add_argument('--services', nargs='+', choices=['zlib', 'annas', 'libgen'], 
                       default=['zlib', 'annas', 'libgen'], help='Services to search')
    parser.add_argument('--limit', '-l', type=int, default=10, help='Limit search results')
    parser.add_argument('--interactive', '-i', action='store_true', help='Run in interactive mode')
    parser.add_argument('--login', nargs='*', choices=['zlib', 'annas'], help='Login to services')
    parser.add_argument('--save-credentials', nargs=2, metavar=('SERVICE', 'EMAIL'), 
                       help='Save credentials for a service')
    parser.add_argument('--delete-credentials', choices=['zlib', 'annas'], 
                       help='Delete saved credentials')
    parser.add_argument('--headless', action='store_true', help='Run browser in headless mode')
    
    args = parser.parse_args()
    
    bot = LibraryBot()
    
    # Set headless mode if specified
    if args.headless:
        bot.config.update_setting('headless', True)
    
    # Handle credential operations
    if args.save_credentials:
        service, email = args.save_credentials
        password = input(f"Enter password for {service}: ")
        bot.save_credentials(service, email, password)
        return
    
    if args.delete_credentials:
        bot.delete_credentials(args.delete_credentials)
        return
    
    # Run interactive mode
    if args.interactive:
        bot.interactive_mode()
        return
    
    # Handle login
    if args.login is not None:
        if not bot.initialize_browser():
            return
        try:
            services = args.login if args.login else ['zlib', 'annas']
            bot.login_to_services(services)
        finally:
            bot.close_browser()
        return
    
    # Handle search
    if args.search:
        if not bot.initialize_browser():
            return
        try:
            # Auto-login if credentials are available
            if bot.config.settings.get('auto_login'):
                bot.login_to_services(['zlib', 'annas'])
            
            results = bot.search_books(args.search, args.services, args.limit)
            bot.display_search_results(results)
            
            # Save results to file
            results_file = Path(bot.config.settings['download_dir']) / f"search_results_{int(time.time())}.json"
            with open(results_file, 'w', encoding='utf-8') as f:
                json.dump(results, f, indent=2, ensure_ascii=False)
            print(f"\n{Fore.GREEN}💾 Results saved to: {results_file}{Style.RESET_ALL}")
            
        finally:
            bot.close_browser()
        return
    
    # If no specific action, show help
    parser.print_help()

if __name__ == '__main__':
    main()