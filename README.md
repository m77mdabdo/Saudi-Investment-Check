# Saudi Investment Check 🇸🇦

A mobile-first Saudi market readiness assessment platform built for Creative Mark.

The platform helps companies quickly evaluate their readiness to enter the Saudi Arabian market through a short interactive assessment and connects qualified leads with Creative Mark consultants.

## 🌐 Production Domain

https://investment.dareljamila.com

## 🎯 Project Objective

Saudi Investment Check is designed to provide a fast, simple, and interactive assessment for companies considering entering the Saudi market.

The complete user journey is:

```text
QR Code
   ↓
Landing Page
   ↓
Start Assessment
   ↓
8 Questions
   ↓
Lead Capture
   ↓
Server-Side Score Calculation
   ↓
Readiness Result
   ↓
Consultation / Meeting CTA
```

The target completion time is approximately **60–90 seconds**.

---

## ✨ Main Features

### Landing Page

* Mobile-first design
* Premium corporate appearance
* Creative Mark branding
* Arabic RTL interface
* Fast loading
* QR-code friendly
* Clear call-to-action

### Interactive Assessment

The assessment contains:

* 7 scored questions
* 1 open-ended question
* One question per screen
* Progress indicator
* Previous / Next navigation
* Fast interaction
* Mobile-friendly answer cards

### Lead Capture

The platform collects:

* Full name
* Company name
* WhatsApp number
* Email address
* Consent to contact

It also stores the user's assessment answers and final classification.

### Readiness Scoring

The scoring system evaluates six readiness dimensions:

1. Company Stage
2. Saudi Entry Goal
3. Saudi Market Traction
4. Entry Timeline
5. Budget Readiness
6. Operational Readiness

Maximum score:

```text
12 points
```

### Result Classification

| Score | Classification |
| ----- | -------------- |
| 9–12  | READY          |
| 5–8   | NEEDS PREP     |
| 0–4   | EARLY STAGE    |

Lead classifications:

```text
READY       → Hot Lead
NEEDS PREP  → Warm Lead
EARLY STAGE → Early Lead
```

The final score is calculated **server-side** to prevent manipulation from the browser.

---

## 📝 Assessment Questions

### Q1 — Company Stage

* Idea / under establishment
* Early operation / growth
* Operating company with customers and sales
* Established company seeking expansion

### Q2 — Business Sector

Examples:

* Trading & Distribution
* Contracting & Finishing
* Technology & Software
* Services & Consulting
* Manufacturing
* Medical
* Training & Education
* Food & Agriculture
* E-commerce
* Marketing & Media
* Other

### Q3 — Saudi Entry Goal

The user identifies the primary reason for entering the Saudi market.

### Q4 — Saudi Market Traction

The user indicates whether they currently have:

* Customers
* Sales
* Leads
* Negotiations
* Potential contracts
* No current traction

### Q5 — Timeline

The user selects the expected Saudi market entry timeline:

* 0–3 months
* 3–6 months
* 6–12 months
* No defined timeline

### Q6 — Budget Readiness

The user indicates whether a budget has been allocated for entering the Saudi market.

### Q7 — Operational Readiness

The user evaluates their current operational readiness, team, and systems.

### Q8 — Main Concern

An open-ended question captures the user's main concern regarding entering Saudi Arabia.

Example:

> Cost? Licensing? Is my activity permitted? Is the market suitable? Where should I start?

This answer is stored as a qualitative sales insight.

---

## 🧮 Scoring

Each scored question uses a value between:

```text
0
1
2
```

Maximum score:

```text
12
```

The Laravel backend calculates the final score.

Example:

```php
$score = 0;

$score += $scores['company_stage'][$request->company_stage] ?? 0;
$score += $scores['saudi_goal'][$request->saudi_goal] ?? 0;
$score += $scores['saudi_traction'][$request->saudi_traction] ?? 0;
$score += $scores['timeline'][$request->timeline] ?? 0;
$score += $scores['budget_readiness'][$request->budget_readiness] ?? 0;
$score += $scores['operational_readiness'][$request->operational_readiness] ?? 0;
```

Result:

```php
if ($score >= 9) {
    $result = 'ready';
} elseif ($score >= 5) {
    $result = 'needs_prep';
} else {
    $result = 'early_stage';
}
```

---

## 📊 Lead Data

Each lead may contain:

```text
Name
Company Name
WhatsApp
Email
Consent
Company Stage
Sector
Sector Other
Saudi Entry Goal
Saudi Market Traction
Timeline
Budget Readiness
Operational Readiness
Main Question
Score
Result
Lead Classification
Source
UTM Source
UTM Medium
UTM Campaign
UTM Content
Device
IP Address
User Agent
Session ID
Created At
```

---

## 📱 QR Source Tracking

QR campaigns can use source parameters.

Example:

```text
https://investment.dareljamila.com?source=booth_qr
```

Possible sources:

```text
booth_qr
walking_qr
portfolio
campaign
direct
```

The source is preserved throughout the assessment and stored with the lead.

---

## 📈 Analytics

The platform is designed to track the complete conversion funnel.

Events include:

```text
landing_page_view
quiz_started
quiz_question_completed
quiz_completed
lead_form_viewed
lead_submitted
result_ready
result_needs_prep
result_early_stage
cta_clicked
meeting_clicked
```

This allows the team to measure:

```text
QR Scans
   ↓
Landing Page Views
   ↓
Quiz Starts
   ↓
Quiz Completions
   ↓
Lead Submissions
   ↓
Consultation / Meeting Requests
```

---

## 🎨 Design System

Primary visual direction:

```text
Black / Dark Charcoal
Gold
White
Muted Gray
```

Suggested colors:

```css
--black: #090909;
--charcoal: #141414;
--gold: #C9A45C;
--white: #FFFFFF;
--muted: #A7A7A7;
```

Design principles:

* Premium
* Corporate
* Saudi business focused
* Mobile-first
* Minimal
* Fast
* High contrast
* Large touch targets
* No unnecessary animations

---

## 🛠 Technology Stack

### Backend

* Laravel 12
* PHP 8.5+
* MySQL

### Frontend

* Blade
* Tailwind CSS
* Alpine.js
* Vite

### Development

* Git
* GitHub
* Composer
* NPM

### Production

* Hostinger
* MySQL
* HTTPS / SSL
* Production Laravel configuration

---

## 📁 Planned Project Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── LandingController.php
│   │   ├── QuizController.php
│   │   ├── LeadController.php
│   │   └── Admin/
│   │       └── LeadController.php
│   │
│   ├── Requests/
│   │   └── StoreLeadRequest.php
│   │
│   └── Middleware/
│
├── Models/
│   ├── Lead.php
│   └── AnalyticsEvent.php
│
└── Services/
    ├── QuizScoringService.php
    └── AnalyticsService.php

database/
├── migrations/
└── seeders/

resources/
├── views/
│   ├── landing.blade.php
│   ├── quiz.blade.php
│   ├── lead-form.blade.php
│   ├── result.blade.php
│   └── admin/
│
├── css/
└── js/

routes/
├── web.php
└── admin.php
```

---

## ⚙️ Installation

Clone the repository:

```bash
git clone <repository-url>
```

Enter the project:

```bash
cd saudi-investment-check
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure the database inside:

```text
.env
```

Run migrations:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

Start the development server:

```bash
php artisan serve
```

---

## 🔐 Environment Variables

Sensitive credentials must never be committed to GitHub.

Example:

```env
APP_NAME="Saudi Investment Check"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CREATIVE_MARK_WHATSAPP=
BOOKING_URL=
```

Production credentials must be configured separately on the server.

---

## 🔒 Security

The application should follow Laravel security best practices.

Required:

* CSRF protection
* Server-side validation
* Server-side score calculation
* Rate limiting
* Secure environment variables
* Authentication for admin areas
* Authorization for administrative actions
* Secure database credentials
* HTTPS in production
* Input validation and sanitization
* Consent tracking
* Application logging

The browser must never be trusted with the final lead score.

---

## 📤 Lead Management

The administrative area is planned to provide:

* Lead list
* Lead details
* Result filtering
* Sector filtering
* Timeline filtering
* Source filtering
* Date filtering
* Lead classification
* Score
* Assessment answers
* Excel export
* Funnel analytics

---

## 🚀 Deployment

Production domain:

```text
https://investment.dareljamila.com
```

The production deployment will include:

```text
Laravel
↓
Production .env
↓
MySQL
↓
Composer dependencies
↓
Frontend build
↓
Database migrations
↓
Storage configuration
↓
SSL
↓
Domain / Subdomain
```

Production commands will be documented after the first deployment.

---

## 🔗 Project Identity

**Project:** Saudi Investment Check

**Brand:** Creative Mark

**Production URL:**

```text
https://investment.dareljamila.com
```

**Purpose:**

Saudi market readiness assessment and lead qualification.

---

## 📌 Project Status

Current stage:

```text
🚧 Initial Development
```

Planned milestones:

* [ ] Laravel 12 setup
* [ ] Git repository
* [ ] Database structure
* [ ] Landing page
* [ ] Quiz engine
* [ ] Scoring service
* [ ] Lead capture
* [ ] Result pages
* [ ] QR source tracking
* [ ] Analytics events
* [ ] Admin dashboard
* [ ] Excel export
* [ ] Production deployment
* [ ] SSL / domain configuration

---

## 📄 Disclaimer

The assessment provides an initial indication of a company's readiness to explore entering the Saudi market.

It is not a final assessment of legal eligibility, licensing requirements, or regulatory approval. Final requirements depend on the company's activity, structure, ownership, and applicable Saudi regulations and should be reviewed with the appropriate specialists.
