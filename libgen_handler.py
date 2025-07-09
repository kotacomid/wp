import time
import json
import re
from pathlib import Path
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from bs4 import BeautifulSoup
import logging

class LibGenHandler:
    def __init__(self, browser_handler, config):
        self.browser = browser_handler
        self.config = config
        self.logger = self._setup_logger()
        
    def _setup_logger(self):
        """Setup logging for LibGen operations"""
        logger = logging.getLogger('LibGenHandler')
        logger.setLevel(logging.INFO)
        
        if not logger.handlers:
            handler = logging.StreamHandler()
            formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
            handler.setFormatter(formatter)
            logger.addHandler(handler)
        
        return logger
    
    def search(self, query, limit=10, search_type="title"):
        """Search for books on LibGen"""
        try:
            # Navigate to search page
            if not self.browser.navigate_to(self.config.urls['libgen']['search']):
                return []
            
            time.sleep(2)
            
            # Find search form
            search_input = self.browser.find_element_safe((By.NAME, "req"))
            if not search_input:
                search_input = self.browser.find_element_safe((By.ID, "searchform"))
            
            if not search_input:
                self.logger.error("Search input not found")
                return []
            
            # Set search type if dropdown exists
            search_type_select = self.browser.find_element_safe((By.NAME, "column"))
            if search_type_select:
                select = Select(search_type_select)
                try:
                    if search_type.lower() == "author":
                        select.select_by_value("author")
                    elif search_type.lower() == "isbn":
                        select.select_by_value("identifier")
                    else:
                        select.select_by_value("title")
                except:
                    pass
            
            # Enter search query
            self.browser.type_text(search_input, query)
            
            # Submit search
            search_button = self.browser.find_element_safe((By.XPATH, "//input[@type='submit']"))
            if search_button:
                self.browser.click_element(search_button)
            else:
                search_input.send_keys(Keys.RETURN)
            
            time.sleep(3)
            
            # Parse search results
            results = []
            soup = BeautifulSoup(self.browser.get_page_source(), 'html.parser')
            
            # LibGen usually displays results in a table
            table = soup.find('table', {'class': lambda x: x and 'c' in str(x).lower()})
            if not table:
                # Try alternative table selectors
                tables = soup.find_all('table')
                for t in tables:
                    if len(t.find_all('tr')) > 3:  # Likely results table
                        table = t
                        break
            
            if table:
                rows = table.find_all('tr')[1:]  # Skip header row
                
                for i, row in enumerate(rows[:limit]):
                    if i >= limit:
                        break
                    
                    try:
                        result = self._extract_book_info_from_row(row)
                        if result:
                            results.append(result)
                    except Exception as e:
                        self.logger.warning(f"Error extracting book info from row: {e}")
                        continue
            
            self.logger.info(f"Found {len(results)} books for query: {query}")
            return results
            
        except Exception as e:
            self.logger.error(f"Search error: {e}")
            return []
    
    def _extract_book_info_from_row(self, row):
        """Extract book information from table row"""
        try:
            cells = row.find_all(['td', 'th'])
            if len(cells) < 5:
                return None
            
            result = {}
            
            # LibGen table structure varies, but typically:
            # ID, Author, Title, Publisher, Year, Pages, Language, Size, Extension, Mirror links
            
            # Try to identify columns by content
            for i, cell in enumerate(cells):
                cell_text = cell.get_text(strip=True)
                
                # ID (usually first column, numeric)
                if i == 0 and cell_text.isdigit():
                    result['id'] = cell_text
                
                # Author (usually second column or contains author info)
                elif i == 1 or 'author' in cell.get('class', []):
                    if cell_text and len(cell_text) > 2:
                        result['author'] = cell_text
                
                # Title (usually third column or contains title info)
                elif i == 2 or 'title' in cell.get('class', []):
                    if cell_text and len(cell_text) > 2:
                        result['title'] = cell_text
                        # Look for download links in title cell
                        links = cell.find_all('a')
                        for link in links:
                            if link.get('href'):
                                result['detail_url'] = link['href']
                                if result['detail_url'].startswith('/'):
                                    result['detail_url'] = self.config.urls['libgen']['main'] + result['detail_url']
                
                # Publisher
                elif i == 3 or 'publisher' in cell.get('class', []):
                    if cell_text and len(cell_text) > 1:
                        result['publisher'] = cell_text
                
                # Year
                elif re.match(r'^\d{4}$', cell_text):
                    result['year'] = cell_text
                
                # Pages
                elif re.match(r'^\d+$', cell_text) and int(cell_text) > 10:
                    result['pages'] = cell_text
                
                # Language
                elif cell_text.lower() in ['english', 'russian', 'german', 'french', 'spanish', 'chinese']:
                    result['language'] = cell_text
                
                # Size
                elif re.search(r'\d+\.?\d*\s*(MB|KB|GB)', cell_text, re.IGNORECASE):
                    result['size'] = cell_text
                
                # Extension/Format
                elif cell_text.upper() in ['PDF', 'EPUB', 'MOBI', 'TXT', 'DOC', 'DOCX', 'AZW3']:
                    result['format'] = cell_text.upper()
                
                # Mirror links (usually last columns)
                elif 'mirror' in cell_text.lower() or cell.find('a'):
                    links = cell.find_all('a')
                    if links:
                        mirror_links = []
                        for link in links:
                            if link.get('href'):
                                href = link['href']
                                if not href.startswith('http'):
                                    href = self.config.urls['libgen']['main'] + href
                                mirror_links.append(href)
                        if mirror_links:
                            result['download_links'] = mirror_links
            
            return result if result.get('title') else None
            
        except Exception as e:
            self.logger.error(f"Error extracting book info from row: {e}")
            return None
    
    def download_book(self, book_info):
        """Download a book from LibGen"""
        try:
            download_links = book_info.get('download_links', [])
            if not download_links and book_info.get('detail_url'):
                # Get download links from detail page
                download_links = self._get_download_links(book_info['detail_url'])
            
            if not download_links:
                self.logger.error("No download links found")
                return None
            
            # Try each download link
            for download_url in download_links:
                try:
                    if not self.browser.navigate_to(download_url):
                        continue
                    
                    time.sleep(3)
                    
                    # Look for download button or direct download link
                    download_selectors = [
                        (By.XPATH, "//a[contains(@href, '.pdf') or contains(@href, '.epub')]"),
                        (By.XPATH, "//a[contains(text(), 'GET') or contains(text(), 'Download')]"),
                        (By.XPATH, "//a[contains(@href, 'get.php')]"),
                        (By.CLASS_NAME, "download"),
                        (By.XPATH, "//button[contains(text(), 'Download')]")
                    ]
                    
                    download_element = None
                    for selector in download_selectors:
                        download_element = self.browser.find_element_safe(selector)
                        if download_element:
                            break
                    
                    if download_element:
                        self.browser.scroll_to_element(download_element)
                        time.sleep(1)
                        
                        if self.browser.click_element(download_element):
                            # Wait for download to complete
                            filename = None
                            if book_info.get('title'):
                                safe_title = re.sub(r'[^\w\s-]', '', book_info['title'])
                                safe_title = re.sub(r'[-\s]+', '_', safe_title)
                                filename = f"{safe_title}.{book_info.get('format', 'pdf').lower()}"
                            
                            downloaded_file = self.browser.wait_for_download(filename)
                            
                            if downloaded_file:
                                self.logger.info(f"Successfully downloaded: {downloaded_file}")
                                
                                # Save metadata
                                if self.config.settings.get('save_metadata'):
                                    self._save_metadata(book_info, downloaded_file)
                                
                                return downloaded_file
                    
                except Exception as e:
                    self.logger.warning(f"Download attempt failed for {download_url}: {e}")
                    continue
            
            self.logger.error("All download attempts failed")
            return None
                
        except Exception as e:
            self.logger.error(f"Download error: {e}")
            return None
    
    def _get_download_links(self, detail_url):
        """Get download links from book detail page"""
        try:
            if not self.browser.navigate_to(detail_url):
                return []
            
            time.sleep(2)
            
            soup = BeautifulSoup(self.browser.get_page_source(), 'html.parser')
            
            download_links = []
            
            # Look for mirror links
            links = soup.find_all('a', href=True)
            for link in links:
                href = link['href']
                if ('mirror' in href.lower() or 
                    'download' in href.lower() or 
                    'get.php' in href or
                    '.pdf' in href or
                    '.epub' in href):
                    
                    if not href.startswith('http'):
                        href = self.config.urls['libgen']['main'] + href
                    download_links.append(href)
            
            return download_links
            
        except Exception as e:
            self.logger.error(f"Error getting download links: {e}")
            return []
    
    def _save_metadata(self, book_info, file_path):
        """Save book metadata"""
        try:
            metadata_format = self.config.settings.get('metadata_format', 'json')
            base_path = Path(file_path).with_suffix('')
            
            if metadata_format == 'json':
                metadata_path = f"{base_path}_metadata.json"
                with open(metadata_path, 'w', encoding='utf-8') as f:
                    json.dump(book_info, f, indent=2, ensure_ascii=False)
            
            elif metadata_format == 'txt':
                metadata_path = f"{base_path}_metadata.txt"
                with open(metadata_path, 'w', encoding='utf-8') as f:
                    for key, value in book_info.items():
                        if key != 'download_links':  # Skip download links in text format
                            f.write(f"{key.title()}: {value}\n")
            
            self.logger.info(f"Metadata saved: {metadata_path}")
            
        except Exception as e:
            self.logger.error(f"Error saving metadata: {e}")
    
    def get_book_details(self, book_url):
        """Get detailed information about a book"""
        try:
            if not self.browser.navigate_to(book_url):
                return None
            
            time.sleep(2)
            
            soup = BeautifulSoup(self.browser.get_page_source(), 'html.parser')
            
            details = {}
            
            # LibGen detail pages often have metadata in tables
            tables = soup.find_all('table')
            
            for table in tables:
                rows = table.find_all('tr')
                for row in rows:
                    cells = row.find_all(['td', 'th'])
                    if len(cells) >= 2:
                        key = cells[0].get_text(strip=True).lower()
                        value = cells[1].get_text(strip=True)
                        
                        if key and value:
                            details[key] = value
            
            # Also extract from text content
            text_content = soup.get_text()
            
            # Additional metadata patterns
            patterns = {
                'isbn': r'ISBN[:\s]+([0-9-]+)',
                'doi': r'DOI[:\s]+([^\s\n]+)',
                'edition': r'Edition[:\s]+([^\n,]+)',
                'description': r'Description[:\s]+([^\n]+)'
            }
            
            for key, pattern in patterns.items():
                match = re.search(pattern, text_content, re.IGNORECASE)
                if match:
                    details[key] = match.group(1).strip()
            
            return details
            
        except Exception as e:
            self.logger.error(f"Error getting book details: {e}")
            return None
    
    def search_by_isbn(self, isbn):
        """Search for books by ISBN"""
        try:
            return self.search(isbn, limit=5, search_type="isbn")
        except Exception as e:
            self.logger.error(f"ISBN search error: {e}")
            return []
    
    def search_by_author(self, author):
        """Search for books by author"""
        try:
            return self.search(author, limit=20, search_type="author")
        except Exception as e:
            self.logger.error(f"Author search error: {e}")
            return []
    
    def search_by_md5(self, md5_hash):
        """Search for a specific book by MD5 hash"""
        try:
            search_url = f"{self.config.urls['libgen']['main']}/book/index.php?md5={md5_hash}"
            if not self.browser.navigate_to(search_url):
                return None
            
            time.sleep(2)
            
            # Extract book information from the specific book page
            soup = BeautifulSoup(self.browser.get_page_source(), 'html.parser')
            
            book_info = {}
            
            # Extract metadata from the book page
            tables = soup.find_all('table')
            for table in tables:
                rows = table.find_all('tr')
                for row in rows:
                    cells = row.find_all(['td', 'th'])
                    if len(cells) >= 2:
                        key = cells[0].get_text(strip=True).lower()
                        value = cells[1].get_text(strip=True)
                        
                        if key and value:
                            book_info[key] = value
            
            return book_info if book_info else None
            
        except Exception as e:
            self.logger.error(f"MD5 search error: {e}")
            return None