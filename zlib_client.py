import os
import pickle
import re
import time
from pathlib import Path
from typing import Dict, List, Optional, Tuple

import requests
from bs4 import BeautifulSoup

# ---------------------------------------------------------------------------
# Z-Library helper
# ---------------------------------------------------------------------------
# IMPORTANT NOTE: Z-Library domains rotate very often due to ongoing
# takedown / block efforts.  Provide the current working domain (clearnet or
# personal) when instantiating the client, e.g. "https://singlelogin.re" or
# your personal Hydra domain.  All endpoints are resolved relative to it.
# ---------------------------------------------------------------------------

DEFAULT_HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/119.0 Safari/537.36"
    )
}


class ZlibClient:
    """Minimal Z-Library scraper.

    Features implemented (HTML-scraping, no official API):
    1. login_zlib()        -> obtains session cookies and stores them on disk
    2. search_zlib(query) -> returns list[dict] basic metadata per hit
    3. download_zlib(id)  -> downloads file to disk, returns (meta, path)

    COOKIE JAR is pickled to *~/.cache/zlib_client_cookies.pkl* by default so
    you don't have to log in every run.  Use ``force_relogin`` to ignore it.
    """

    def __init__(
        self,
        base_url: str,
        email: str,
        password: str,
        cookies_path: Optional[Path] = None,
    ) -> None:
        if base_url.endswith("/"):
            base_url = base_url.rstrip("/")
        self.base_url = base_url
        self.email = email
        self.password = password
        self.session = requests.Session()
        self.session.headers.update(DEFAULT_HEADERS)
        self.cookies_path = (
            cookies_path
            if cookies_path
            else Path.home() / ".cache" / "zlib_client_cookies.pkl"
        )
        self.cookies_path.parent.mkdir(parents=True, exist_ok=True)

    # ---------------------------------------------------------------------
    # Public helpers
    # ---------------------------------------------------------------------

    def login_zlib(self, force_relogin: bool = False) -> bool:
        """Log in (or re-use cached cookies).

        Returns True if logged-in session is valid.
        """
        # 1. Try cookie jar reuse -------------------------------------------------
        if not force_relogin and self._load_cookies():
            if self._is_authenticated():
                return True
            # Stale cookies – discard
        # 2. Perform fresh login --------------------------------------------------
        login_page = self.session.get(f"{self.base_url}/eapi/user/login")
        if login_page.status_code >= 400:
            raise RuntimeError(
                f"Unable to reach login endpoint ({login_page.status_code})"
            )
        # The JS frontend uses JSON POST; replicate minimal payload
        payload = {"email": self.email, "password": self.password}
        resp = self.session.post(
            f"{self.base_url}/eapi/user/login",
            json=payload,
        )
        if resp.status_code != 200 or resp.json().get("error"):
            raise RuntimeError(
                f"Login failed: {resp.text[:200]}…"
            )
        # Cookies now in session
        self._save_cookies()
        return True

    def search_zlib(self, query: str, max_results: int = 10) -> List[Dict]:
        """Search books.  Returns list of dicts (basic metadata)."""
        url = f"{self.base_url}/s/{requests.utils.quote(query)}"
        r = self.session.get(url, timeout=30)
        r.raise_for_status()
        soup = BeautifulSoup(r.text, "html.parser")
        rows = soup.select("div.resItemBox")
        results: List[Dict] = []
        for row in rows[:max_results]:
            # Title
            title_tag = row.select_one("h3[itemprop='name'] a")
            if not title_tag:
                continue
            title = title_tag.get_text(strip=True)
            link = title_tag["href"]
            # id is trailing part after '/book/'
            m = re.search(r"/book/(\d+)", link)
            book_id = m.group(1) if m else None
            # Author, year, ext
            author = (
                row.select_one("div.bookAuthors span")
                or row.select_one("div.bookAuthors")
            )
            author_text = author.get_text(" ", strip=True) if author else ""
            year_tag = row.select_one("div.bookDetails span.year")
            year_text = year_tag.get_text(strip=True) if year_tag else ""
            fmt_tag = row.select_one("span.bookFormat")
            ext = fmt_tag.get_text(strip=True) if fmt_tag else ""
            results.append(
                {
                    "id": book_id,
                    "title": title,
                    "author": author_text,
                    "year": year_text,
                    "ext": ext,
                    "detail_url": link if link.startswith("http") else f"{self.base_url}{link}",
                }
            )
        return results

    def download_zlib(self, book_id: str, dest_dir: Path = Path("downloads")) -> Tuple[Dict, Path]:
        """Download a single book by numeric id.  Returns metadata, file path."""
        dest_dir.mkdir(parents=True, exist_ok=True)
        # 1. Get book detail page to find the download link
        detail_url = f"{self.base_url}/book/{book_id}"
        detail_page = self.session.get(detail_url, timeout=30)
        detail_page.raise_for_status()
        soup = BeautifulSoup(detail_page.text, "html.parser")
        dl_btn = soup.find("a", string=re.compile("Download", re.I))
        if not dl_btn:
            raise RuntimeError("Unable to locate download button")
        dl_href = dl_btn["href"]
        if not dl_href.startswith("http"):
            dl_href = f"{self.base_url}{dl_href}"
        # 2. Hit the intermediate page to get final link
        intermediate = self.session.get(dl_href, timeout=30)
        intermediate.raise_for_status()
        m = re.search(r'href="(https?://[^"]+)"[^>]*>\s*GET\s*', intermediate.text)
        final_url = m.group(1) if m else dl_href  # fallback
        # 3. Stream download
        filename = self._guess_filename(final_url, soup)
        local_path = dest_dir / filename
        with self.session.get(final_url, stream=True, timeout=120) as r:
            r.raise_for_status()
            with open(local_path, "wb") as fh:
                for chunk in r.iter_content(chunk_size=8192):
                    if chunk:
                        fh.write(chunk)
        meta = {
            "id": book_id,
            "title": soup.select_one("h1[itemprop='name']").get_text(strip=True) if soup.select_one("h1[itemprop='name']") else "",
            "authors": [a.get_text(strip=True) for a in soup.select("a[itemprop='author']")],
            "file": str(local_path),
        }
        return meta, local_path

    # ------------------------------------------------------------------
    # Internal helpers
    # ------------------------------------------------------------------

    def _is_authenticated(self) -> bool:
        """Quick check by visiting /my-books."""
        r = self.session.get(f"{self.base_url}/my-books", allow_redirects=False)
        return r.status_code == 200

    def _save_cookies(self) -> None:
        with open(self.cookies_path, "wb") as fh:
            pickle.dump(self.session.cookies, fh)

    def _load_cookies(self) -> bool:
        if not self.cookies_path.exists():
            return False
        try:
            with open(self.cookies_path, "rb") as fh:
                cookies = pickle.load(fh)
            self.session.cookies.update(cookies)
            return True
        except Exception:
            return False

    @staticmethod
    def _guess_filename(url: str, soup: BeautifulSoup) -> str:
        # Try Content-Disposition? Not available; fallback to title.ext
        ext = Path(url.split("?")[0]).suffix or ".bin"
        title_tag = soup.select_one("h1[itemprop='name']")
        name_stub = title_tag.get_text(strip=True) if title_tag else str(int(time.time()))
        safe = re.sub(r"[^\w\d\-_ ]+", "_", name_stub)[:120]
        return f"{safe}{ext}"