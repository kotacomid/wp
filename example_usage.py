#!/usr/bin/env python3
"""
Example usage of Library Bot
This script demonstrates how to use the bot programmatically
"""

import time
from library_bot import LibraryBot

def example_search_and_display():
    """Example: Search for books and display results"""
    print("🔍 Example: Searching for Python programming books")
    
    bot = LibraryBot()
    
    try:
        # Initialize browser
        if not bot.initialize_browser():
            print("Failed to initialize browser")
            return
        
        # Search for books
        search_query = "python programming"
        results = bot.search_books(search_query, limit=5)
        
        # Display results
        bot.display_search_results(results)
        
    finally:
        bot.close_browser()

def example_with_login():
    """Example: Login and search with authentication"""
    print("🔑 Example: Login and authenticated search")
    
    bot = LibraryBot()
    
    try:
        # Initialize browser
        if not bot.initialize_browser():
            print("Failed to initialize browser")
            return
        
        # Login to services (requires saved credentials)
        login_results = bot.login_to_services(['zlib'])
        
        if login_results.get('zlib'):
            print("Successfully logged into Z-Library")
            
            # Search with authenticated access
            results = bot.search_books("machine learning", services=['zlib'], limit=3)
            bot.display_search_results(results)
        else:
            print("Login failed - continuing without authentication")
    
    finally:
        bot.close_browser()

def example_download_book():
    """Example: Search and download a book"""
    print("📥 Example: Search and download")
    
    bot = LibraryBot()
    
    try:
        # Initialize browser
        if not bot.initialize_browser():
            print("Failed to initialize browser")
            return
        
        # Search for a specific book
        results = bot.search_books("clean code", services=['libgen'], limit=3)
        
        # Download the first result if available
        if results.get('libgen') and len(results['libgen']) > 0:
            first_book = results['libgen'][0]
            print(f"Attempting to download: {first_book.get('title', 'Unknown')}")
            
            downloaded_file = bot.download_book(first_book, 'libgen')
            
            if downloaded_file:
                print(f"Successfully downloaded: {downloaded_file}")
            else:
                print("Download failed")
        else:
            print("No books found to download")
    
    finally:
        bot.close_browser()

def example_metadata_extraction():
    """Example: Extract detailed metadata"""
    print("📊 Example: Metadata extraction")
    
    bot = LibraryBot()
    
    try:
        # Initialize browser
        if not bot.initialize_browser():
            print("Failed to initialize browser")
            return
        
        # Search for books
        results = bot.search_books("data science", services=['annas'], limit=2)
        
        # Extract detailed metadata for each book
        if results.get('annas'):
            for i, book in enumerate(results['annas'][:2]):
                print(f"\n📖 Book {i+1} Metadata:")
                print(f"Title: {book.get('title', 'N/A')}")
                print(f"Author: {book.get('author', 'N/A')}")
                print(f"Year: {book.get('year', 'N/A')}")
                print(f"Format: {book.get('format', 'N/A')}")
                print(f"Size: {book.get('size', 'N/A')}")
                
                # Get detailed metadata if available
                if book.get('download_url') and hasattr(bot.annas, 'get_book_details'):
                    details = bot.annas.get_book_details(book['download_url'])
                    if details:
                        print("Additional details:", details)
    
    finally:
        bot.close_browser()

def example_configure_settings():
    """Example: Configure bot settings"""
    print("⚙️ Example: Configuring settings")
    
    bot = LibraryBot()
    
    # Show current settings
    print("Current settings:")
    for key, value in bot.config.settings.items():
        print(f"  {key}: {value}")
    
    # Update some settings
    print("\nUpdating settings...")
    bot.config.update_setting('headless', True)
    bot.config.update_setting('download_timeout', 600)
    bot.config.update_setting('metadata_format', 'txt')
    
    print("Settings updated!")
    print("New settings:")
    for key, value in bot.config.settings.items():
        print(f"  {key}: {value}")

def example_batch_search():
    """Example: Batch search multiple queries"""
    print("🔍 Example: Batch searching")
    
    bot = LibraryBot()
    
    search_queries = [
        "artificial intelligence",
        "web development",
        "data structures"
    ]
    
    try:
        # Initialize browser once
        if not bot.initialize_browser():
            print("Failed to initialize browser")
            return
        
        all_results = {}
        
        for query in search_queries:
            print(f"\nSearching for: {query}")
            results = bot.search_books(query, limit=3)
            all_results[query] = results
            
            # Brief summary
            total_books = sum(len(books) for books in results.values())
            print(f"Found {total_books} total books for '{query}'")
        
        # Display summary
        print("\n📋 Batch Search Summary:")
        for query, results in all_results.items():
            total = sum(len(books) for books in results.values())
            print(f"  {query}: {total} books found")
    
    finally:
        bot.close_browser()

def example_service_comparison():
    """Example: Compare results across services"""
    print("⚖️ Example: Service comparison")
    
    bot = LibraryBot()
    
    try:
        # Initialize browser
        if not bot.initialize_browser():
            print("Failed to initialize browser")
            return
        
        search_query = "programming"
        
        # Search each service individually
        services = ['zlib', 'annas', 'libgen']
        comparison = {}
        
        for service in services:
            print(f"\nSearching {service}...")
            results = bot.search_books(search_query, services=[service], limit=5)
            comparison[service] = results.get(service, [])
        
        # Compare results
        print(f"\n📊 Comparison for '{search_query}':")
        for service, books in comparison.items():
            print(f"\n{service.upper()}:")
            print(f"  📚 Total books: {len(books)}")
            
            if books:
                formats = [book.get('format') for book in books if book.get('format')]
                unique_formats = list(set(formats))
                print(f"  📄 Available formats: {', '.join(unique_formats) if unique_formats else 'N/A'}")
                
                years = [book.get('year') for book in books if book.get('year')]
                if years:
                    print(f"  📅 Year range: {min(years)} - {max(years)}")
    
    finally:
        bot.close_browser()

def main():
    """Run all examples"""
    print("🚀 Library Bot Examples")
    print("=" * 50)
    
    examples = [
        example_configure_settings,
        example_search_and_display,
        # example_with_login,  # Uncomment if you have saved credentials
        # example_download_book,  # Uncomment to test downloads
        example_metadata_extraction,
        example_batch_search,
        example_service_comparison
    ]
    
    for i, example_func in enumerate(examples, 1):
        print(f"\n🔹 Running Example {i}: {example_func.__name__}")
        print("-" * 40)
        
        try:
            example_func()
        except Exception as e:
            print(f"❌ Example failed: {e}")
        
        if i < len(examples):
            print("\n⏳ Waiting 3 seconds before next example...")
            time.sleep(3)
    
    print("\n✅ All examples completed!")

if __name__ == '__main__':
    main()