[🇫🇷 Français](GUIDE_FIGMA.md) | 🇬🇧 English

# Figma Guide — Mon Miam Miam Mockup and Prototype

This guide answers three questions: what each screen is made of, how to link them in a prototype, and where to find tutorials and examples close to the project.

**Sprint 0 deadline**: the Figma prototype must be finished by **01/10/2026** (3 days from 29/09), then deployed on Vercel (S0-05).

## 1. What the requirements document asks for

- Figma interfaces: **home page** (daily menu, promotions, events) and **authentication page** (login and registration).
- **Brand guidelines**: font and colors (primary **#cfbd97**, secondary **#000000**).
- **Interactive mockup** deployed on **Vercel**, to present the interfaces and user journeys.
- Inspiration suggested in the document: https://dribbble.com/search/restaurant
- Reading suggested in the document on the difference between zoning, wireframe, mockup and prototype: https://marcdezordo.me/les-differences-entre-zoning-wireframe-mockup-et-prototype/

Since the project has four roles (student, employee, manager, administrator), the mockup must show **each role's space**, not only the home page and login.

> **Important**: no existing template matches the project exactly. You need to combine a food ordering app example (student side), a restaurant dashboard (employee, manager and administrator side) and loyalty and referral templates. Use them as **inspiration**: rebuild your screens with your own brand guidelines, without copy-pasting, and check each file's license.

## 2. Organizing the Figma file

**One shared file**, created in a team space (not in drafts) so that Nabil and Nathan can work at the same time. If your free plan limits the number of pages, use **sections** on a single page instead of several pages.

| Section | Content |
|---|---|
| 00 – Brand and components | Colors, typography, buttons, fields, cards, badges, modals |
| 01 – Student (mobile) | All student space screens |
| 02 – Employee (desktop) | Employee space screens |
| 03 – Manager (desktop) | Manager space screens |
| 04 – Administrator (desktop) | Administrator space screens |

**Screen sizes (frames)**: mobile **390 × 844** for the student, desktop **1440 × 900** for staff dashboards. Produce at least the home page and login in a desktop version, since the application is a web application.

**Naming**: `01-Home`, `02-Login`, `03-Registration`… in journey order. Good naming makes the prototype much easier to link.

**Suggested split**: Nabil takes the student space, Nathan the staff space (employee, manager, administrator). Start together with the brand guidelines and shared components.

## 3. Brand guidelines and shared components

### Brand guidelines
- **Colors**: primary #cfbd97, secondary #000000. Add neutrals (white, light gray, dark gray) and state colors: success, warning, error.
- **Contrast**: beige #cfbd97 on a white background is hard to read. Use it as a button background with **black text**, and keep black for body text.
- **Font**: one or two free families (Google Fonts). Record it in the guidelines, since it is part of the deliverables.
- **Figma styles**: create *color styles* and *text styles* (heading 1, heading 2, body, caption) and use them everywhere. A change in guidelines then propagates to every screen.
- **Grid**: spacing in multiples of 8 px, identical corner radii everywhere.
- Note the color codes and font: the front-end team will reuse them in the DaisyUI theme (MM-49).

### Components to create once
Create them as **components** with **variants** (states):

| Component | Variants |
|---|---|
| Button | Primary, secondary, outline, disabled |
| Input field | Default, focus, error, filled |
| Dish card | Normal, sold out (grayed) |
| Status badge | Pending, in preparation, ready or out for delivery, delivered |
| Quantity stepper | − 1 + |
| Bottom navigation bar (mobile) and side menu (desktop) | Active or inactive item |
| Modal | Confirmation, form, cookies |
| Table row | Normal, hovered |
| Message (toast) | Success, error |

Use **Auto Layout** (Shift + A) so components adapt to their text and stay aligned.

## 4. Screens and their composition

For every screen, also think about the **empty**, **error**, **loading** and **success** states.

### 4.1 Student space (mobile 390 × 844)

| Screen | Composition | Story |
|---|---|---|
| Home | Header (logo, Login button or avatar), poster carousel (promotions, events), "Menu of the day" section (dish cards), quick access (Menu, Cart, Points), footer with legal notices, cookie banner | MM-05, MM-09, MM-10 |
| Registration | Name, email, phone, location, password (at least 1 uppercase letter and 1 digit), optional referral code, "Create my account" button, link to login, error messages | MM-01 |
| Login | Email, password (with an icon to show it), "Forgot password?" link, "Log in" button, link to registration, error message | MM-02 |
| Forgot password | 3 screens: email entry, "link sent" confirmation, new password | MM-03 |
| Menu | Category tabs, dish cards (photo, name, price, "Sold out" label, + button), cart icon with counter, bottom navigation bar | MM-06 |
| Cart | Item list (photo, name, quantity stepper, delete), subtotal, "Use my points" block, total, "Order" button, "empty cart" version | MM-14, MM-23 |
| Order confirmation | Choice of "Dine-in" (arrival time) or "Delivery" (location), summary, online payment (bonus), "Confirm" button, confirmation screen with order number and points earned | MM-15, MM-40 |
| My orders | List with status badges, order detail (items, total, points earned), "Leave a comment" button (delivered order), "Make a complaint" button | MM-16, MM-17 |
| Profile and loyalty | Account information, prominent points balance, progress toward the next discount, points history | MM-22, MM-23 |
| Referral | My code (copy and share buttons), program rules, list of referees with status ("reward granted" or "pending") | MM-28 to MM-31 |
| Complaint | Form (related order, reason, message), tracking screen showing the status | MM-32 |
| *Bonus*: Top 10 and mini-games | Ranking with day, week, month filters; game poster with "Play" button | MM-38, MM-39 |

### 4.2 Employee space (desktop 1440 × 900)

| Screen | Composition | Story |
|---|---|---|
| Orders | Side menu (Orders, Menu, Complaints, Statistics), tabs per status, table (no., customer, items, mode, time, status, "Prepare" and "Validate" actions), automatic refresh indicator, detail modal | MM-18 |
| Menu update | Dish list with "Sold out" and "Dish of the day" switches | MM-19 |
| Complaints | Complaints table and reply panel | MM-33 |
| Weekly statistics | Number cards (sales, number of orders) and weekly chart | MM-35 |

### 4.3 Manager space (desktop)

| Screen | Composition | Story |
|---|---|---|
| Order monitoring | Counters per status and table of ongoing orders (read-only) | MM-20 |
| Employee accounts | Employee table and "Add an employee" modal | MM-08 |
| Complaints to validate | List, reply proposed by the employee, "Approve" and "Reject" buttons | MM-34 |
| General statistics | Indicators and charts: sales, orders, loyalty, referral | MM-36 |

### 4.4 Administrator space (desktop)

| Screen | Composition | Story |
|---|---|---|
| Menu management | Dish table and add or edit form (name, category, price, photo, availability) | MM-07 |
| Employee accounts | Table with edit and delete actions, deletion confirmation | MM-08 |
| Promotions and events | Poster upload, start and end dates, preview | MM-09 |
| Settings | Opening hours, points conversion rate, validity period, referral points | MM-24 |
| Points complaints | Balance adjustment with reason, date and author | MM-50 |
| Statistics | Same indicators as the manager, across the whole application | MM-36 |

**Shared screen**: the login serves all roles. A mockup has no real logic: provide either demo buttons ("Log in as student / employee / manager / administrator") or a separate entry point per role (see chapter 5).

## 5. Linking screens: the prototype

### 5.1 Steps
1. **Place the screens in journey order**, from left to right, with clear names.
2. Open the **Prototype** tab (top of the right panel).
3. Select a clickable element (a button, a card, an icon), click the small **+** that appears, then **drag the wire** to the destination screen. To delete a connection, click it then press Delete.
4. In the right panel, set the interaction: **trigger** (On click, On hover…), **action** (Navigate to, Back, Open overlay, Scroll to…), **animation** (Instant, Dissolve, Move in, Push, Smart animate) and its duration.
5. Define the **journey's starting point**: select the screen then click "Flow starting point". A small rectangle appears at the top left of the screen. Give it a name (for example "Student order journey").
6. Test with the **Present** button (▶) at the top right. Try to follow each journey like a real user.

### 5.2 Useful techniques
- **Back button**: the **Back** action, to avoid drawing a wire to every previous screen.
- **Modals** (cookies, deletion confirmation, adding an employee): **Open overlay** action, with a **Close overlay** action on the close button.
- **Component states** (sold out, active, error): create **variants** and link them with **Smart animate**, for example for a switch or a quantity stepper.
- **Fixed bars** (header, bottom navigation bar): tick "Fix position when scrolling" so they stay visible while scrolling.
- **Hover**: add a "While hovering" effect on buttons and table rows so the mockup feels alive.

### 5.3 Journeys to prototype
One starting point per journey, in this order of priority:

| Journey | Sequence |
|---|---|
| 1. Registration | Home → Registration → Logged-in home |
| 2. Student order | Login → Menu → Cart → Confirmation → Order confirmed → My orders |
| 3. Employee handling | Login → Orders → Detail → Prepare → Validate |
| 4. Menu administration | Admin menu → Add a dish → Updated list |
| 5. Loyalty and referral | Profile → Points → Referral; Cart → Use points |
| 6. Complaint | My orders → Detail → Complaint → Tracking; manager side: Approve |
| 7. Promotions and settings | Admin → Promotions; Admin → Settings |

## 6. Deploying the mockup on Vercel

1. In Figma, check that the prototype starts from the right starting point, then use **Share** to get a viewing link.
2. Figma also offers an **embed code**: check in the Share menu. Paste it into a minimal `index.html` page, with the prototype shown full screen in an `iframe`.
3. Put this page in the GitHub repository (`docs/` folder or a dedicated folder) then import the project on **Vercel** to get a public link.
4. Test the Vercel link in a private browsing window, to check that anyone can open the prototype.

## 7. Tutorials and examples

Figma's interfaces evolve. In older videos, the location of some buttons may differ, but the prototyping principles (Flow starting point, On click, Navigate to, Overlay, Smart animate) remain the same.

### 7.1 YouTube tutorials in French
- **Figma pour débutants : les bases** (basics): https://www.youtube.com/watch?v=oBcbcmYfSLk
- **Figma pour débutants : les prototypes** (prototypes): https://www.youtube.com/watch?v=M0xkv7Sqtc0
- **Le prototype sur Figma : tutoriel avec exemple concret** (prototype with a concrete example): https://www.youtube.com/watch?v=tYKkzsAoIG8
- **Prototype, animations et transitions** (Figma basics, episode 2): https://www.youtube.com/watch?v=Lkn3C4a5NqI
- **Composants interactifs, prototype et variantes** (interactive components, prototype and variants): https://www.youtube.com/watch?v=zhet0av8ITk
- **Formation Figma 2025 : les bases pour bien commencer** (beginner course): https://www.youtube.com/watch?v=KCoeSoVEMZE
- **TUTO Prototyper 1** (hover, On click, Back, Scroll to): https://youtu.be/UzFfrifhmMU

### 7.2 Food ordering app video tutorials (in English)
They match the **student** side: menu, cart, order, payment, profile.
- **Food Ordering Mobile App Design in Figma** (long tutorial: wireframes, profile, payment, prototype): https://www.youtube.com/watch?v=Yf00MKUfcIY
- **Food Ordering Mobile App Design in Figma (2020)**: https://www.youtube.com/watch?v=O3BmHGNAGhM
- **Food App Design in Figma**: https://www.youtube.com/watch?v=esbdyyEvkxw
- **Design and Prototype a Food Delivery App in Figma**: https://www.youtube.com/watch?v=-zgweOZ6lbI
- **Modern Food Delivery App UI Design in Figma** (from the home page to checkout): https://www.youtube.com/watch?v=nh4E48zsfE8
- **Designing a Food Delivery App in Figma**: https://www.youtube.com/watch?v=u06jwiBRWoU

### 7.3 Figma Community files to browse or duplicate for inspiration
**Food ordering (student)**
- Food Delivery App UI Design File (with video tutorial): https://www.figma.com/community/file/1278152924485698833/food-delivery-app-ui-design-file-with-step-by-step-tutorial-youtube
- Food Delivery Website & App Design UI Kit (restaurant detail, ordering, cart, checkout, mobile views): https://www.figma.com/community/file/1311333346304045465/food-delivery-website-app-design-ui-kit

**Staff dashboards (employee, manager, administrator)**
- Davur – Restaurant Admin Dashboard Template (dashboard, order list and detail, customers, analytics, reviews): https://www.figma.com/community/file/1348825795821174985/davur-restaurant-admin-dashboard-template
- Free – Restaurant Management Dashboard: https://www.figma.com/community/file/1377334105126529648/free-restaurant-management-dashboard
- Restaurant Dashboard UI Kit (CC BY 4.0 license): https://www.figma.com/community/file/1347700614100496995/restaurant-dashboard-ui-kit-editable
- Free Admin Dashboard UI Kit: https://www.figma.com/community/file/1244293267600418871/free-admin-dashboard-ui-kit

**Loyalty and referral**
- UI Kit – Loyalty App: https://www.figma.com/community/file/1625344491654069884/ui-kit-loyalty-app
- FindMe – Food App UI Mobile Kit (points exchangeable at restaurants): https://www.figma.com/community/file/1343957599586760177/findme-food-app-ui-mobile-kit
- LoyaltyLion – Loyalty Page UI Kit (loyalty page, mobile and desktop versions): https://www.figma.com/community/file/1217884680103043804/loyaltylion-loyalty-page-ui-kit
- Free Referral App UI Design (home, pop-up window, statistics): https://www.figma.com/community/file/1088006789763110466/free-referral-app-ui-design
- Referral Program UI + Components: https://www.figma.com/community/file/1281965507164461142/referral-program-ui-components

### 7.4 Articles
- Figma prototyping for beginners (linking screens, starting point): https://uxplanet.org/figma-prototyping-for-beginners-the-basics-features-and-tips-you-should-know-921ce6c0f846
- Dribbble, "restaurant" search (inspiration source from the requirements document): https://dribbble.com/search/restaurant

## 8. Suggested schedule before the 01/10 deadline

| When | Nabil (student) | Nathan (staff) |
|---|---|---|
| **Today, morning** | Low-fidelity wireframes of all student screens | Brand guidelines, shared styles and components |
| **Today, afternoon** | High fidelity: home, login, registration, menu | High fidelity: employee orders, admin menu management |
| **01/10, morning** | Cart, order confirmation, my orders, loyalty | Manager, settings, promotions, statistics |
| **01/10, afternoon** | Prototype linking, tests, Vercel deployment (together) | |

**Priority if time runs short**: the **Must** screens first (home, authentication, menu, cart, order, my orders, employee orders, admin menu management), then loyalty and referral, then complaints and statistics, and bonuses last.

## 9. Checklist before delivery

- [ ] The brand guidelines (colors #cfbd97 and #000000, font) are documented in Figma.
- [ ] Components have their variants and screens use the shared styles.
- [ ] Each role's screens are present (student, employee, manager, administrator).
- [ ] Empty, error and success states are drawn for the main forms.
- [ ] Each journey in chapter 5.3 has a named starting point and runs without dead ends.
- [ ] The Back button and modals work.
- [ ] The Vercel link opens without an account, on computer and phone.
- [ ] The prototype link is added to the GitHub repository (README or `docs/` folder) and in Jira (S0-03 and S0-05).
