import time
import json
import re
from pathlib import Path
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from bs4 import BeautifulSoup
import logging

class AnnasHandler:
    def __init__(self, browser_handler, config):
        self.browser = browser_handler
        self.config = config
        self.logger = self._setup_logger()
        self.logged_in = False
        
    def _setup_logger(self):
        """Setup logging for Anna's Archive operations"""
        logger = logging.getLogger('AnnasHandler')
        logger.setLevel(logging.INFO)
        
        if not logger.handlers:
            handler = logging.StreamHandler()
            formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
            handler.setFormatter(formatter)
            logger.addHandler(handler)
        
        return logger
    
    def login(self, username=None, password=None):
        """Login to Anna's Archive (if needed)"""
        try:
            # Anna's Archive typically doesn't require login for basic usage
            # but we'll implement it in case it's needed
            credentials = self.config.get_credentials('annas')
            if not credentials and not (username and password):
                self.logger.info("No credentials found for Anna's Archive - proceeding without login")
                return True
            
            if not username or not password:
                username = credentials.get('username')
                password = credentials.get('password')
            
            # Navigate to main page first
            if not self.browser.navigate_to(self.config.urls['annas']['main']):
                return False
            
            time.sleep(2)
            
            # Look for login link
            login_link = self.browser.find_element_safe((By.XPATH, "//a[contains(text(), 'Login') or contains(text(), 'Sign')]"))
            
            if login_link:
                self.browser.click_element(login_link)
                time.sleep(2)
                
                # Fill login form if found
                email_field = self.browser.find_element_safe((By.NAME, "email"))
                if not email_field:
                    email_field = self.browser.find_element_safe((By.ID, "email"))
                
                password_field = self.browser.find_element_safe((By.NAME, "password"))
                if not password_field:
                    password_field = self.browser.find_element_safe((By.ID, "password"))
                
                if email_field and password_field:
                    self.browser.type_text(email_field, username)
                    self.browser.type_text(password_field, password)
                    
                    login_button = self.browser.find_element_safe((By.XPATH, "//button[@type='submit']"))
                    if login_button:
                        self.browser.click_element(login_button)
                    else:
                        password_field.send_keys(Keys.RETURN)
                    
                    time.sleep(3)
                    self.logged_in = True
                    self.logger.info("Successfully logged in to Anna's Archive")
                    return True
            
            # If no login found, proceed without login
            self.logger.info("No login required for Anna's Archive")
            return True
                
        except Exception as e:
            self.logger.error(f"Login error: {e}")
            return False
    
    def search(self, query, limit=10):
        """Search for books on Anna's Archive"""
        try:
            # Navigate to search page
            search_url = f"{self.config.urls['annas']['search']}?q={query}"
            if not self.browser.navigate_to(search_url):
                return []
            
            time.sleep(3)
            
            # Parse search results
            results = []
            soup = BeautifulSoup(self.browser.get_page_source(), 'html.parser')
            
            # Find book containers - Anna's Archive has specific structure
            book_elements = soup.find_all('div', class_=re.compile(r'js-scroll-hidden|search-result'))
            
            if not book_elements:
                # Try alternative selectors
                book_elements = soup.find_all(['div', 'article'], {'class': lambda x: x and 'result' in str(x).lower()})
            
            if not book_elements:
                # Try more general approach
                book_elements = soup.select('div[class*="border"]')[:limit]
            
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
            
            # Title - Anna's Archive usually has titles in specific structure
            title_elem = book_element.find(['h3', 'h2', 'div'], class_=re.compile(r'title|name'))
            if not title_elem:
                title_elem = book_element.find('a', title=True)
            if not title_elem:
                # Try more specific selectors for Anna's Archive
                title_elem = book_element.find('div', text=re.compile(r'[A-Za-z]'))
            
            if title_elem:
                result['title'] = title_elem.get_text(strip=True)
                # Get download link
                link_elem = title_elem.find('a') if title_elem.name != 'a' else title_elem
                if not link_elem:
                    link_elem = book_element.find('a')
                
                if link_elem and link_elem.get('href'):
                    result['download_url'] = link_elem['href']
                    if result['download_url'].startswith('/'):
                        result['download_url'] = self.config.urls['annas']['main'] + result['download_url']
            
            # Extract metadata from text content
            text_content = book_element.get_text()
            
            # Author pattern
            author_match = re.search(r'by\s+([^,\n]+)', text_content, re.IGNORECASE)
            if author_match:
                result['author'] = author_match.group(1).strip()
            
            # Year pattern
            year_match = re.search(r'\b(19|20)\d{2}\b', text_content)
            if year_match:
                result['year'] = year_match.group()
            
            # Format pattern
            format_match = re.search(r'\b(PDF|EPUB|MOBI|AZW3|TXT|DOC|DOCX)\b', text_content, re.IGNORECASE)
            if format_match:
                result['format'] = format_match.group().upper()
            
            # Size pattern
            size_match = re.search(r'\b\d+(?:\.\d+)?\s*(MB|KB|GB)\b', text_content, re.IGNORECASE)
            if size_match:
                result['size'] = size_match.group()
            
            # Language pattern
            lang_match = re.search(r'\b(English|Spanish|French|German|Russian|Chinese|Japanese)\b', text_content, re.IGNORECASE)
            if lang_match:
                result['language'] = lang_match.group()
            
            # Publisher pattern
            publisher_match = re.search(r'Publisher[:\s]+([^,\n]+)', text_content, re.IGNORECASE)
            if publisher_match:
                result['publisher'] = publisher_match.group(1).strip()
            
            return result if result.get('title') else None
            
        except Exception as e:
            self.logger.error(f"Error extracting book info: {e}")
            return None
    
    def download_book(self, book_info):
        """Download a book from Anna's Archive"""
        try:
            if not book_info.get('download_url'):
                self.logger.error("No download URL provided")
                return None
            
            # Navigate to book page
            if not self.browser.navigate_to(book_info['download_url']):
                return None
            
            time.sleep(3)
            
            # Anna's Archive has multiple download mirrors
            # Look for download links
            download_selectors = [
                (By.XPATH, "//a[contains(@href, 'download') or contains(text(), 'Download')]"),
                (By.XPATH, "//a[contains(@href, 'mirror')]"),
                (By.XPATH, "//button[contains(text(), 'Download')]"),
                (By.CLASS_NAME, "js-download-link"),
                (By.XPATH, "//a[contains(@class, 'download')]")
            ]
            
            download_links = []
            for selector in download_selectors:
                elements = self.browser.find_elements_safe(selector)
                download_links.extend(elements)
            
            if not download_links:
                self.logger.error("No download links found")
                return None
            
            # Try each download link until one works
            for download_link in download_links[:3]:  # Try first 3 links
                try:
                    self.browser.scroll_to_element(download_link)
                    time.sleep(1)
                    
                    if self.browser.click_element(download_link):
                        # Check if we need to wait for redirect or if download started
                        time.sleep(3)
                        
                        # Sometimes Anna's Archive redirects to mirror sites
                        current_url = self.browser.driver.current_url
                        if 'annas-archive.org' not in current_url:
                            # We're on a mirror site, look for download button there
                            mirror_download = self.browser.find_element_safe((By.XPATH, "//a[contains(@href, '.pdf') or contains(@href, '.epub') or contains(text(), 'Download')]"))
                            if mirror_download:
                                self.browser.click_element(mirror_download)
                        
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
                    self.logger.warning(f"Download attempt failed: {e}")
                    continue
            
            self.logger.error("All download attempts failed")
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
            
            # Extract detailed information from Anna's Archive structure
            title_elem = soup.find(['h1', 'h2'])
            if title_elem:
                details['title'] = title_elem.get_text(strip=True)
            
            # Look for metadata in various containers
            metadata_containers = soup.find_all(['div', 'section'], class_=re.compile(r'meta|info|detail|content'))
            
            for container in metadata_containers:
                text = container.get_text()
                
                # Extract various metadata fields
                patterns = {
                    'author': r'Author[:\s]+([^\n,]+)',
                    'isbn': r'ISBN[:\s]+([0-9-]+)',
                    'publisher': r'Publisher[:\s]+([^\n,]+)',
                    'year': r'Year[:\s]+(\d{4})',
                    'pages': r'Pages[:\s]+(\d+)',
                    'language': r'Language[:\s]+([^\n,]+)',
                    'description': r'Description[:\s]+([^\n]+)'
                }
                
                for key, pattern in patterns.items():
                    match = re.search(pattern, text, re.IGNORECASE)
                    if match:
                        details[key] = match.group(1).strip()
            
            return details
            
        except Exception as e:
            self.logger.error(f"Error getting book details: {e}")
            return None
    
    def search_by_isbn(self, isbn):
        """Search for books by ISBN"""
        try:
            search_url = f"{self.config.urls['annas']['search']}?q=isbn:{isbn}"
            return self.search(isbn, limit=5)
        except Exception as e:
            self.logger.error(f"ISBN search error: {e}")
            return []
    
    def search_by_author(self, author):
        """Search for books by author"""
        try:
            search_url = f"{self.config.urls['annas']['search']}?q=author:{author}"
            return self.search(f"author:{author}", limit=20)
        except Exception as e:
            self.logger.error(f"Author search error: {e}")
            return []