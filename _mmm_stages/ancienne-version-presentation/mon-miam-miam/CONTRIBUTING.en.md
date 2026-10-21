[🇫🇷 Français](CONTRIBUTING.md) | 🇬🇧 English

# Contributing to Mon Miam Miam

Detailed step-by-step guide: `docs/01-gestion-de-projet/guides/GUIDE_GITHUB.en.md`.

## Branches

| Branch | Role |
|---|---|
| `main` | Stable, deliverable version, updated only through a `develop` → `main` pull request at the end of a sprint |
| `develop` | Integration of the team's work, updated only through pull requests |
| `feature/MM-xx-short-name` | One task (user story), created from `develop` |

Examples: `feature/MM-01-registration`, `feature/MM-15-place-order`.

## Work cycle

1. `git checkout develop` then `git pull origin develop`
2. `git checkout -b feature/MM-xx-short-name`
3. Work, then `git add .` and `git commit -m "MM-xx: what was done"` (small, frequent commits)
4. `git push -u origin feature/MM-xx-short-name`
5. Before proposing your work: `git pull origin develop`, fix conflicts, test
6. Open a pull request to `develop` (the PR template fills itself in)
7. Another member reviews within the day, then the PR is merged with **Squash and merge**
8. Delete the branch, then `git checkout develop` and `git pull origin develop`

## Rules

1. Never push directly to `main` or `develop`.
2. Never `git push --force` on a shared branch.
3. One branch per user story, with the Jira key in its name.
4. No secrets in the repository: no `.env`, API key or password. Each folder provides a `.env.example` with dummy values.
5. Commit messages start with the Jira key.
6. A waiting pull request blocks someone: review within the day.
7. Do not edit a Laravel migration that has already been merged: create a new one.

## Definition of Done

- Code reviewed by at least one peer through a pull request
- Unit tests written and passing
- Matches the Figma mockup and brand guidelines (#cfbd97 and #000000)
- Works on Chrome, Firefox and Safari
- Acceptance criteria validated by the Product Owner
