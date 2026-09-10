# AGENTS.md - SaudeMaxi v3

## 1. Purpose

This file defines mandatory engineering rules for any AI agent, developer, or automation that changes the SaudeMaxi v3 repository.

The primary goal is to evolve the application without destabilizing existing behavior, weakening security, duplicating architecture, or inventing product rules that have not been validated.

When a task conflicts with this document, stop and report the conflict before implementing it.

---

## 2. Project context

SaudeMaxi v3 is the production destination for the new SaudeMaxi application.

Baseline identified in the analyzed repository archive:

- Laravel 12
- PHP 8.2+
- Vite 7
- Tailwind CSS 4
- Axios
- PHPUnit 11
- Laravel Pint
- Laravel Sail available for development
- Initial Laravel skeleton with minimal application code

The analyzed baseline did not yet contain the final Vue architecture. Therefore, agents must always inspect the repository as it exists at execution time before assuming whether Vue, Inertia, Pinia, authentication scaffolding, or other frontend packages have already been installed.

### Product references

Two reference implementations were analyzed during project planning:

1. `sistema-saude-maxi`
   - Functional product reference.
   - Contains business behavior, module rules, consultation states, API contracts, white label behavior, patient and manager flows, and MAX assistant rules.

2. `esboco-saude-maxi`
   - Visual and commercial prototype.
   - Contains additional visual states and user journeys.

For migration work, treat the functional system as the main behavioral reference and the prototype as the main visual reference.

Do not copy the legacy architecture literally into the new project.

---

## 3. Source of truth and conflict resolution

Before changing behavior, use this order of precedence:

1. Explicit requirements from the current task.
2. This `AGENTS.md`.
3. Current SaudeMaxi v3 code and passing automated tests.
4. Current project documentation.
5. Functional rules documented in `sistema-saude-maxi`.
6. Behavior implemented in `sistema-saude-maxi`.
7. Visual behavior from `esboco-saude-maxi`.

If two sources conflict on a business rule, do not silently choose one.

Report:

- what conflicts;
- which files or rules are involved;
- the safest option;
- what needs product confirmation.

Never invent a medical, contractual, privacy, billing, tenant, or integration rule to unblock implementation.

---

## 4. Mandatory workflow before editing

For every non-trivial task, follow this sequence.

### 4.1 Inspect first

Before writing code:

1. Read this file.
2. Inspect `composer.json` and `package.json`.
3. Inspect the affected routes.
4. Inspect the affected controllers, services, models, Vue components, stores, tests, and configuration.
5. Inspect `git status` and existing uncommitted changes when repository access allows it.
6. Search for an existing implementation before creating a new abstraction.
7. Identify the smallest set of files required by the task.

Do not assume the repository still matches the originally analyzed ZIP.

### 4.2 Protect unrelated work

Never delete, revert, rewrite, or reformat unrelated user changes.

Do not use broad refactors to solve a local task unless the refactor is explicitly requested or required for correctness.

Do not replace a working subsystem only because another approach is personally preferred.

### 4.3 Plan the affected layers

Map the task before implementation:

```text
UI / Route
    -> application state or action
    -> backend endpoint if required
    -> validation / authorization
    -> service or integration
    -> persistence if required
    -> tests
```

Only touch layers that are necessary.

---

## 5. Stability rules

These rules have priority over convenience.

### 5.1 Preserve working behavior

- Do not break existing routes, authentication, authorization, APIs, layouts, builds, tests, or database compatibility.
- Do not rename public routes, API fields, component props, emitted events, enum values, database columns, or environment variables without checking all consumers.
- Keep backward compatibility unless the task explicitly authorizes a breaking change.
- Prefer additive changes over destructive changes.
- Prefer small, reviewable changes over large rewrites.

### 5.2 No architecture duplication

Do not introduce two competing solutions for the same responsibility.

Examples:

- Do not add Vue Router to an application that has already standardized navigation on Inertia unless an explicit architecture change is approved.
- Do not create Pinia state that merely duplicates authoritative Inertia server props without a real client-side state requirement.
- Do not use both Axios services and random `fetch()` calls throughout views.
- Do not create a second authentication mechanism beside an existing working Laravel authentication flow.
- Do not create a second tenant resolution mechanism.

### 5.3 Do not introduce technology casually

Do not add a dependency unless:

- the existing stack cannot reasonably solve the requirement;
- it has a clear maintenance benefit;
- its use is scoped;
- the build remains stable.

For the current project direction:

- JavaScript is preferred unless TypeScript is already present or explicitly requested.
- Do not introduce TypeScript as a side effect of an unrelated task.
- Do not enable SSR unless explicitly required.
- Do not introduce dark mode unless explicitly required by the product.
- ESLint and Prettier are recommended when already configured or when the task is specifically establishing frontend standards.

---

## 6. Laravel architecture

Use Laravel conventions before creating custom infrastructure.

### 6.1 Controllers

Controllers should coordinate the request, not contain large business rules.

Prefer:

```text
Request
  -> Form Request validation
  -> Controller
  -> Action / Service when business logic is non-trivial
  -> Model / Integration
  -> Resource / response
```

Do not put external API integration logic directly into controllers.

### 6.2 Validation

- Validate all external input on the server.
- Use Form Requests for non-trivial validation.
- Frontend validation improves UX but never replaces backend validation.
- Do not trust IDs, tenant identifiers, role names, prices, permissions, health data, or status transitions supplied by the browser.

### 6.3 Authorization

Authorization must be enforced server-side.

Use Laravel Policies, Gates, middleware, or an equivalent centralized mechanism.

Hiding a button in Vue is not authorization.

### 6.4 Services and Actions

Create a Service or Action when logic:

- has meaningful business rules;
- integrates with an external provider;
- is reused;
- performs multiple coordinated operations;
- needs independent testing.

Do not create empty architectural layers that only forward one method without adding value.

### 6.5 Database writes

Use transactions for operations that must succeed or fail as a unit.

Do not perform partial multi-table writes that can leave inconsistent state.

### 6.6 Migrations

- Never edit an already deployed migration just to change production schema behavior.
- Create a new migration for schema evolution.
- Migrations must be reversible whenever reasonably possible.
- Destructive changes require explicit approval.
- Do not drop columns or tables simply because current code no longer appears to use them.
- Add indexes based on real query patterns, not guesswork.

### 6.7 Models

- Keep mass assignment explicit.
- Define relationships clearly.
- Prevent N+1 queries with deliberate eager loading.
- Avoid placing external HTTP calls in Eloquent models.

---

## 7. Frontend architecture

The target frontend uses Vue 3 when Vue is part of the current repository state.

Always inspect the installed stack before deciding the routing model.

### 7.1 If the project uses Inertia

If Laravel Vue Starter Kit or Inertia is already installed:

- preserve Inertia as the navigation architecture;
- do not introduce Vue Router without explicit approval;
- use Inertia pages for route-level screens;
- keep authorization and authoritative server data on Laravel;
- use Pinia only for meaningful client-only or cross-page state;
- do not duplicate every server prop in a store.

### 7.2 If the project uses a standalone Vue SPA

If the repository intentionally uses Vue Router instead of Inertia:

- preserve Vue Router as the single frontend router;
- centralize route guards;
- keep HTTP communication inside services;
- do not mix Blade page routing and Vue page routing without a clear boundary.

Changing between Inertia and Vue Router is an architectural migration and requires explicit approval.

### 7.3 Vue coding rules

Prefer Vue 3 Composition API and `<script setup>` for new components unless the current codebase has standardized another style.

Components should have one clear responsibility.

Do not:

- build the entire application inside `App.vue`;
- manipulate the DOM manually with `document.querySelector` when Vue can own the state;
- use global mutable variables for application state;
- use `window.*` as an informal store;
- call APIs directly from arbitrary templates or presentation-only components;
- create giant components containing routing, business rules, integration code, and UI together;
- use `v-html` with untrusted content.

Prefer:

```text
Page
  -> reusable component
  -> composable / store when needed
  -> service
  -> Laravel endpoint
```

### 7.4 Shared state

Shared state must have a defined owner.

Use Pinia only when state genuinely needs to survive or be shared across unrelated components or pages.

Examples that may justify shared state:

- authenticated user context;
- tenant branding context;
- contracted module availability;
- persistent UI state;
- complex multi-step client flow when server state is not appropriate.

Do not make one global store for the entire application.

### 7.5 HTTP access

Centralize HTTP configuration.

Do not scatter raw Axios or fetch configuration across pages.

The HTTP layer should consistently handle, when relevant:

- CSRF;
- authentication;
- validation errors 422;
- unauthorized 401;
- forbidden 403;
- not found 404;
- server errors 5xx;
- network failures;
- request cancellation where applicable.

A page must not contain service credentials.

---

## 8. UI and design system stability

The reference application uses white label branding and a single primary brand color per client.

### 8.1 White label rule

Do not create a separate hard-coded palette for each client.

Use one client brand color and derive visual variants through CSS tokens where possible.

Conceptually:

```css
:root {
    --brand: #5E5212;
    --brand-soft: color-mix(in srgb, var(--brand) 8%, #fff);
    --brand-dark: color-mix(in srgb, var(--brand) 16%, #0A1030);
}
```

The exact variable names may follow the current frontend implementation, but there must be one centralized branding source.

Never write code such as:

```js
if (tenant === 'client_x') {
    // special colors
}
```

unless a documented product requirement requires tenant-specific behavior beyond branding.

### 8.2 Semantic colors are not brand colors

Success, warning, error, danger, and informational colors represent semantic state.

Do not derive a danger button from the client's brand color.

### 8.3 Design tokens

Centralize reusable values for:

- brand colors;
- semantic colors;
- spacing;
- radius;
- shadows;
- typography;
- transitions;
- layer/z-index conventions.

Avoid arbitrary repeated magic values.

### 8.4 Hidden elements

The reference prototype previously suffered a regression where authored `display` styles overrode the browser behavior of the `hidden` attribute.

In Vue, prefer `v-if` or `v-show` for state-driven visibility.

If the final CSS still uses elements with `hidden`, preserve an equivalent protection:

```css
[hidden] {
    display: none !important;
}
```

Do not remove it without proving no affected element still relies on `hidden`.

### 8.5 Responsive behavior

Every significant frontend change must be checked for at least:

- 375 px;
- 768 px;
- 1024 px;
- 1440 px.

The product is expected to be usable on mobile, desktop, and health-station or kiosk-like environments.

Use sufficiently large touch targets and readable text.

### 8.6 Accessibility

Maintain semantic HTML and keyboard support.

Use appropriate:

- labels;
- focus states;
- `aria-*` attributes;
- `aria-live` for important dynamic feedback;
- modal focus management;
- Escape handling where appropriate.

Do not trade accessibility for visual fidelity to the prototype.

---

## 9. Multi-tenant isolation

Tenant isolation is a security boundary, not a UI feature.

### Mandatory rules

- Resolve the tenant from the authenticated and trusted application context.
- Never trust `tenant_id` received from a query string, route parameter, form field, or JSON body as proof of access.
- Every tenant-owned query must be scoped correctly.
- Authorization must verify ownership or tenant access server-side.
- Do not hard-code tenant IDs in application logic.
- White label information must come from tenant configuration/context.

A browser request cannot grant itself access to another tenant by changing an ID.

When implementing background jobs, queued tasks, exports, notifications, or external synchronization, carry tenant context explicitly and safely.

---

## 10. Contracted modules and plans

The reference product separates a plan from the modules enabled by that plan.

Known module concepts include:

- `orientacao`
- `atendimento`
- `agendamento`
- `farmacia`
- `nr1`

Do not scatter plan-name checks throughout the frontend or backend.

Prefer a centralized capability rule such as:

```text
moduleEnabled('farmacia')
```

The UI may explain that a feature is unavailable, but the server must also enforce any protected module restriction.

Do not make a module permanently unavailable because one client or one plan does not currently contract it.

---

## 11. Medical and product safety rules

These are product constraints and must not be weakened by refactoring.

### 11.1 Guidance, not diagnosis

The product guides and routes the user. It must not conclude that a user has a disease.

Do not produce patient-facing copy that presents a disease conclusion as a medical determination.

The legacy product specifically prohibits the terms:

- `diagnóstico`
- `pré-diagnóstico`

Do not introduce them into assistant responses or product copy without explicit product and clinical approval.

### 11.2 Medication

Never suggest replacing or changing a medication on behalf of the system.

Medication substitution is a medical decision.

### 11.3 Emergency signals

Emergency guidance must preserve the validated escalation behavior.

In the reference product, severity signals requiring emergency response direct the user to SAMU 192.

Do not weaken or hide emergency escalation logic when changing MAX or patient guidance flows.

### 11.4 MAX assistant

MAX must be architected so that responses and decisions can be audited where required.

If MAX is connected to a real AI service:

```text
Browser
    -> SaudeMaxi Laravel backend
    -> AI provider
```

Never expose the AI provider secret to the browser.

Product safety rules must live in the backend/system prompt and server validation where appropriate, not only in visual frontend code.

### 11.5 NR-1

NR-1 health and mental-health reporting has a strict privacy boundary.

The manager sees aggregate/group information, not an individual's sensitive mental-health information.

Do not build:

- individual mental-health alerts for HR;
- individual mental-health risk dashboards for managers;
- individual sensitive NR-1 records exposed through manager APIs;

without an explicit, legally reviewed, product-approved requirement.

When a requirement conflicts with confidentiality, stop and request clarification.

### 11.6 LGPD and sensitive information

Health data is sensitive personal data.

Minimize its collection, storage, exposure, and logging.

Never log full sensitive payloads merely for debugging.

Do not put sensitive health information in:

- URLs;
- query strings;
- analytics event names;
- browser localStorage unless a reviewed requirement explicitly allows it;
- exception messages exposed to users.

---

## 12. External telemedicine platform

The telemedicine platform already exists and is maintained by another provider.

SaudeMaxi is a layer above it.

### 12.1 Do not reimplement provider features by default

When uncertain whether a feature should be built locally or consumed from the telemedicine provider, investigate first.

The safe default is to verify whether it already exists before implementing a duplicate.

### 12.2 Service token

The clinic service token must never be included in JavaScript, Vue props, HTML, browser storage, or client-visible network configuration.

Required integration shape:

```text
Vue / browser
    -> Laravel
    -> SaudeMaxi integration service
    -> telemedicine provider API
```

Secrets belong in server-side environment configuration.

Never commit real secrets to the repository.

### 12.3 Known provider constraints

The analyzed provider documentation states:

- base API context is under `/api/clinic/`;
- authentication uses a clinic service Bearer token;
- there is no general webhook for consultation events;
- there is no prescription endpoint returning Mevo prescriptions to SaudeMaxi.

Do not invent a webhook or prescription endpoint because the desired UX would be easier with one.

### 12.4 Consultation reconciliation

Because the provider does not send a general consultation webhook, consultation state may require reconciliation using the documented consultation history endpoint or a backend-controlled polling/reconciliation strategy.

Do not make frontend correctness depend on an event the provider does not emit.

### 12.5 Prescription limitation

The external platform creates prescriptions through Mevo inside the appointment flow, but the analyzed API does not return those prescriptions to SaudeMaxi.

Do not claim that a prescription was synchronized unless a real supported integration now exists.

The reference product keeps photo upload as the working prescription path until the integration contract changes.

### 12.6 External API access belongs in Laravel

Create a dedicated integration layer, for example:

```text
app/
  Services/
    Telemedicine/
      TelemedicineClient.php
      ...
```

or follow the equivalent structure already adopted by the current repository.

Centralize:

- base URL;
- authentication;
- timeout;
- retry policy where safe;
- error mapping;
- logging with sensitive-data redaction;
- response normalization.

Do not repeat HTTP client setup in controllers.

---

## 13. Consultation status machine

The reference system uses these provider consultation states:

```text
SCHEDULED
PENDING
WAITING_HELPDESK
ONGOING_HELPDESK
WAITING_DOCTOR
ONGOING_DOCTOR
FINISHED
CANCELED
```

Do not invent new external provider status values.

If the application needs a local presentation state, keep it clearly separated from the provider status.

Centralize status labels and UI mapping rather than duplicating `switch` statements across pages.

---

## 14. Appointment flow

The documented scheduling flow uses six provider operations in this order:

```text
1. specialties
2. business-days
3. available-times
4. doctors
5. create-consultation
6. update-payment-status, when required
```

Preserve dependency between steps.

A later step must not assume inputs that were not confirmed by earlier steps.

Each remote step must support relevant UI states:

- loading;
- success;
- empty;
- validation failure;
- network/provider failure;
- retry when safe.

Avoid double submission of consultation creation or payment status operations.

---

## 15. Authentication and session security

Use the authentication model already selected by the current repository.

Do not add a competing token/session mechanism without explicit approval.

For browser-based Laravel applications:

- preserve CSRF protection;
- use secure session/cookie settings appropriate to the environment;
- never store privileged service tokens in localStorage;
- invalidate or rotate sessions according to existing Laravel authentication behavior.

Magic links from the telemedicine provider are integration artifacts, not a substitute for SaudeMaxi authorization.

---

## 16. Error handling

Do not expose stack traces, provider secrets, SQL, internal identifiers, or sensitive payloads to end users.

User-facing errors should be actionable and written in Brazilian Portuguese.

Backend logs should contain enough context to diagnose failures without storing unnecessary sensitive health data.

For external integrations, distinguish when possible between:

- timeout;
- provider unavailable;
- unauthorized integration;
- invalid request;
- not found;
- business rejection.

Do not turn every provider error into HTTP 500 if a more accurate local response exists.

---

## 17. Logging and observability

Log important application and integration failures with structured context.

Do not log:

- passwords;
- authentication tokens;
- complete Authorization headers;
- full medical descriptions unnecessarily;
- sensitive patient documents unless strictly necessary and approved.

Redact sensitive values before logging.

Audit events involving privileged actions or AI decisions should be designed for traceability where required.

---

## 18. Testing requirements

No task is complete merely because the code compiles.

### 18.1 PHP tests

Run focused tests while developing.

Before considering a backend-affecting task complete, run when available:

```bash
php artisan test
```

or the repository's equivalent Composer test command.

### 18.2 Frontend build

For frontend changes, run:

```bash
npm run build
```

The task is not complete with a broken production build.

### 18.3 Lint and formatting

If ESLint/Prettier scripts exist, run the project-provided commands.

For PHP formatting, follow the existing Laravel Pint configuration.

Do not mass-format unrelated files.

### 18.4 Route changes

When changing Laravel routes, inspect:

```bash
php artisan route:list
```

Confirm that existing routes were not accidentally shadowed or removed.

### 18.5 Database changes

For migrations, verify the migration path in a disposable development/test database when possible.

Do not test destructive migration experiments against production data.

### 18.6 UI checks

Automated tests do not replace visual checks.

When changing layouts, modals, navigation, responsive behavior, branding, or visibility, inspect relevant screens at the target breakpoints.

### 18.7 Do not fake validation

Never report a command as successful if it was not run.

If the environment prevents a test or build from running, explicitly state:

- which command could not be run;
- why;
- what remains unverified.

---

## 19. Test priorities

As the project evolves, prioritize automated coverage for:

- authentication;
- authorization;
- tenant isolation;
- contracted module access;
- patient versus manager access;
- consultation status mapping;
- appointment creation;
- external integration error handling;
- payment/status idempotency where applicable;
- MAX safety rules;
- NR-1 privacy boundaries;
- sensitive manager actions.

Test business behavior, not implementation trivia.

---

## 20. Environment and configuration

Never commit `.env` secrets.

When adding a new environment variable:

1. add a safe placeholder or documented value to `.env.example` when appropriate;
2. access it from Laravel config files, not by calling `env()` throughout application code;
3. use `config()` in application classes;
4. document required production configuration.

External URLs, timeouts, feature switches, provider credentials, and environment-specific behavior belong in configuration, not hard-coded classes.

---

## 21. Dependencies

Before adding a Composer or npm dependency, answer:

1. Can Laravel, Vue, Tailwind, or the existing stack already solve this?
2. Is the package actively maintained?
3. Is the dependency necessary in production or only development?
4. Does it introduce a second architecture for an existing concern?
5. Can it be removed cleanly later?

Do not edit `vendor/` or `node_modules/` directly.

Commit lockfile changes when dependency changes are intentional.

---

## 22. Performance

Avoid premature micro-optimization, but prevent obvious regressions.

Backend:

- prevent N+1 queries;
- paginate large collections;
- select only needed data when practical;
- do not call the same external endpoint repeatedly within one request without reason;
- use caching only when invalidation rules are understood.

Frontend:

- avoid unnecessary global state;
- avoid huge page bundles when lazy loading provides clear benefit;
- avoid duplicating large API payloads in multiple stores;
- debounce searches that would otherwise trigger excessive requests;
- cancel stale requests when appropriate.

---

## 23. Naming and language

Code identifiers should follow existing Laravel/Vue conventions and remain consistent with the current codebase.

User-facing interface text must be Brazilian Portuguese unless a task explicitly requests localization.

The legacy product has an explicit communication rule to avoid electoral vocabulary in municipal contexts. Do not use the word `campanha` in product copy unless explicitly approved.

The legacy specification also prohibits the em dash character in product text, code comments, and assistant copy. Use punctuation such as commas, periods, colons, or a normal hyphen instead.

---

## 24. Files and directories that require extra care

Treat changes to these areas as high impact:

```text
.env*
composer.json
composer.lock
package.json
package-lock.json
vite.config.*
bootstrap/app.php
config/
routes/
database/migrations/
app/Models/
app/Policies/
app/Services/ or integration layer
resources/js/app.*
frontend router or Inertia bootstrap
frontend auth/session bootstrap
```

Before changing them, inspect all consumers and verify build/tests afterward.

Do not modify lockfiles manually.

---

## 25. Prohibited shortcuts

Do not:

- disable tests to make a build pass;
- weaken authorization to unblock a screen;
- remove validation because the frontend already validates;
- expose provider tokens to make browser integration easier;
- bypass tenant scoping;
- hard-code a production credential;
- silently swallow integration failures;
- use fake success responses in production paths;
- invent unavailable provider endpoints;
- persist sensitive medical data in browser storage for convenience;
- create health conclusions that the product is not allowed to make;
- implement individual NR-1 mental-health reporting without explicit approval;
- modify unrelated files simply to satisfy formatting preferences;
- replace established architecture without explicit approval.

---

## 26. Implementing a new feature

Use this checklist.

### Step 1. Understand

- What user profile owns the feature?
- Patient, manager, admin, or integration?
- Which tenant owns the data?
- Which contracted module controls access?
- Is the feature local to SaudeMaxi or already owned by the telemedicine provider?
- Does it involve sensitive health information?

### Step 2. Locate existing patterns

Search for:

- similar routes;
- similar pages/components;
- existing services;
- existing policies;
- existing status mappings;
- existing tenant/module guards;
- existing tests.

### Step 3. Define server boundary

For privileged or sensitive behavior, design backend validation and authorization first.

### Step 4. Implement minimally

Create only the abstractions the feature needs.

### Step 5. Verify failure paths

Test:

- invalid data;
- unauthorized user;
- wrong tenant;
- module unavailable;
- empty response;
- provider failure;
- repeated submission.

### Step 6. Validate

Run focused tests, full relevant tests, frontend build, and visual checks.

---

## 27. Fixing a bug

For bug fixes:

1. Reproduce the bug first when possible.
2. Find the root cause, not only the visible symptom.
3. Identify why current tests did not detect it.
4. Add a regression test when practical.
5. Apply the smallest safe fix.
6. Verify adjacent flows.

Do not rewrite an entire subsystem to fix a local bug unless the architecture itself is the root cause and the change is explicitly approved.

---

## 28. Refactoring

Refactoring must preserve observable behavior unless behavior change is explicitly part of the task.

Before refactoring:

- ensure there is adequate test coverage or create targeted characterization tests;
- establish the current public contract;
- avoid mixing major refactoring with unrelated feature work;
- keep migrations and API changes separate from pure code cleanup when possible.

A refactor is not permission to rename public contracts casually.

---

## 29. Definition of done

A change is complete only when all applicable items are true:

- requested behavior is implemented;
- existing behavior remains stable;
- authorization is enforced server-side;
- tenant isolation is preserved;
- sensitive data is handled safely;
- external provider secrets remain server-side;
- relevant automated tests pass;
- frontend production build passes;
- responsive behavior was checked when UI changed;
- accessibility was considered when UI changed;
- no unrelated files were changed without reason;
- no undocumented architectural duplication was introduced;
- remaining limitations are explicitly reported.

---

## 30. Required completion report for agents

At the end of a significant implementation, report concisely:

### Implemented

What behavior was added or corrected.

### Files changed

List the relevant files and why they changed.

### Architecture impact

State whether routes, database, integrations, authentication, tenant rules, stores, or public APIs changed.

### Validation performed

List the commands actually run and their result, for example:

```text
php artisan test: PASS
npm run build: PASS
php artisan route:list: inspected
```

### Pending or blocked items

State anything that still depends on:

- provider credentials;
- homologation environment;
- product decision;
- legal/LGPD decision;
- unavailable external API;
- environment limitation.

Never hide a blocked dependency by implementing a fake production behavior.

---

## 31. Final engineering principle

SaudeMaxi v3 must evolve through controlled, compatible changes.

The agent's priority order is:

```text
Correctness
  -> Security and privacy
  -> Tenant isolation
  -> Product safety
  -> Backward compatibility
  -> Maintainability
  -> UX
  -> Performance
  -> Convenience
```

When a faster implementation conflicts with one of the priorities above, choose the safer and more maintainable implementation.
