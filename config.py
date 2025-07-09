import os
import json
from pathlib import Path
from cryptography.fernet import Fernet

class Config:
    def __init__(self):
        self.config_dir = Path.home() / '.library_bot'
        self.config_dir.mkdir(exist_ok=True)
        
        self.credentials_file = self.config_dir / 'credentials.json'
        self.key_file = self.config_dir / 'key.key'
        self.settings_file = self.config_dir / 'settings.json'
        
        self.default_settings = {
            'download_dir': str(Path.home() / 'Downloads' / 'LibraryBot'),
            'browser': 'chrome',  # chrome, firefox, edge
            'headless': False,
            'wait_timeout': 10,
            'download_timeout': 300,
            'max_retries': 3,
            'auto_login': True,
            'save_metadata': True,
            'metadata_format': 'json'  # json, csv, txt
        }
        
        # URLs for different services
        self.urls = {
            'zlib': {
                'main': 'https://z-lib.gs',
                'login': 'https://z-lib.gs/login',
                'search': 'https://z-lib.gs/s/'
            },
            'annas': {
                'main': 'https://annas-archive.org',
                'search': 'https://annas-archive.org/search'
            },
            'libgen': {
                'main': 'http://libgen.li',
                'search': 'http://libgen.li/index.php'
            }
        }
        
        self._init_encryption()
        self.load_settings()
    
    def _init_encryption(self):
        """Initialize encryption for storing credentials"""
        if not self.key_file.exists():
            key = Fernet.generate_key()
            with open(self.key_file, 'wb') as f:
                f.write(key)
        
        with open(self.key_file, 'rb') as f:
            self.cipher_suite = Fernet(f.read())
    
    def save_credentials(self, service, username, password):
        """Save encrypted credentials for a service"""
        credentials = {}
        if self.credentials_file.exists():
            credentials = self.load_credentials()
        
        encrypted_data = {
            'username': self.cipher_suite.encrypt(username.encode()).decode(),
            'password': self.cipher_suite.encrypt(password.encode()).decode()
        }
        
        credentials[service] = encrypted_data
        
        with open(self.credentials_file, 'w') as f:
            json.dump(credentials, f, indent=2)
    
    def load_credentials(self):
        """Load and decrypt credentials"""
        if not self.credentials_file.exists():
            return {}
        
        with open(self.credentials_file, 'r') as f:
            encrypted_credentials = json.load(f)
        
        credentials = {}
        for service, data in encrypted_credentials.items():
            try:
                credentials[service] = {
                    'username': self.cipher_suite.decrypt(data['username'].encode()).decode(),
                    'password': self.cipher_suite.decrypt(data['password'].encode()).decode()
                }
            except Exception as e:
                print(f"Error decrypting credentials for {service}: {e}")
        
        return credentials
    
    def get_credentials(self, service):
        """Get credentials for a specific service"""
        credentials = self.load_credentials()
        return credentials.get(service, {})
    
    def save_settings(self):
        """Save current settings to file"""
        with open(self.settings_file, 'w') as f:
            json.dump(self.settings, f, indent=2)
    
    def load_settings(self):
        """Load settings from file or use defaults"""
        if self.settings_file.exists():
            with open(self.settings_file, 'r') as f:
                saved_settings = json.load(f)
                self.settings = {**self.default_settings, **saved_settings}
        else:
            self.settings = self.default_settings.copy()
            self.save_settings()
        
        # Ensure download directory exists
        Path(self.settings['download_dir']).mkdir(parents=True, exist_ok=True)
    
    def update_setting(self, key, value):
        """Update a specific setting"""
        self.settings[key] = value
        self.save_settings()
    
    def delete_credentials(self, service):
        """Delete credentials for a service"""
        credentials = self.load_credentials()
        if service in credentials:
            del credentials[service]
            with open(self.credentials_file, 'w') as f:
                json.dump(credentials, f, indent=2)
            return True
        return False