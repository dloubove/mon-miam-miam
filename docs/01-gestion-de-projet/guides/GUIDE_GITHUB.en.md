[🇫🇷 Français](GUIDE_GITHUB.md) | 🇬🇧 English

# GitHub Guide for the Team — Mon Miam Miam

This guide explains step by step how 6 or 7 people can work on the same code without overwriting each other. Just follow it in order.

## 1. Key terms

| Term | Simple explanation |
|---|---|
| **Repository** | The project folder, stored on GitHub with its full history. |
| **Clone** | A copy of the repository on your computer. |
| **Commit** | A saved set of changes, with a message. |
| **Branch** | A separate line of work. You change code there without touching other branches. |
| **Push** | Send your commits from your computer to GitHub. |
| **Pull** | Fetch onto your computer what others have sent to GitHub. |
| **Pull request (PR)** | A request to merge your branch into another one, reviewed by a teammate. |
| **Merge** | Combining one branch into another. |
| **Conflict** | Two people changed the same lines: you must choose the right version. |

## 2. Repository organization

**A single repository** with three folders:

```
mon-miam-miam/
├── backend/    (Laravel)
├── frontend/   (React)
├── docs/       (UML, MCD/MLD, meeting minutes, reports)
├── .gitignore
└── README.md
```

**Branches**

| Branch | Role | Who can write to it |
|---|---|---|
| `main` | Stable, deliverable version | Only through a `develop` → `main` pull request at the end of a sprint |
| `develop` | The whole team's work, assembled | Only through a pull request from a `feature/...` branch |
| `feature/MM-xx-name` | One task (user story) in progress | The developer working on it |

**Branch name**: `feature/` + the Jira key + a short name.
Examples: `feature/MM-01-registration`, `feature/MM-15-place-order`.

**Commit message**: the Jira key, a colon, then what was done, in the present tense.
Examples: `MM-01: add registration form`, `MM-15: compute cart total`.

## 3. Initial setup (every member, once)

1. **Create a GitHub account** and give your username to the project lead.
2. **Install Git**: https://git-scm.com/downloads
3. **Optional but recommended for beginners**: install **GitHub Desktop** or use the Git tab in **VS Code**. They do the same actions with buttons. The commands in this guide remain valid.
4. **Set your identity** in a terminal:
   ```
   git config --global user.name "First Last"
   git config --global user.email "your@email.com"
   ```
   Use the same email address as your GitHub account.
5. **Accept the invitation** to the repository received by email (or on GitHub, in notifications).
6. **Clone the repository**:
   ```
   git clone <repository-url>
   cd mon-miam-miam
   git checkout develop
   ```

## 4. Repository setup (project lead, once)

1. On GitHub: **New repository**, name `mon-miam-miam`, private, tick "Add a README".
2. **Clone** it, then create the `backend/`, `frontend/` and `docs/` folders (Git does not keep empty folders: add a `.gitkeep` file in each).
3. **Create the `.gitignore`** with at least:
   ```
   .env
   vendor/
   node_modules/
   .idea/
   .vscode/
   storage/*.key
   ```
4. **Create the `develop` branch** and push it:
   ```
   git add .
   git commit -m "Initialize project structure"
   git push origin main
   git checkout -b develop
   git push -u origin develop
   ```
5. **Invite the team**: *Settings → Collaborators → Add people*, with write access.
6. **Set `develop` as the default branch**: *Settings → Branches → Default branch*. New pull requests will then target `develop` automatically.
7. **Protect `main` and `develop`**: *Settings → Branches → Add branch protection rule*. Tick "Require a pull request before merging" and "Require approvals" (1 approval).

> **Warning**: on a **private** repository with a free GitHub account, branch protection is not available. Options: request the **GitHub Student Developer Pack** (free Pro accounts for students), make the repository public, or follow the rules by discipline without technical enforcement. In all cases, the rules in chapter 9 apply.

8. **Connect GitHub to Jira** (optional): branches and pull requests containing the `MM-xx` key then appear on the Jira card.

## 5. The work cycle for each task

Follow these 8 steps **for every user story**.

### Step 1 — Get up to date
```
git checkout develop
git pull origin develop
```
*Why?* To start from the team's latest work.

### Step 2 — Create your branch
```
git checkout -b feature/MM-15-place-order
```
*Why?* To work without disturbing others. Check with `git branch` that the active branch (marked with a star) is the right one.

### Step 3 — Work and commit
After each small piece of finished work:
```
git status
git add .
git commit -m "MM-15: add the cart"
```
- `git status` shows the modified files.
- `git add .` stages all modified files. Check beforehand that no secret file (`.env`) is included.
- Make **frequent, small commits** rather than one big commit at the end of the day.

### Step 4 — Push your branch to GitHub
The first time:
```
git push -u origin feature/MM-15-place-order
```
Afterwards, a simple `git push` is enough.

### Step 5 — Resynchronize before proposing your work
While you were working, others may have merged code into `develop`. Fetch it:
```
git pull origin develop
```
- If there is a **conflict**, see chapter 7.
- If a text editor opens for a merge message, save and close (in Vim: press `Esc`, then type `:wq` and `Enter`).
- **Test** that the application still works, then `git push`.

### Step 6 — Open a pull request
1. On GitHub, click **Compare & pull request** (or *Pull requests → New pull request*).
2. Check: **base: `develop`** and **compare: your branch**.
3. **Title**: `MM-15 Place order`.
4. **Description**: what was done, how to test it, which acceptance criteria are covered.
5. Assign **at least one reviewer** (*Reviewers*).
6. Notify the reviewer in the team chat.

### Step 7 — The review
See chapter 6.

### Step 8 — Merge, then clean up
Once the pull request is approved:
1. Click **Squash and merge** (combines all commits into one, keeping the history readable), then **Confirm**.
2. Click **Delete branch**.
3. Locally:
   ```
   git checkout develop
   git pull origin develop
   git branch -d feature/MM-15-place-order
   ```
4. Move the Jira card to **Done** (after the PO's validation at the Sprint Review).

## 6. Giving and receiving a review

**The reviewer** (within a day, so nobody is blocked):
1. Open the **Files changed** tab of the pull request.
2. Read the code. Leave line-by-line comments if needed.
3. Go through the checklist below.
4. Click **Review changes**: **Approve** if everything is fine, **Request changes** otherwise.

**Review checklist**
- The code meets the user story's acceptance criteria.
- No secret, no `.env`, no password in the code.
- Names are clear, no dead code or forgotten `console.log`.
- Tests exist for the important logic.
- The result matches the Figma mockup and the brand guidelines (#cfbd97 / #000000).

**The author**: fix, then `git add`, `git commit` and `git push` on **the same branch**. The pull request updates itself.

## 7. Resolving a conflict

A conflict appears during a `git pull` or a merge when two people changed the same lines. Git reports it:

```
Auto-merging routes/web.php
CONFLICT (content): Merge conflict in routes/web.php
```

**What to do?**
1. Open the affected file. Git added markers:
   ```
   <<<<<<< HEAD
   your version
   =======
   the other person's version
   >>>>>>> origin/develop
   ```
2. Decide: keep one, the other, or combine both.
3. **Delete the three markers** (`<<<<<<<`, `=======`, `>>>>>>>`).
4. Save, then:
   ```
   git add routes/web.php
   git commit
   ```
5. Test the application, then `git push`.

VS Code offers "Accept Current / Incoming / Both" buttons that make this easier. When in doubt, talk to the other person involved instead of guessing.

**How to avoid conflicts**
- Short branches: 1 to 2 days maximum.
- `git pull origin develop` every morning and before each pull request.
- Warn the team before modifying a shared file (`routes/web.php`, `App.jsx`, configuration files).
- Never edit a Laravel migration that has already been merged: create a new one.
- Do not reformat whole files unnecessarily (it creates pointless conflicts).

## 8. End of sprint

After the Sprint Review and the Product Owner's validation:
1. Open a **`develop` → `main`** pull request titled "Sprint X".
2. Have it reviewed by the Scrum Master or the PO, then merge with **Create a merge commit** (not Squash, to keep the task history).
3. Tag the delivered version:
   ```
   git checkout main
   git pull origin main
   git tag sprint-1
   git push origin sprint-1
   ```
4. Go back to `develop` to continue: `git checkout develop`.

## 9. Golden rules

1. **Never push directly** to `main` or `develop`.
2. **Never `git push --force`** on a shared branch.
3. **One branch per user story**, with the Jira key in its name.
4. **No secret in the repository**: `.env`, API keys, passwords. Provide a `.env.example` file with dummy values.
5. **One commit = one clear change**, with a message starting with the Jira key.
6. **Get up to date before starting**: `git pull origin develop`.
7. **Review within the day**: a waiting pull request blocks someone.
8. **Don't take over someone else's branch** without telling them.

## 10. Common problems

| Problem | Solution |
|---|---|
| I coded without creating a branch (on `develop`), nothing committed yet | `git checkout -b feature/MM-xx-name`: your changes follow to the new branch. |
| I already committed on `develop` by mistake (not pushed) | Ask the project lead for help before trying any manipulation. |
| `git push` is rejected ("rejected", "non-fast-forward") | Someone pushed before you: `git pull origin <your-branch>`, fix conflicts, then `git push`. |
| I must switch branches but have unfinished work | `git stash` (sets the work aside), switch branch, then `git stash pop` to get it back. |
| I want to discard my uncommitted changes to a file | `git restore file`. **Warning: this is irreversible.** |
| I committed the `.env` file | Notify the project lead immediately, remove the file (`git rm --cached .env`), and **change every secret** it contained (they remain visible in the history). |
| I don't know where I am | `git status` (current state) and `git log --oneline` (latest commits). |
| I can't see a teammate's branch | `git fetch`, then `git checkout branch-name`. |

## 11. Cheat sheet

```
git status                          # see the state
git branch                          # see your active branch
git checkout develop                # switch to develop
git pull origin develop             # fetch others' work
git checkout -b feature/MM-xx-name  # create your branch
git add .                           # stage changes
git commit -m "MM-xx: message"      # save
git push -u origin feature/MM-xx-name  # push (first time)
git push                            # push (afterwards)
git stash / git stash pop           # set aside / get back
git log --oneline                   # short history
```

**Work order: `pull` → branch → `commit` → `pull develop` → `push` → pull request → review → merge.**
