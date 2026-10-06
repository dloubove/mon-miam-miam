[🇫🇷 Français](BACKLOG.md) | 🇬🇧 English

# Product Backlog — Mon Miam Miam

Version 1.0 — 29/09/2026 — Product Owner: Dan
Sources: requirements document V1.0.2 and minutes of meeting no. 2.

## 1. Project framework

**Period**: 30/09/2026 → 21/10/2026. One design sprint (Sprint 0), then 3 development sprints of about one week.

**Team and roles (meeting no. 2)**

| Role | Person(s) |
|---|---|
| Product Owner | Dan |
| Scrum Master (rotating weekly) | Kadir first |
| Back-end (Laravel) | Julio, Dan |
| Front-end (React, TailwindCSS, DaisyUI) and Figma | Nabil, Nathan |
| Database design (MCD/MLD) | Siméon |
| UML diagrams (Draw.io) | Julio, Kadir |

**Tools**: Jira, GitHub, Figma, Vercel, PostgreSQL, DataGrip, Mocodo, Draw.io, Postman, GitHub Actions.

**Schedule**

| Sprint | Dates | Goal |
|---|---|---|
| Sprint 0 | 29/09 → 01/10 | Design: Jira, repository, Figma, MCD/MLD, UML |
| Sprint 1 | 02/10 → 08/10 | Accounts, roles, menu, home page |
| Sprint 2 | 09/10 → 15/10 | Full ordering cycle and loyalty |
| Sprint 3 | 16/10 → 21/10 | Referral, complaints, statistics, tests, deployment and deliverables |

**Priorities (MoSCoW)**: **Must** = essential, **Should** = important, **Could** = if time allows.
**Points**: suggested estimate on a Fibonacci scale, to be revised by the developers with Planning Poker.
**Team**: Back, Front, DB or Doc, for guidance only.

**Definition of Done (to be confirmed with the team)**
- Code reviewed by at least one peer through a pull request, then merged into `develop`.
- Unit tests written and passing.
- Matches the Figma mockup and the brand guidelines (primary #cfbd97, secondary #000000).
- Works on Chrome, Firefox and Safari.
- Acceptance criteria validated by the Product Owner at the Sprint Review.
- Jira card moved to Done.

**Cross-cutting constraints from the requirements document (apply to every story)**
- MVC architecture (Laravel on the back-end) and REST API.
- Web application compatible with all browsers.
- GDPR compliance and data security.
- Compliance with the brand guidelines.
- Tables filled dynamically with AJAX (automatic updates).

**Out of scope for now**: mobile application, multi-restaurant management (future evolution to document in MM-44) and online payment (bonus, MM-40).

## 2. Sprint 0 — Design (29/09 → 01/10)

**Goal**: have a clear working base (mockups, database, diagrams, tools) before coding.

| ID | Task | Owner(s) | Deadline |
|---|---|---|---|
| S0-01 | Set up the Jira workspace (project, backlog, board, sprints) | Dan | 30/09 (2 days max) |
| S0-02 | Create the GitHub repository (frontend/backend folders, branches `main`, `develop`, `feature/*`) | Dan, Julio | 30/09 |
| S0-03 | Functional Figma prototype: home, authentication, student, employee, manager and administrator spaces | Nabil, Nathan | 01/10 (3 days max) |
| S0-04 | Brand guidelines (font, colors #cfbd97 and #000000) in Figma | Nabil, Nathan | 01/10 |
| S0-05 | Deploy the mockup on Vercel | Nabil, Nathan | 01/10 |
| S0-06 | MCD and MLD (Mocodo, PostgreSQL, DataGrip) | Siméon | 2 days max |
| S0-07 | UML use case diagram (Draw.io) | Julio, Kadir | 01/10 |
| S0-08 | UML sequence diagram (Draw.io) | Julio, Kadir | 01/10 |
| S0-09 | Initialize Laravel and the PostgreSQL connection | Julio, Dan | 01/10 |
| S0-10 | Minutes of every team meeting | Scrum Master | Ongoing |

## 3. Sprint 1 — Accounts, roles, menu and home page (02/10 → 08/10)

**Goal**: a student can create an account, log in and browse the menu; the administration manages the menu and employees.

| ID | User story | Acceptance criteria | Prio | Pts | Team |
|---|---|---|---|---|---|
| MM-49 | As a front-end developer, I want a configured React project (Vite, TailwindCSS, DaisyUI in the brand colors, routing, role-based layouts) so that screens are built consistently. | DaisyUI theme in #cfbd97 and #000000. Routes protected by role. Starts with one command. | Must | 3 | Front |
| MM-01 | As a student, I want to create an account (name, email, phone, location, password) so that I can order. | Password with at least 1 uppercase letter and 1 digit. Unique email. Clear error messages. | Must | 5 | Back, Front |
| MM-02 | As a user, I want to log in and out securely so that I can access my space. | Hashed password. Redirect to the space matching the role. | Must | 3 | Back, Front |
| MM-03 | As a user, I want to recover my password through a link so that I don't lose my account. | Link sent by email, time-limited. New password follows the same rules. | Should | 3 | Back, Front |
| MM-04 | As an administrator, I want each role (student, employee, manager, admin) to access only its own space so that data stays protected. | Access denied (403) to unauthorized spaces. Verified by tests. | Must | 5 | Back |
| MM-05 | As a student, I want to see the daily menu, promotions and events on the home page so that I can choose quickly. | Content served by the API. Responsive layout. | Must | 5 | Front, Back |
| MM-06 | As a student, I want to browse the menu (categories, prices, availability) so that I can prepare my order. | Sold-out items flagged. Lists and tables updated dynamically (AJAX). | Must | 3 | Front, Back |
| MM-07 | As an administrator, I want to create, edit and delete menu items so that the menu stays up to date. | Full CRUD with field validation. | Must | 5 | Back, Front |
| MM-08 | As an administrator or manager, I want to add employee accounts (the administrator can also edit and delete them) so that staff can access the application. | Creation with role assignment. Deletion reserved to the admin. | Must | 5 | Back, Front |
| MM-09 | As an administrator, I want to create promotions and events as posters so that I can boost sales. | Image upload, start and end dates. Displayed on the home page. | Should | 5 | Back, Front |
| MM-10 | As a visitor, I want to be told how cookies are used and give my consent so that my data is protected (GDPR). | Banner on first visit. Choice remembered. | Must | 2 | Front |
| MM-11 | As a team, we want migrations and seeders generated from the MLD so that everyone shares the same database. | Test accounts for each role. Database rebuildable with one command. | Must | 3 | DB |
| MM-12 | As a team, we want unit tests on authentication and roles so that this foundation is secure. | Passing tests on registration, login and role-based access. | Must | 3 | Back |
| MM-13 | As a team, we want a risk management plan so that we anticipate delays, critical bugs and server outages. | Risks identified with mitigation measures. | Must | 2 | Doc |

## 4. Sprint 2 — Ordering and loyalty (09/10 → 15/10)

**Goal**: a student places an order, employees process it, and loyalty points work. The application is deployed online.

| ID | User story | Acceptance criteria | Prio | Pts | Team |
|---|---|---|---|---|---|
| MM-14 | As a student, I want to add items to a cart and change quantities so that I can prepare my order. | Total recalculated automatically. Items can be removed. | Must | 5 | Front, Back |
| MM-15 | As a student, I want to confirm my order for dine-in (with arrival time) or delivery (with location) so that I get served. | Mode selection required. Order saved with "pending" status. | Must | 8 | Back, Front |
| MM-16 | As a student, I want to view my order history and details so that I can track my purchases. | List sorted by date. Details for each order. Order status visible. | Must | 3 | Front, Back |
| MM-17 | As a student, I want to leave a comment once my order is delivered so that I can share my feedback. | Only possible for a delivered order. | Should | 2 | Back, Front |
| MM-18 | As an employee, I want to view, prepare and validate orders so that I can handle the flow efficiently. | Table refreshed automatically (AJAX). Statuses: pending, in preparation, ready or out for delivery, delivered. Status changes logged. | Must | 8 | Front, Back |
| MM-19 | As an employee, I want to temporarily edit the menu (sold-out dish, dish of the day) so that customers are informed in real time. | Change immediately visible on the student side. | Must | 3 | Back, Front |
| MM-20 | As a manager, I want to monitor order status in real time so that I can oversee service. | Global view of all ongoing orders. | Should | 3 | Front, Back |
| MM-21 | As a student, I want to automatically earn points on each order so that I am rewarded (1,000 F spent = 1 point). | Points added to the balance once the order is validated. Conversion rate configurable. | Should | 5 | Back |
| MM-22 | As a student, I want to see my balance and points history so that I can track my loyalty. | Balance shown in the profile. Table of points per order. | Should | 3 | Front, Back |
| MM-23 | As a student, I want to use my points to reduce an order total (15 points = 1,000 F) so that I benefit from my loyalty. | Minimum threshold checked. Discount computed and points deducted. | Should | 5 | Back, Front |
| MM-24 | As an administrator, I want to configure application settings (opening hours, conversion rate, points validity period, referral points) so that I can adapt the rules. | Settings editable without touching the code, including the points granted to a referrer. | Should | 3 | Back, Front |
| MM-25 | As a team, we want unit tests on orders and points so that calculations are reliable. | Passing tests on order creation, points earning and redemption. | Must | 3 | Back |
| MM-26 | As a team, we want a project budget analysis so that we can include it in the deliverables. | Document written and reviewed by the PO. | Must | 2 | Doc |
| MM-27 | As a team, we want the legal notices and related legal aspects so that we are compliant. | Legal notices and privacy policy integrated into the site (online generator possible). | Must | 2 | Doc, Front |
| MM-42 | GitHub Actions CI/CD and deployment: backend on a cloud platform with a production PostgreSQL database, frontend on Vercel | `develop` deployed automatically to a test environment, `main` to production. Application reachable online. | Must | 5 | Back |

## 5. Sprint 3 — Referral, complaints, statistics and delivery (16/10 → 21/10)

**Goal**: complete the features, validate quality and deliver a deployed version with all documentation.

**Warning**: this sprint is heavy. At the end of Sprint 1, compare the real velocity with these estimates. If needed, drop the **Could** items first, then some **Should** items (statistics before complaints).

### 5.1 Features

| ID | User story | Acceptance criteria | Prio | Pts | Team |
|---|---|---|---|---|---|
| MM-28 | As a student, I want to generate my unique referral code so that I can share it. | One unique code per user. | Should | 3 | Back, Front |
| MM-29 | As a new student, I want to enter a referral code at registration so that my account is linked to my referrer. | Invalid code rejected. Referrer/referee link saved. When the code is entered (registration or first order) to be confirmed with the client. | Should | 3 | Back, Front |
| MM-30 | As a referrer, I want to earn points when my referee places a first successful order so that I am rewarded. | Reward granted only once. Status "reward granted". | Should | 5 | Back |
| MM-31 | As a referrer, I want to follow the list of my referees and the state of their first order so that I know whether I was rewarded. | List with status per referee. | Should | 2 | Front, Back |
| MM-32 | As a student, I want to report a problem with an order and follow my complaint's status so that I get a solution. | Complaint linked to an order. Statuses visible. | Should | 5 | Back, Front |
| MM-33 | As an employee, I want to view and answer complaints so that I can handle them. | Complaints dashboard. Proposed answer. | Should | 3 | Front, Back |
| MM-34 | As a manager, I want to approve or reject proposed answers, and as an administrator to follow complaints, so that I stay in control. | Decision logged. Tracking view for the admin. | Should | 3 | Back, Front |
| MM-50 | As an administrator, I want to manually validate a points complaint so that I can correct a student's balance in case of error. | Points adjustment logged (reason, date, author). Balance updated. | Should | 3 | Back, Front |
| MM-35 | As an employee, I want to see this week's sales statistics so that I can adjust my work. | Weekly sales and orders. | Should | 5 | Back, Front |
| MM-36 | As a manager or administrator, I want to see general statistics (sales, orders, loyalty, referral) so that I can run the restaurant. | Charts and totals refreshed automatically (AJAX). | Should | 5 | Back, Front |
| MM-37 | As an administrator, I want points to expire automatically after the defined period (e.g. 12 months) so that the loyalty policy is enforced. | Scheduled task. Expired points removed from the balance. | Could | 3 | Back |
| MM-38 | As a student, I want to see the top 10 customers with day, week or month filters so that I can compare myself with others. | Ranking by number of orders. | Could | 3 | Back, Front |
| MM-39 | As a student, I want to take part in mini-games and events so that I can win prizes or points. | At least one working mini-game awarding points. | Could | 8 | Front, Back |
| MM-40 | As a student, I want to pay online through an aggregator (Mobile Money, card) so that I avoid paying on site. | CinetPay or Stripe integration. Bonus feature, not mandatory. | Could | 13 | Back, Front |

### 5.2 Quality, deployment and deliverables

| ID | Task | Acceptance criteria | Prio | Pts | Team |
|---|---|---|---|---|---|
| MM-51 | Referral unit tests (code generation and use, reward granting) | Passing tests on the three cases. | Must | 2 | Back |
| MM-41 | Functional and end-to-end tests (from order to delivery) and test report | Critical features covered (login, order, loyalty). Report written. | Must | 5 | All |
| MM-43 | REST API documentation | Endpoints documented (Postman or OpenAPI). | Must | 3 | Back |
| MM-44 | Technical documentation: MVC architecture, installation guide, database schemas, brand guidelines | Installation reproducible locally and in production. Future evolution to several restaurants mentioned. | Must | 3 | Doc |
| MM-45 | User manual per role (student, employee, manager, administrator) | One guide per role with screenshots. | Must | 3 | Doc |
| MM-46 | Pre-production test version and beta test with a small group | Feedback collected and prioritized. | Must | 3 | All |
| MM-47 | Check browser compatibility (Chrome, Firefox, Safari) | Main user flows validated on all 3 browsers. | Must | 2 | Front |
| MM-48 | Progress report for each sprint and meeting minutes | One report per sprint, stored in the repository. | Must | 2 | Scrum Master |

## 6. Dependencies to watch

- MM-06 to MM-08 and MM-14 depend on the MLD (S0-06) and the migrations (MM-11).
- Front-end development starts after the Figma mockup (01/10).
- Loyalty points (MM-21) require validated orders (MM-18).
- Referral (MM-30) requires registration (MM-01) and orders (MM-15).
- CI/CD and deployment (MM-42) are planned in Sprint 2 to get the application online early. The cloud hosting choice must be made in Sprint 1.
- MM-49 (front-end initialization) comes before all other front-end tasks.
- MM-50 requires complaints (MM-32) and points (MM-21).
