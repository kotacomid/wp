# Kotacom AI – Plugin Road-Map

> Last updated: 07 July 2025

---

## 0. Baseline (Done – v1.3.0)

* AI content generator (single + bulk) with template system
* Background queue (Action Scheduler)
* Keyword DB + tag filters
* Multi-provider text generation (Google AI, OpenAI, Groq, …)
* Gutenberg blocks + short-codes (`ai_content`, `ai_list`, `ai_template`, …)
* Multi-source image generator + `[ai_image]` shortcode (OpenAI / Unsplash / Replicate)
* One-click **Hero Image** in post list
* Content Refresh page (bulk rewrite via prompt)
* Smart scheduling field (`datetime-local`) in generator
* Settings page with API keys (incl. Unsplash)
* CHANGELOG & docs up-to-date

---

## 1. Sprint 1 – Logging & Dashboard (**Planned**)

| # | Feature | Detail |
|---|---------|--------|
| 1 | Generation log table | `wp_kotacom_ai_logs` with indexes + Logger class |
| 2 | Insert hooks | Log every generate / image / refresh call |
| 3 | Log retention | Daily cron – keep 90 days |
| 4 | Logs WP-Admin page | `WP_List_Table` – filters, search, CSV export, pagination |
| 5 | Live stream widget | Ajax poll last 20 logs |
| 6 | Cost analytics | Aggregate cost / day / provider graph |

**Duration:** 1 week  
**Outcome:** visibility into usage, failures, spend.

---

## 2. Sprint 2 – Template-Based Content Refresh

1. CPT `refresh_template` (or extend existing)  
2. Variables & placeholders (`{current_content}` etc.)  
3. Template picker in Refresh page  
4. Option "Update modified date"  
5. Queue >100 posts (chunk into 20)  
6. Progress bar + per-item status  

---

## 3. Sprint 3 – Standalone Dashboard (Front-End)

* React/Vite SPA embedded via `[ai_dashboard]` shortcode  
* JWT-secure REST API endpoints  
* Widgets: Today cost, queue, recent errors, success-rate  
* PWA install option

---

## 4. Sprint 4 – Monetisation & Credits

* Credit system (WooCommerce product)  
* Quota middleware on generation endpoints  
* Stripe webhook listener  
* Usage meter in dashboard

---

## 5. Backlog / Nice-to-Have

* AI internal-link builder  
* FAQ / PAA harvesting + answer block  
* EEAT & fact-check module with citation list  
* Multilingual generation pipeline (WPML/Polylang)  
* Chrome extension "Generate with site context"  
* Prompt marketplace (share/import)  
* Accessibility & readability scoring

---

## Dev Standards

* Namespace prefix `kotacom_ai_`  
* Coding standard: WP Coding Standards via PHPCS  
* All external calls timeout ≤ 60 s  
* Escape & sanitize everything  
* DB schema migrations via `dbDelta`  
* Tests: PHPUnit + Playwright E2E  

---

_This roadmap is iterative; reorder tasks based on user feedback & API changes._