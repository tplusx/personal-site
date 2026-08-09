# Nnamdi — Personal site

A lean, four-page portfolio for Nnamdi, a Product Leader. The frontend uses plain HTML, CSS and browser JavaScript; a small PHP endpoint delivers contact enquiries. Vite is included only as optional development and build tooling.

## Pages

- `index.html` — homepage and randomly selected joke
- `work.html` — placeholder product leadership work
- `about.html` — profile and working principles
- `contact.html` — enquiry form
- `contact.php` — validated, rate-limited server-side email handler

Shared styles, behavior and jokes live in `src/`.

## Run locally

### Without Node

To browse the pages without testing email delivery, run any static file server. Python is a convenient option:

```sh
python3 -m http.server 8000
```

Open <http://localhost:8000>. No dependencies or build step are required.

To test the contact handler locally, use PHP's development server instead:

```sh
CONTACT_TO=your-email@example.com php -S localhost:8000
```

The host must have PHP's `mail()` transport configured for an enquiry to be delivered. In production, set the `CONTACT_TO` environment variable to keep the delivery inbox outside the repository; it defaults to `info@nnamdi.ng`. The form never opens a local email application.

### With Node and Vite

```sh
npm install
npm run dev
```

Create and preview a production build with:

```sh
npm run build
npm run preview
```

## Deploy

Deploy the site to a web host with PHP and a configured mail transport. Upload the HTML files, `src/`, `contact.php`, and the applicable security-header configuration. A static-only host will display the pages but cannot run the contact handler without adapting it to that platform's serverless functions.

The `public/_headers` file supplies security headers on hosts that support the Netlify-style headers format. Configure equivalent headers in the host dashboard when the platform does not read this file. Vite can still bundle the frontend, but its output does not include the PHP handler; copy `contact.php` alongside the built pages if the host serves the `dist/` directory.

The production domain is currently **nnamdi.ng**, and the public contact address is **info@nnamdi.ng**.

## Switching to another domain

For example, to move the site from `nnamdi.ng` to `nnamdi.eu`:

1. Replace `https://nnamdi.ng` in the canonical and Open Graph URLs in:
   - `index.html`
   - `work.html`
   - `about.html`
   - `contact.html`
2. If the email address also changes, replace `info@nnamdi.ng` in:
   - `contact.html` (the visible email link)
   - `src/main.js` (delivery status fallback copy)
   - `contact.php` (default recipient, sender domain and fallback copy)
   Prefer setting `CONTACT_TO` on the server when only the private delivery inbox changes.
3. Point the new domain's DNS records to the chosen hosting provider and add the domain in that provider's project settings.
4. Enable HTTPS before launch. Keep the `Strict-Transport-Security` header only after HTTPS works correctly on the domain and its subdomains.
5. Build or upload the site, then check every page and submit a test enquiry.

A useful pre-deployment search is:

```sh
rg -n "nnamdi\.ng|info@nnamdi\.ng" . \
  --glob '!node_modules' --glob '!dist'
```

After changing domains, that command should return only intentional references, such as updated documentation or redirects.

## Content maintenance

- Update the professional positioning and homepage metadata in `index.html`.
- Update profile copy in `about.html`.
- Update project placeholders in `work.html` and the selected project in `index.html`.
- Add or remove jokes in the exported `jokes` array in `src/jokes.js`. The browser selects one entry at random on each homepage load.
- Adjust colors, spacing, responsive behavior and theme styles in `src/style.css`.

## Pre-deployment checklist

```sh
npm run build
```

- Confirm canonical URLs use the live domain.
- Confirm the contact link and form use the live email address.
- Test dark and light themes on desktop and mobile.
- Refresh the homepage several times to confirm joke rotation.
- Verify security headers on the deployed URL.
