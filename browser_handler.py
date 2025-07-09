import time
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.firefox.service import Service as FirefoxService
from selenium.webdriver.edge.service import Service as EdgeService
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.firefox.options import Options as FirefoxOptions
from selenium.webdriver.edge.options import Options as EdgeOptions
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager
from webdriver_manager.firefox import GeckoDriverManager
from webdriver_manager.microsoft import EdgeChromiumDriverManager
from pathlib import Path
import logging

class BrowserHandler:
    def __init__(self, config):
        self.config = config
        self.driver = None
        self.wait = None
        self.logger = self._setup_logger()
    
    def _setup_logger(self):
        """Setup logging for browser operations"""
        logger = logging.getLogger('BrowserHandler')
        logger.setLevel(logging.INFO)
        
        if not logger.handlers:
            handler = logging.StreamHandler()
            formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
            handler.setFormatter(formatter)
            logger.addHandler(handler)
        
        return logger
    
    def start_browser(self):
        """Initialize and start the browser"""
        try:
            browser_type = self.config.settings['browser'].lower()
            
            if browser_type == 'chrome':
                self._start_chrome()
            elif browser_type == 'firefox':
                self._start_firefox()
            elif browser_type == 'edge':
                self._start_edge()
            else:
                raise ValueError(f"Unsupported browser: {browser_type}")
            
            self.wait = WebDriverWait(self.driver, self.config.settings['wait_timeout'])
            self.logger.info(f"Browser {browser_type} started successfully")
            
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to start browser: {e}")
            return False
    
    def _start_chrome(self):
        """Start Chrome browser"""
        options = ChromeOptions()
        
        # Download preferences
        prefs = {
            "download.default_directory": self.config.settings['download_dir'],
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "safebrowsing.enabled": True
        }
        options.add_experimental_option("prefs", prefs)
        
        # Additional options
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-gpu")
        options.add_argument("--window-size=1920,1080")
        
        if self.config.settings['headless']:
            options.add_argument("--headless")
        
        service = ChromeService(ChromeDriverManager().install())
        self.driver = webdriver.Chrome(service=service, options=options)
    
    def _start_firefox(self):
        """Start Firefox browser"""
        options = FirefoxOptions()
        
        # Download preferences
        options.set_preference("browser.download.folderList", 2)
        options.set_preference("browser.download.dir", self.config.settings['download_dir'])
        options.set_preference("browser.download.useDownloadDir", True)
        options.set_preference("browser.helperApps.neverAsk.saveToDisk", 
                             "application/pdf,application/epub+zip,application/x-mobipocket-ebook")
        
        if self.config.settings['headless']:
            options.add_argument("--headless")
        
        service = FirefoxService(GeckoDriverManager().install())
        self.driver = webdriver.Firefox(service=service, options=options)
    
    def _start_edge(self):
        """Start Edge browser"""
        options = EdgeOptions()
        
        # Download preferences
        prefs = {
            "download.default_directory": self.config.settings['download_dir'],
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "safebrowsing.enabled": True
        }
        options.add_experimental_option("prefs", prefs)
        
        if self.config.settings['headless']:
            options.add_argument("--headless")
        
        service = EdgeService(EdgeChromiumDriverManager().install())
        self.driver = webdriver.Edge(service=service, options=options)
    
    def navigate_to(self, url):
        """Navigate to a URL"""
        try:
            self.driver.get(url)
            self.logger.info(f"Navigated to: {url}")
            return True
        except Exception as e:
            self.logger.error(f"Failed to navigate to {url}: {e}")
            return False
    
    def wait_for_element(self, locator, timeout=None):
        """Wait for an element to be present"""
        try:
            if timeout:
                wait = WebDriverWait(self.driver, timeout)
            else:
                wait = self.wait
            
            element = wait.until(EC.presence_of_element_located(locator))
            return element
        except TimeoutException:
            self.logger.warning(f"Element not found: {locator}")
            return None
    
    def wait_for_clickable(self, locator, timeout=None):
        """Wait for an element to be clickable"""
        try:
            if timeout:
                wait = WebDriverWait(self.driver, timeout)
            else:
                wait = self.wait
            
            element = wait.until(EC.element_to_be_clickable(locator))
            return element
        except TimeoutException:
            self.logger.warning(f"Element not clickable: {locator}")
            return None
    
    def find_element_safe(self, locator):
        """Safely find an element without throwing exception"""
        try:
            return self.driver.find_element(*locator)
        except NoSuchElementException:
            return None
    
    def find_elements_safe(self, locator):
        """Safely find elements without throwing exception"""
        try:
            return self.driver.find_elements(*locator)
        except NoSuchElementException:
            return []
    
    def click_element(self, element):
        """Safely click an element"""
        try:
            self.driver.execute_script("arguments[0].click();", element)
            return True
        except Exception as e:
            self.logger.error(f"Failed to click element: {e}")
            return False
    
    def type_text(self, element, text, clear=True):
        """Type text into an element"""
        try:
            if clear:
                element.clear()
            element.send_keys(text)
            return True
        except Exception as e:
            self.logger.error(f"Failed to type text: {e}")
            return False
    
    def wait_for_download(self, filename=None, timeout=None):
        """Wait for a file to be downloaded"""
        if not timeout:
            timeout = self.config.settings['download_timeout']
        
        download_dir = Path(self.config.settings['download_dir'])
        start_time = time.time()
        
        while time.time() - start_time < timeout:
            if filename:
                if (download_dir / filename).exists():
                    return str(download_dir / filename)
            else:
                # Check for any new files
                files = list(download_dir.glob('*'))
                for file in files:
                    if file.is_file() and not file.name.endswith('.crdownload'):
                        # Check if file was created recently
                        if time.time() - file.stat().st_mtime < 10:
                            return str(file)
            
            time.sleep(1)
        
        self.logger.warning(f"Download timeout after {timeout} seconds")
        return None
    
    def scroll_to_element(self, element):
        """Scroll to an element"""
        try:
            self.driver.execute_script("arguments[0].scrollIntoView(true);", element)
            time.sleep(0.5)
            return True
        except Exception as e:
            self.logger.error(f"Failed to scroll to element: {e}")
            return False
    
    def get_page_source(self):
        """Get current page source"""
        return self.driver.page_source
    
    def take_screenshot(self, filename=None):
        """Take a screenshot"""
        try:
            if not filename:
                filename = f"screenshot_{int(time.time())}.png"
            
            screenshot_path = Path(self.config.settings['download_dir']) / filename
            self.driver.save_screenshot(str(screenshot_path))
            self.logger.info(f"Screenshot saved: {screenshot_path}")
            return str(screenshot_path)
        except Exception as e:
            self.logger.error(f"Failed to take screenshot: {e}")
            return None
    
    def close_browser(self):
        """Close the browser"""
        try:
            if self.driver:
                self.driver.quit()
                self.logger.info("Browser closed successfully")
        except Exception as e:
            self.logger.error(f"Error closing browser: {e}")
    
    def refresh_page(self):
        """Refresh the current page"""
        try:
            self.driver.refresh()
            time.sleep(2)
            return True
        except Exception as e:
            self.logger.error(f"Failed to refresh page: {e}")
            return False
    
    def switch_to_new_tab(self):
        """Switch to the newest tab"""
        try:
            self.driver.switch_to.window(self.driver.window_handles[-1])
            return True
        except Exception as e:
            self.logger.error(f"Failed to switch tab: {e}")
            return False
    
    def close_current_tab(self):
        """Close current tab and switch to previous"""
        try:
            if len(self.driver.window_handles) > 1:
                self.driver.close()
                self.driver.switch_to.window(self.driver.window_handles[-1])
            return True
        except Exception as e:
            self.logger.error(f"Failed to close tab: {e}")
            return False