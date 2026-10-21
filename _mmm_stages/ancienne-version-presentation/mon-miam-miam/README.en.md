[🇫🇷 Français](README.md) | 🇬🇧 English

# Mon Miam Miam

Web platform for ordering meals at the **ZeDuc@Space** restaurant: dine-in or delivery orders, loyalty program, referral, complaints, and dedicated spaces (student, employee, manager, administrator).

Project of **group 9**, carried out from 30/09/2026 to 21/10/2026 using the Scrum method.

## Repository structure

```
mon-miam-miam/
├── backend/            Laravel REST API (to be initialized, see below)
├── frontend/           React application (to be initialized)
├── prototype-figma/    Figma mockup deployed on Vercel
├── docs/               All non-technical deliverables and the design
├── .github/            Pull request and issue templates, continuous integration
├── .vscode/            Settings and recommended extensions for VS Code
├── CONTRIBUTING.en.md  How to contribute (branches, commits, pull requests)
└── README.en.md
```

The `docs/` folder is organized by deliverable: see [docs/README.en.md](docs/README.en.md).

## Technologies

- **Backend**: Laravel (PHP), REST API
- **Frontend**: React, TailwindCSS, DaisyUI
- **Database**: to be confirmed by the team (MySQL or PostgreSQL, the same for everyone)
- **Mockup**: Figma, deployed on Vercel
- **Tools**: GitHub, Jira, VS Code

## Project links

- Figma mockup: [Open the Figma prototype](https://www.figma.com/proto/Z55RYti3JixBN2vdx1GIMn/Site-Zeduc-Space?node-id=38-1806&starting-point-node-id=38%3A1806)
- Online mockup (Vercel): [mon-miam-miam-prototype-figma.vercel.app](https://mon-miam-miam-prototype-figma.vercel.app)
- Jira: [Project Jira board](https://2030-team-tnjko9vn.atlassian.net/jira/software/projects/MM/boards/68/backlog)

## Getting started

```
git clone <repository-url>
cd mon-miam-miam
git checkout develop
code .
```

VS Code offers to install the recommended extensions: accept.
Backend and frontend installation instructions will be added to `docs/03-technique/installation` once they are initialized.

## Contributing

Nobody pushes directly to `main` or `develop`. Everything goes through a pull request reviewed by another member: see [CONTRIBUTING.en.md](CONTRIBUTING.en.md).

## Team — Group 9

Dan (Product Owner), Julio, Nabil, Nathan, Siméon, Kadir, Loïs.
