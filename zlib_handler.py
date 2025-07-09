import time
import json
import re
from pathlib import Path
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from bs4 import BeautifulSoup
import logging

class ZLibHandler:
    def __init__(self, browser_handler, config):
        self.browser = browser_handler
        self.config = config
        self.logger = self._setup_logger()
        self.logged_in = False
        
    def _setup_logger(self):
        """Setup logging for Z-Library operations"""
        logger = logging.getLogger('ZLibHandler')
        logger.setLevel(logging.INFO)
        
        if not logger.handlers:
            handler = logging.StreamHandler()
            formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
            handler.setFormatter(formatter)
            logger.addHandler(handler)
        
        return logger
    
    def login(self, username=None, password=None):
        """Login to Z-Library"""
        try:
            # Get credentials
            if not username or not password:
                credentials = self.config.get_credentials('zlib')
                if not credentials:
                    self.logger.error("No credentials found for Z-Library")
                    return False
                username = credentials.get('username')
                password = credentials.get('password')
            
            # Navigate to login page
            if not self.browser.navigate_to(self.config.urls['zlib']['login']):
                return False
            
            time.sleep(2)
            
            # Find and fill login form
            email_field = self.browser.wait_for_element((By.NAME, "email"), 10)
            if not email_field:
                email_field = self.browser.wait_for_element((By.ID, "email"), 10)
            
            if not email_field:
                self.logger.error("Email field not found")
                return False
            
            password_field = self.browser.find_element_safe((By.NAME, "password"))
            if not password_field:
                password_field = self.browser.find_element_safe((By.ID, "password"))
            
            if not password_field:
                self.logger.error("Password field not found")
                return False
            
            # Fill credentials
            self.browser.type_text(email_field, username)
            self.browser.type_text(password_field, password)
            
            # Submit form
            login_button = self.browser.find_element_safe((By.XPATH, "//button[@type='submit']"))
            if not login_button:
                login_button = self.browser.find_element_safe((By.XPATH, "//input[@type='submit']"))
            
            if login_button:
                self.browser.click_element(login_button)
            else:
                password_field.send_keys(Keys.RETURN)
            
            # Wait for login to complete
            time.sleep(3)
            
            # Check if login was successful
            current_url = self.browser.driver.current_url
            if "login" not in current_url.lower():
                self.logged_in = True
                self.logger.info("Successfully logged in to Z-Library")
                return True
            else:
                self.logger.error("Login failed - still on login page")
                return False
                
        except Exception as e:
            self.logger.error(f"Login error: {e}")
            return False
    
    def search(self, query, limit=10):
        """Search for books on Z-Library"""
        try:
            # Navigate to search page
            search_url = f"{self.config.urls['zlib']['search']}{query}"
            if not self.browser.navigate_to(search_url):
                return []
            
            time.sleep(3)
            
            # Parse search results
            results = []
            soup = BeautifulSoup(self.browser.get_page_source(), 'html.parser')
            
            # Find book containers (adjust selectors based on current site structure)
            book_elements = soup.find_all(['div', 'article'], class_=re.compile(r'book|item|result'))
            
            if not book_elements:
                # Try alternative selectors
                book_elements = soup.find_all('div', {'class': lambda x: x and ('book' in str(x).lower() or 'item' in str(x).lower())})
            
            for i, book in enumerate(book_elements[:limit]):
                if i >= limit:
                    break
                
                try:
                    result = self._extract_book_info(book)
                    if result:
                        results.append(result)
                except Exception as e:
                    self.logger.warning(f"Error extracting book info: {e}")
                    continue
            
            self.logger.info(f"Found {len(results)} books for query: {query}")
            return results
            
        except Exception as e:
            self.logger.error(f"Search error: {e}")
            return []
    
    def _extract_book_info(self, book_element):
        """Extract book information from search result element"""
        try:
            result = {}
            
            # Title
            title_elem = book_element.find(['h3', 'h2', 'h4'], class_=re.compile(r'title|name'))
            if not title_elem:
                title_elem = book_element.find('a', title=True)
            
            if title_elem:
                result['title'] = title_elem.get_text(strip=True)
                # Get download link
                link_elem = title_elem.find('a') if title_elem.name != 'a' else title_elem
                if link_elem and link_elem.get('href'):
                    result['download_url'] = link_elem['href']
                    if result['download_url'].startswith('/'):
                        result['download_url'] = self.config.urls['zlib']['main'] + result['download_url']
            
            # Author
            author_elem = book_element.find(['div', 'span'], class_=re.compile(r'author'))
            if author_elem:
                result['author'] = author_elem.get_text(strip=True)
            
            # Year
            year_elem = book_element.find(['div', 'span'], class_=re.compile(r'year|date'))
            if year_elem:
                year_text = year_elem.get_text(strip=True)
                year_match = re.search(r'\d{4}', year_text)
                if year_match:
                    result['year'] = year_match.group()
            
            # Format/Extension
            format_elem = book_element.find(['div', 'span'], class_=re.compile(r'format|ext|type'))
            if format_elem:
                result['format'] = format_elem.get_text(strip=True).upper()
            
            # Size
            size_elem = book_element.find(['div', 'span'], class_=re.compile(r'size|file'))
            if size_elem:
                result['size'] = size_elem.get_text(strip=True)
            
            # Language
            lang_elem = book_element.find(['div', 'span'], class_=re.compile(r'lang'))
            if lang_elem:
                result['language'] = lang_elem.get_text(strip=True)
            
            return result if result.get('title') else None
            
        except Exception as e:
            self.logger.error(f"Error extracting book info: {e}")
            return None
    
    def download_book(self, book_info):
        """Download a book from Z-Library"""
        try:
            if not book_info.get('download_url'):
                self.logger.error("No download URL provided")
                return None
            
            # Navigate to book page
            if not self.browser.navigate_to(book_info['download_url']):
                return None
            
            time.sleep(3)
            
            # Look for download button
            download_selectors = [
                (By.XPATH, "//a[contains(@class, 'download') or contains(text(), 'Download')]"),
                (By.CLASS_NAME, "download-button"),
                (By.ID, "download"),
                (By.XPATH, "//button[contains(text(), 'Download')]"),
                (By.XPATH, "//a[contains(@href, 'download')]")
            ]
            
            download_button = None
            for selector in download_selectors:
                download_button = self.browser.find_element_safe(selector)
                if download_button:
                    break
            
            if not download_button:
                self.logger.error("Download button not found")
                return None
            
            # Click download button
            self.browser.scroll_to_element(download_button)
            time.sleep(1)
            
            if not self.browser.click_element(download_button):
                return None
            
            # Wait for download to complete
            filename = None
            if book_info.get('title'):
                # Try to predict filename
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
            else:
                self.logger.error("Download failed or timed out")
                return None
                
        except Exception as e:
            self.logger.error(f"Download error: {e}")
            return None
    
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
            
            # Extract detailed information
            title_elem = soup.find(['h1', 'h2'], class_=re.compile(r'title|book'))
            if title_elem:
                details['title'] = title_elem.get_text(strip=True)
            
            # Look for metadata table or list
            metadata_container = soup.find(['table', 'dl', 'div'], class_=re.compile(r'meta|info|detail'))
            
            if metadata_container:
                # Extract from table
                if metadata_container.name == 'table':
                    rows = metadata_container.find_all('tr')
                    for row in rows:
                        cells = row.find_all(['td', 'th'])
                        if len(cells) >= 2:
                            key = cells[0].get_text(strip=True).lower()
                            value = cells[1].get_text(strip=True)
                            details[key] = value
                
                # Extract from definition list
                elif metadata_container.name == 'dl':
                    terms = metadata_container.find_all('dt')
                    definitions = metadata_container.find_all('dd')
                    for term, definition in zip(terms, definitions):
                        key = term.get_text(strip=True).lower()
                        value = definition.get_text(strip=True)
                        details[key] = value
            
            return details
            
        except Exception as e:
            self.logger.error(f"Error getting book details: {e}")
            return None