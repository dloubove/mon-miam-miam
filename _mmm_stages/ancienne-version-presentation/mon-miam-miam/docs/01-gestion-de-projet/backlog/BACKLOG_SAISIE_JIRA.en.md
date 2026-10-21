[🇫🇷 Français](BACKLOG_SAISIE_JIRA.md) | 🇬🇧 English

# Backlog to enter in Jira — Mon Miam Miam

This document is for filling Jira **by hand**, without CSV import. Each item is ready to copy and paste: title, type, sprint, points, labels and description.

## How to proceed

1. **Create a new project** (Scrum template, key **MM**): Jira never resets numbering in an existing project, even if items are deleted.
2. **Create the 4 sprints** (see the table below).
3. **Create the 61 items in the exact order of the "Creation order" table** (title and type only). Jira numbers in creation order, epics included: if epics are created first, stories start at 11.
4. **Create the 10 epics last.**
5. **Attach each item to its epic** (filter by label, then the **Parent** field) and set the **sprint**, **points** and **labels**.
6. **Paste the descriptions** from the blocks in the "Items by epic" section. Tick the ☐ box of each title as you go, then check against the totals in the last section.

**Time saver**: first enter all titles, epics, sprints, points and labels (what matters to organize the backlog). Paste descriptions sprint by sprint, right before each Sprint Planning.

**Priority**: the Priority field is not used. MoSCoW priority is carried by the first label (`must`, `should` or `could`).

## Sprints to create

| Sprint | Dates | Items | Points |
|---|---|---|---|
| Sprint 0 | 29/09 → 01/10 | 10 | 0 |
| Sprint 1 | 02/10 → 08/10 | 14 | 52 |
| Sprint 2 | 09/10 → 15/10 | 15 | 60 |
| Sprint 3 | 16/10 → 21/10 | 22 | 87 |

Dates are set when each sprint starts.

## The epics

| Epic | Description to paste |
|---|---|
| Design and technical foundation | Mockups, database, UML diagrams, repository, Laravel and React initialization. |
| Accounts and access | Registration, login, password recovery, roles and cookie consent. |
| Menu and home page | Home page, menu browsing and management, promotions and events. |
| Ordering | Cart, dine-in or delivery orders, history, handling by employees and monitoring by the manager. |
| Loyalty and referral | Loyalty points, redemption for discounts, referral codes and rewards. |
| Complaints | Submission, handling, validation and tracking of complaints, including points complaints. |
| Administration and statistics | Employee accounts, application settings and statistics. |
| Quality, testing and deployment | Unit, functional and E2E tests, CI/CD, beta test, browser compatibility. |
| Documentation and project management | Risks, budget, legal aspects, technical and API documentation, manuals, reports. |
| Bonus | Top 10 customers, mini-games, online payment. |

## Creation order

Create the items **exactly in this order**. With project key MM, the number Jira shows should match the "Expected Jira no." column: MM-01 becomes MM-1, and so on. S0 items get numbers 52 to 61 and epics 62 to 71.

| Expected Jira no. | Item | Type | Sprint |
|---|---|---|---|
| MM-1 | [MM-01] Student registration | Story | Sprint 1 |
| MM-2 | [MM-02] Secure login and logout | Story | Sprint 1 |
| MM-3 | [MM-03] Password recovery | Story | Sprint 1 |
| MM-4 | [MM-04] Roles and permissions | Story | Sprint 1 |
| MM-5 | [MM-05] Home page | Story | Sprint 1 |
| MM-6 | [MM-06] Menu browsing | Story | Sprint 1 |
| MM-7 | [MM-07] Menu CRUD (admin) | Story | Sprint 1 |
| MM-8 | [MM-08] Employee account management | Story | Sprint 1 |
| MM-9 | [MM-09] Promotions and events (posters) | Story | Sprint 1 |
| MM-10 | [MM-10] Cookie consent banner | Story | Sprint 1 |
| MM-11 | [MM-11] Migrations and seeders | Task | Sprint 1 |
| MM-12 | [MM-12] Authentication and roles unit tests | Task | Sprint 1 |
| MM-13 | [MM-13] Risk management plan | Task | Sprint 1 |
| MM-14 | [MM-14] Cart | Story | Sprint 2 |
| MM-15 | [MM-15] Order placement (dine-in / delivery) | Story | Sprint 2 |
| MM-16 | [MM-16] Order history | Story | Sprint 2 |
| MM-17 | [MM-17] Comment after delivery | Story | Sprint 2 |
| MM-18 | [MM-18] Order management (employee) | Story | Sprint 2 |
| MM-19 | [MM-19] Temporary menu update (employee) | Story | Sprint 2 |
| MM-20 | [MM-20] Order monitoring (manager) | Story | Sprint 2 |
| MM-21 | [MM-21] Automatic points earning | Story | Sprint 2 |
| MM-22 | [MM-22] Points balance and history | Story | Sprint 2 |
| MM-23 | [MM-23] Redeem points for a discount | Story | Sprint 2 |
| MM-24 | [MM-24] Application settings | Story | Sprint 2 |
| MM-25 | [MM-25] Orders and points unit tests | Task | Sprint 2 |
| MM-26 | [MM-26] Budget analysis | Task | Sprint 2 |
| MM-27 | [MM-27] Legal notices and legal aspects | Task | Sprint 2 |
| MM-28 | [MM-28] Referral code generation | Story | Sprint 3 |
| MM-29 | [MM-29] Referral code use | Story | Sprint 3 |
| MM-30 | [MM-30] Referrer reward | Story | Sprint 3 |
| MM-31 | [MM-31] Referee tracking | Story | Sprint 3 |
| MM-32 | [MM-32] Student complaint | Story | Sprint 3 |
| MM-33 | [MM-33] Complaint handling (employee) | Story | Sprint 3 |
| MM-34 | [MM-34] Complaint validation (manager/admin) | Story | Sprint 3 |
| MM-35 | [MM-35] Weekly statistics (employee) | Story | Sprint 3 |
| MM-36 | [MM-36] General statistics (manager/admin) | Story | Sprint 3 |
| MM-37 | [MM-37] Automatic points expiration | Story | Sprint 3 |
| MM-38 | [MM-38] Top 10 customers | Story | Sprint 3 |
| MM-39 | [MM-39] Mini-games and events | Story | Sprint 3 |
| MM-40 | [MM-40] Online payment (bonus) | Story | Sprint 3 |
| MM-41 | [MM-41] Functional, E2E tests and test report | Task | Sprint 3 |
| MM-42 | [MM-42] CI/CD and deployment | Task | Sprint 2 |
| MM-43 | [MM-43] REST API documentation | Task | Sprint 3 |
| MM-44 | [MM-44] Technical documentation | Task | Sprint 3 |
| MM-45 | [MM-45] User manual per role | Task | Sprint 3 |
| MM-46 | [MM-46] Test version and beta test | Task | Sprint 3 |
| MM-47 | [MM-47] Browser compatibility | Task | Sprint 3 |
| MM-48 | [MM-48] Sprint reports and meeting minutes | Task | Sprint 3 |
| MM-49 | [MM-49] Initialize the React frontend | Story | Sprint 1 |
| MM-50 | [MM-50] Manual validation of points complaints | Story | Sprint 3 |
| MM-51 | [MM-51] Referral unit tests | Task | Sprint 3 |
| MM-52 | [S0-01] Set up the Jira workspace | Task | Sprint 0 |
| MM-53 | [S0-02] Create the GitHub repository | Task | Sprint 0 |
| MM-54 | [S0-03] Functional Figma prototype | Task | Sprint 0 |
| MM-55 | [S0-04] Brand guidelines in Figma | Task | Sprint 0 |
| MM-56 | [S0-05] Deploy the mockup on Vercel | Task | Sprint 0 |
| MM-57 | [S0-06] MCD and MLD | Task | Sprint 0 |
| MM-58 | [S0-07] UML use case diagram | Task | Sprint 0 |
| MM-59 | [S0-08] UML sequence diagram | Task | Sprint 0 |
| MM-60 | [S0-09] Initialize Laravel and PostgreSQL | Task | Sprint 0 |
| MM-61 | [S0-10] Meeting minutes | Task | Sprint 0 |
| MM-62 | Design and technical foundation | Epic | — |
| MM-63 | Accounts and access | Epic | — |
| MM-64 | Menu and home page | Epic | — |
| MM-65 | Ordering | Epic | — |
| MM-66 | Loyalty and referral | Epic | — |
| MM-67 | Complaints | Epic | — |
| MM-68 | Administration and statistics | Epic | — |
| MM-69 | Quality, testing and deployment | Epic | — |
| MM-70 | Documentation and project management | Epic | — |
| MM-71 | Bonus | Epic | — |

## Items by epic

### Design and technical foundation

12 items, 6 points.

#### ☐ [S0-01] Set up the Jira workspace

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
Set up the Jira workspace (project, backlog, board, sprints)
Suggested owner(s): Dan
Deadline: 30/09 (2 days max)
```

#### ☐ [S0-02] Create the GitHub repository

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
Create the GitHub repository (frontend/backend folders, branches `main`, `develop`, `feature/*`)
Suggested owner(s): Dan, Julio
Deadline: 30/09
```

#### ☐ [S0-03] Functional Figma prototype

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
Functional Figma prototype: home, authentication, student, employee, manager and administrator spaces
Suggested owner(s): Nabil, Nathan
Deadline: 01/10 (3 days max)
```

#### ☐ [S0-04] Brand guidelines in Figma

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
Brand guidelines (font, colors #cfbd97 and #000000) in Figma
Suggested owner(s): Nabil, Nathan
Deadline: 01/10
```

#### ☐ [S0-05] Deploy the mockup on Vercel

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
Deploy the mockup on Vercel
Suggested owner(s): Nabil, Nathan
Deadline: 01/10
```

#### ☐ [S0-06] MCD and MLD

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
MCD and MLD (Mocodo, PostgreSQL, DataGrip)
Suggested owner(s): Siméon
Deadline: 2 days max
```

#### ☐ [S0-07] UML use case diagram

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
UML use case diagram (Draw.io)
Suggested owner(s): Julio, Kadir
Deadline: 01/10
```

#### ☐ [S0-08] UML sequence diagram

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
UML sequence diagram (Draw.io)
Suggested owner(s): Julio, Kadir
Deadline: 01/10
```

#### ☐ [S0-09] Initialize Laravel and PostgreSQL

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
Initialize Laravel and the PostgreSQL connection
Suggested owner(s): Julio, Dan
Deadline: 01/10
```

#### ☐ [S0-10] Meeting minutes

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 0

**Labels**: `conception`

**Description to paste**:

```
Minutes of every team meeting
Suggested owner(s): Scrum Master
Deadline: Ongoing
```

#### ☐ [MM-11] Migrations and seeders

**Epic**: Design and technical foundation · **Type**: Task · **Sprint**: Sprint 1 · **Points**: 3

**Labels**: `must` `base-de-donnees` `bdd`

**Description to paste**:

```
As a team, we want migrations and seeders generated from the MLD so that everyone shares the same database.

Acceptance criteria: Test accounts for each role. Database rebuildable with one command.
```

#### ☐ [MM-49] Initialize the React frontend

**Epic**: Design and technical foundation · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 3

**Labels**: `must` `frontend` `front`

**Description to paste**:

```
As a front-end developer, I want a configured React project (Vite, TailwindCSS, DaisyUI in the brand colors, routing, role-based layouts) so that screens are built consistently.

Acceptance criteria: DaisyUI theme in #cfbd97 and #000000. Routes protected by role. Starts with one command.
```

### Accounts and access

5 items, 18 points.

#### ☐ [MM-01] Student registration

**Epic**: Accounts and access · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 5

**Labels**: `must` `authentification` `back` `front`

**Description to paste**:

```
As a student, I want to create an account (name, email, phone, location, password) so that I can order.

Acceptance criteria: Password with at least 1 uppercase letter and 1 digit. Unique email. Clear error messages.
```

#### ☐ [MM-02] Secure login and logout

**Epic**: Accounts and access · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 3

**Labels**: `must` `authentification` `back` `front`

**Description to paste**:

```
As a user, I want to log in and out securely so that I can access my space.

Acceptance criteria: Hashed password. Redirect to the space matching the role.
```

#### ☐ [MM-03] Password recovery

**Epic**: Accounts and access · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 3

**Labels**: `should` `authentification` `back` `front`

**Description to paste**:

```
As a user, I want to recover my password through a link so that I don't lose my account.

Acceptance criteria: Link sent by email, time-limited. New password follows the same rules.
```

#### ☐ [MM-04] Roles and permissions

**Epic**: Accounts and access · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 5

**Labels**: `must` `authentification` `back`

**Description to paste**:

```
As an administrator, I want each role (student, employee, manager, admin) to access only its own space so that data stays protected.

Acceptance criteria: Access denied (403) to unauthorized spaces. Verified by tests.
```

#### ☐ [MM-10] Cookie consent banner

**Epic**: Accounts and access · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 2

**Labels**: `must` `rgpd` `front`

**Description to paste**:

```
As a visitor, I want to be told how cookies are used and give my consent so that my data is protected (GDPR).

Acceptance criteria: Banner on first visit. Choice remembered.
```

### Menu and home page

4 items, 18 points.

#### ☐ [MM-05] Home page

**Epic**: Menu and home page · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 5

**Labels**: `must` `menu-accueil` `front` `back`

**Description to paste**:

```
As a student, I want to see the daily menu, promotions and events on the home page so that I can choose quickly.

Acceptance criteria: Content served by the API. Responsive layout.
```

#### ☐ [MM-06] Menu browsing

**Epic**: Menu and home page · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 3

**Labels**: `must` `menu-accueil` `front` `back`

**Description to paste**:

```
As a student, I want to browse the menu (categories, prices, availability) so that I can prepare my order.

Acceptance criteria: Sold-out items flagged. Lists and tables updated dynamically (AJAX).
```

#### ☐ [MM-07] Menu CRUD (admin)

**Epic**: Menu and home page · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 5

**Labels**: `must` `menu-accueil` `back` `front`

**Description to paste**:

```
As an administrator, I want to create, edit and delete menu items so that the menu stays up to date.

Acceptance criteria: Full CRUD with field validation.
```

#### ☐ [MM-09] Promotions and events (posters)

**Epic**: Menu and home page · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 5

**Labels**: `should` `menu-accueil` `back` `front`

**Description to paste**:

```
As an administrator, I want to create promotions and events as posters so that I can boost sales.

Acceptance criteria: Image upload, start and end dates. Displayed on the home page.
```

### Ordering

7 items, 32 points.

#### ☐ [MM-14] Cart

**Epic**: Ordering · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 5

**Labels**: `must` `commande` `front` `back`

**Description to paste**:

```
As a student, I want to add items to a cart and change quantities so that I can prepare my order.

Acceptance criteria: Total recalculated automatically. Items can be removed.
```

#### ☐ [MM-15] Order placement (dine-in / delivery)

**Epic**: Ordering · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 8

**Labels**: `must` `commande` `back` `front`

**Description to paste**:

```
As a student, I want to confirm my order for dine-in (with arrival time) or delivery (with location) so that I get served.

Acceptance criteria: Mode selection required. Order saved with "pending" status.
```

#### ☐ [MM-16] Order history

**Epic**: Ordering · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 3

**Labels**: `must` `commande` `front` `back`

**Description to paste**:

```
As a student, I want to view my order history and details so that I can track my purchases.

Acceptance criteria: List sorted by date. Details for each order. Order status visible.
```

#### ☐ [MM-17] Comment after delivery

**Epic**: Ordering · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 2

**Labels**: `should` `commande` `back` `front`

**Description to paste**:

```
As a student, I want to leave a comment once my order is delivered so that I can share my feedback.

Acceptance criteria: Only possible for a delivered order.
```

#### ☐ [MM-18] Order management (employee)

**Epic**: Ordering · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 8

**Labels**: `must` `commande` `front` `back`

**Description to paste**:

```
As an employee, I want to view, prepare and validate orders so that I can handle the flow efficiently.

Acceptance criteria: Table refreshed automatically (AJAX). Statuses: pending, in preparation, ready or out for delivery, delivered. Status changes logged.
```

#### ☐ [MM-19] Temporary menu update (employee)

**Epic**: Ordering · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 3

**Labels**: `must` `commande` `back` `front`

**Description to paste**:

```
As an employee, I want to temporarily edit the menu (sold-out dish, dish of the day) so that customers are informed in real time.

Acceptance criteria: Change immediately visible on the student side.
```

#### ☐ [MM-20] Order monitoring (manager)

**Epic**: Ordering · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 3

**Labels**: `should` `commande` `front` `back`

**Description to paste**:

```
As a manager, I want to monitor order status in real time so that I can oversee service.

Acceptance criteria: Global view of all ongoing orders.
```

### Loyalty and referral

8 items, 29 points.

#### ☐ [MM-21] Automatic points earning

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 5

**Labels**: `should` `fidelite` `back`

**Description to paste**:

```
As a student, I want to automatically earn points on each order so that I am rewarded (1,000 F spent = 1 point).

Acceptance criteria: Points added to the balance once the order is validated. Conversion rate configurable.
```

#### ☐ [MM-22] Points balance and history

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 3

**Labels**: `should` `fidelite` `front` `back`

**Description to paste**:

```
As a student, I want to see my balance and points history so that I can track my loyalty.

Acceptance criteria: Balance shown in the profile. Table of points per order.
```

#### ☐ [MM-23] Redeem points for a discount

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 5

**Labels**: `should` `fidelite` `back` `front`

**Description to paste**:

```
As a student, I want to use my points to reduce an order total (15 points = 1,000 F) so that I benefit from my loyalty.

Acceptance criteria: Minimum threshold checked. Discount computed and points deducted.
```

#### ☐ [MM-28] Referral code generation

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `should` `parrainage` `back` `front`

**Description to paste**:

```
As a student, I want to generate my unique referral code so that I can share it.

Acceptance criteria: One unique code per user.
```

#### ☐ [MM-29] Referral code use

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `should` `parrainage` `back` `front`

**Description to paste**:

```
As a new student, I want to enter a referral code at registration so that my account is linked to my referrer.

Acceptance criteria: Invalid code rejected. Referrer/referee link saved. When the code is entered (registration or first order) to be confirmed with the client.
```

#### ☐ [MM-30] Referrer reward

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 5

**Labels**: `should` `parrainage` `back`

**Description to paste**:

```
As a referrer, I want to earn points when my referee places a first successful order so that I am rewarded.

Acceptance criteria: Reward granted only once. Status "reward granted".
```

#### ☐ [MM-31] Referee tracking

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 2

**Labels**: `should` `parrainage` `front` `back`

**Description to paste**:

```
As a referrer, I want to follow the list of my referees and the state of their first order so that I know whether I was rewarded.

Acceptance criteria: List with status per referee.
```

#### ☐ [MM-37] Automatic points expiration

**Epic**: Loyalty and referral · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `could` `fidelite` `back`

**Description to paste**:

```
As an administrator, I want points to expire automatically after the defined period (e.g. 12 months) so that the loyalty policy is enforced.

Acceptance criteria: Scheduled task. Expired points removed from the balance.
```

### Complaints

4 items, 14 points.

#### ☐ [MM-32] Student complaint

**Epic**: Complaints · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 5

**Labels**: `should` `reclamations` `back` `front`

**Description to paste**:

```
As a student, I want to report a problem with an order and follow my complaint's status so that I get a solution.

Acceptance criteria: Complaint linked to an order. Statuses visible.
```

#### ☐ [MM-33] Complaint handling (employee)

**Epic**: Complaints · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `should` `reclamations` `front` `back`

**Description to paste**:

```
As an employee, I want to view and answer complaints so that I can handle them.

Acceptance criteria: Complaints dashboard. Proposed answer.
```

#### ☐ [MM-34] Complaint validation (manager/admin)

**Epic**: Complaints · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `should` `reclamations` `back` `front`

**Description to paste**:

```
As a manager, I want to approve or reject proposed answers, and as an administrator to follow complaints, so that I stay in control.

Acceptance criteria: Decision logged. Tracking view for the admin.
```

#### ☐ [MM-50] Manual validation of points complaints

**Epic**: Complaints · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `should` `reclamations` `back` `front`

**Description to paste**:

```
As an administrator, I want to manually validate a points complaint so that I can correct a student's balance in case of error.

Acceptance criteria: Points adjustment logged (reason, date, author). Balance updated.
```

### Administration and statistics

4 items, 18 points.

#### ☐ [MM-08] Employee account management

**Epic**: Administration and statistics · **Type**: Story · **Sprint**: Sprint 1 · **Points**: 5

**Labels**: `must` `administration` `back` `front`

**Description to paste**:

```
As an administrator or manager, I want to add employee accounts (the administrator can also edit and delete them) so that staff can access the application.

Acceptance criteria: Creation with role assignment. Deletion reserved to the admin.
```

#### ☐ [MM-24] Application settings

**Epic**: Administration and statistics · **Type**: Story · **Sprint**: Sprint 2 · **Points**: 3

**Labels**: `should` `administration` `back` `front`

**Description to paste**:

```
As an administrator, I want to configure application settings (opening hours, conversion rate, points validity period, referral points) so that I can adapt the rules.

Acceptance criteria: Settings editable without touching the code, including the points granted to a referrer.
```

#### ☐ [MM-35] Weekly statistics (employee)

**Epic**: Administration and statistics · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 5

**Labels**: `should` `statistiques` `back` `front`

**Description to paste**:

```
As an employee, I want to see this week's sales statistics so that I can adjust my work.

Acceptance criteria: Weekly sales and orders.
```

#### ☐ [MM-36] General statistics (manager/admin)

**Epic**: Administration and statistics · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 5

**Labels**: `should` `statistiques` `back` `front`

**Description to paste**:

```
As a manager or administrator, I want to see general statistics (sales, orders, loyalty, referral) so that I can run the restaurant.

Acceptance criteria: Charts and totals refreshed automatically (AJAX).
```

### Quality, testing and deployment

7 items, 23 points.

#### ☐ [MM-12] Authentication and roles unit tests

**Epic**: Quality, testing and deployment · **Type**: Task · **Sprint**: Sprint 1 · **Points**: 3

**Labels**: `must` `tests` `back`

**Description to paste**:

```
As a team, we want unit tests on authentication and roles so that this foundation is secure.

Acceptance criteria: Passing tests on registration, login and role-based access.
```

#### ☐ [MM-25] Orders and points unit tests

**Epic**: Quality, testing and deployment · **Type**: Task · **Sprint**: Sprint 2 · **Points**: 3

**Labels**: `must` `tests` `back`

**Description to paste**:

```
As a team, we want unit tests on orders and points so that calculations are reliable.

Acceptance criteria: Passing tests on order creation, points earning and redemption.
```

#### ☐ [MM-42] CI/CD and deployment

**Epic**: Quality, testing and deployment · **Type**: Task · **Sprint**: Sprint 2 · **Points**: 5

**Labels**: `must` `deploiement` `back`

**Description to paste**:

```
GitHub Actions CI/CD and deployment: backend on a cloud platform with a production PostgreSQL database, frontend on Vercel

Acceptance criteria: `develop` deployed automatically to a test environment, `main` to production. Application reachable online.
```

#### ☐ [MM-41] Functional, E2E tests and test report

**Epic**: Quality, testing and deployment · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 5

**Labels**: `must` `tests` `tous`

**Description to paste**:

```
Functional and end-to-end tests (from order to delivery) and test report

Acceptance criteria: Critical features covered (login, order, loyalty). Report written.
```

#### ☐ [MM-46] Test version and beta test

**Epic**: Quality, testing and deployment · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `must` `qualite` `tous`

**Description to paste**:

```
Pre-production test version and beta test with a small group

Acceptance criteria: Feedback collected and prioritized.
```

#### ☐ [MM-47] Browser compatibility

**Epic**: Quality, testing and deployment · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 2

**Labels**: `must` `qualite` `front`

**Description to paste**:

```
Check browser compatibility (Chrome, Firefox, Safari)

Acceptance criteria: Main user flows validated on all 3 browsers.
```

#### ☐ [MM-51] Referral unit tests

**Epic**: Quality, testing and deployment · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 2

**Labels**: `must` `tests` `back`

**Description to paste**:

```
Referral unit tests (code generation and use, reward granting)

Acceptance criteria: Passing tests on the three cases.
```

### Documentation and project management

7 items, 17 points.

#### ☐ [MM-13] Risk management plan

**Epic**: Documentation and project management · **Type**: Task · **Sprint**: Sprint 1 · **Points**: 2

**Labels**: `must` `gestion-projet` `doc`

**Description to paste**:

```
As a team, we want a risk management plan so that we anticipate delays, critical bugs and server outages.

Acceptance criteria: Risks identified with mitigation measures.
```

#### ☐ [MM-26] Budget analysis

**Epic**: Documentation and project management · **Type**: Task · **Sprint**: Sprint 2 · **Points**: 2

**Labels**: `must` `gestion-projet` `doc`

**Description to paste**:

```
As a team, we want a project budget analysis so that we can include it in the deliverables.

Acceptance criteria: Document written and reviewed by the PO.
```

#### ☐ [MM-27] Legal notices and legal aspects

**Epic**: Documentation and project management · **Type**: Task · **Sprint**: Sprint 2 · **Points**: 2

**Labels**: `must` `juridique` `doc` `front`

**Description to paste**:

```
As a team, we want the legal notices and related legal aspects so that we are compliant.

Acceptance criteria: Legal notices and privacy policy integrated into the site (online generator possible).
```

#### ☐ [MM-43] REST API documentation

**Epic**: Documentation and project management · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `must` `documentation` `back`

**Description to paste**:

```
REST API documentation

Acceptance criteria: Endpoints documented (Postman or OpenAPI).
```

#### ☐ [MM-44] Technical documentation

**Epic**: Documentation and project management · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `must` `documentation` `doc`

**Description to paste**:

```
Technical documentation: MVC architecture, installation guide, database schemas, brand guidelines

Acceptance criteria: Installation reproducible locally and in production. Future evolution to several restaurants mentioned.
```

#### ☐ [MM-45] User manual per role

**Epic**: Documentation and project management · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `must` `documentation` `doc`

**Description to paste**:

```
User manual per role (student, employee, manager, administrator)

Acceptance criteria: One guide per role with screenshots.
```

#### ☐ [MM-48] Sprint reports and meeting minutes

**Epic**: Documentation and project management · **Type**: Task · **Sprint**: Sprint 3 · **Points**: 2

**Labels**: `must` `gestion-projet` `scrum-master`

**Description to paste**:

```
Progress report for each sprint and meeting minutes

Acceptance criteria: One report per sprint, stored in the repository.
```

### Bonus

3 items, 24 points.

#### ☐ [MM-38] Top 10 customers

**Epic**: Bonus · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 3

**Labels**: `could` `engagement` `back` `front`

**Description to paste**:

```
As a student, I want to see the top 10 customers with day, week or month filters so that I can compare myself with others.

Acceptance criteria: Ranking by number of orders.
```

#### ☐ [MM-39] Mini-games and events

**Epic**: Bonus · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 8

**Labels**: `could` `engagement` `front` `back`

**Description to paste**:

```
As a student, I want to take part in mini-games and events so that I can win prizes or points.

Acceptance criteria: At least one working mini-game awarding points.
```

#### ☐ [MM-40] Online payment (bonus)

**Epic**: Bonus · **Type**: Story · **Sprint**: Sprint 3 · **Points**: 13

**Labels**: `could` `paiement` `back` `front`

**Description to paste**:

```
As a student, I want to pay online through an aggregator (Mobile Money, card) so that I avoid paying on site.

Acceptance criteria: CinetPay or Stripe integration. Bonus feature, not mandatory.
```

## Final check

| Epic | Items | Points |
|---|---|---|
| Design and technical foundation | 12 | 6 |
| Accounts and access | 5 | 18 |
| Menu and home page | 4 | 18 |
| Ordering | 7 | 32 |
| Loyalty and referral | 8 | 29 |
| Complaints | 4 | 14 |
| Administration and statistics | 4 | 18 |
| Quality, testing and deployment | 7 | 23 |
| Documentation and project management | 7 | 17 |
| Bonus | 3 | 24 |
| **Total** | **61** | **199** |

Jira should contain 61 items in total, plus the 10 epics.
