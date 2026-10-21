[🇫🇷 Français](README.md) | 🇬🇧 English

# Figma mockup on Vercel

This folder contains a minimal page that displays the Figma prototype in full screen. Vercel publishes it on every update of `main`.

## Setup (once)

1. In Figma, make the prototype viewable by link, then get the **embed code** (Share menu).
2. Paste the prototype address into the `src` attribute of the `iframe` in `index.html`, through a pull request.
3. On https://vercel.com, create a project and **import the GitHub repository**.
4. Settings: **Root Directory** = `prototype-figma`, **Framework Preset** = *Other*, **Production Branch** = `main`.
5. Start the deployment, then test the resulting address in a private browsing window.
6. Copy the address into the root `README.en.md` and into Jira (S0-05).

## Updates

Every merge into `main` redeploys the page. To change the Figma prototype without changing the link, just republish the Figma file.
