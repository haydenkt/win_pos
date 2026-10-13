# Mobile / offline Site Survey

Only `/site_survey/mobile/` is controlled by the service worker. No invoice,
payment, login, customer API response or authenticated photo is cached by it.
The public shell contains no customer data. Device drafts and the explicitly
downloaded customer list are stored in IndexedDB and are not encrypted.
Use a personal locked phone, not a shared device. POS logout does not erase
device drafts. Browser storage eviction/clearing can remove unsynced work;
export private backups regularly.

## Deployment

Apply `schema.sql` once using a database migration account, before opening the
updated survey detail page. It only adds sync receipts and photo tables.
Keep these tables in database backups alongside the existing survey tables.
Photos are stored in the database, not in publicly accessible upload folders.
Verify hosting request limits allow at least 6 MB and database packet limits
allow a 600 KB JPEG. The frontend resizes photos to 1600 px / <= 600 KB, up to six.

Open `site_survey/mobile/index.html` on the SAME origin as the POS login. Sign in
with site_survey_manage permission, return and press Connect. This downloads
the customer list to the device. Add to Home Screen / install in the browser.
Connect again inside the installed app; browser and installed-app storage may
be separate. Test opening the installed app in airplane mode before real use.

Phones require trusted HTTPS for offline reopening. Plain HTTP LAN addresses
do not support service workers. Desktop localhost is a development exception.
Do not bypass certificate warnings or expose PHP's development server publicly.
A trusted local HTTPS setup or HTTPS hosting is needed before field deployment.
The computer still needs internet to reach TiDB when syncing.

Use the same stable origin: changing hostname or port creates separate browser
storage. Export/import can transfer drafts; reconnect using the same POS account.
Backups contain personal data and photos. Import does not overwrite existing IDs.

## Workflow and safety

Create a survey, enter millimetres, notes and optional photos, then Save on this
phone. Editing is not autosaved: unsaved changes warn on close/navigation.
Later reconnect and use Sync on each record. Sync requires a live login and
fresh CSRF token and enforces the draft owner. A transaction saves new customers,
survey, measurements, photos and an idempotency receipt together. Once upload
starts the payload is frozen so a lost response can be retried without creating
duplicates. A synced phone copy is retained; edits to the server survey happen
in the desktop POS and are not mirrored back to the local copy.

This is capture-and-upload, not two-way sync, background sync, or offline access
to the whole POS. Measurement units are MM to match existing survey records.
Deleting a survey makes photos inaccessible; retention cleanup is separate.
