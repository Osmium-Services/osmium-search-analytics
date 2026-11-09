# Osmium Search Analytics service

Records what customers search for in the shop, shows the top and zero-result searches of the last 30 days, and adds a
search synonym in one click from a failed search. Repo `Osmium-Services/osmium-search-analytics`, v0.2.1<!--v:osmium-search-analytics-->, category shop.
Extracted 2026-10-10. See [[Core and Services]].

## What stays in core

The search itself: `api/search` (rate limit 20 per 10 seconds, 60-second result cache, `ShopSearchService`). Synonyms
are this service's: saved in its `search_synonyms` table and handed to the shop through the `search.synonyms` hook, so
with the service absent searches are not recorded and use no synonyms.

## What it does

- **Hook `search.performed`** (dispatch) fires on **every** search, including ones answered from the cache, with the term as
  typed, the result count and `dataSource`. The service normalises (trim, lower case, 100 characters) and upserts one row
  per term per day in `search_logs`. No visitor details are stored.
- **Admin** (Shop > Search Analytics): summary cards (total searches, unique terms, failed searches), a Top Searches list
  and a Failed Searches list. A failed search gets a spelling suggestion (within three typos of an existing synonym key,
  brand name or model name) and an **Add synonym** action that writes the term to the shop's synonyms file, which core
  search reads either way.
- The report and the synonym action are one feature: the synonym is what you do about a failed search, so they moved
  together.

## Not verified

Whether a saved synonym changes results (the endpoint caches for 60 seconds, and multi-word targets may not match).
