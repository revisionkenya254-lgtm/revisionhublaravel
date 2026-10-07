# API Plan

RevisionHub already has a partial API surface, but it is spread across the root app and several modules. The goal of this plan is to turn that into a clean, mobile-first API that can support a dedicated app without depending on browser routes.

## 1. What We Have Today

Current API pieces already exist in:

- `routes/api.php`
- `app/Http/Controllers/API/AuthenticatedController.php`
- `app/Http/Controllers/API/FrontendController.php`
- `app/Http/Controllers/API/DashboardController.php`
- `app/Http/Controllers/API/CartController.php`
- module-level `routes/api.php` files, many of which still expose placeholder endpoints

The app domain already includes:

- Auth and OTP login
- Course catalog and course detail data
- Learning/player flows
- Quizzes and quiz results
- Lesson Q&A and replies
- Cart and orders
- Reviews and wishlist
- Profile and settings
- AI chat and AI document features
- Payment gateways

## 2. API Direction

The mobile app should use a single unversioned API namespace:

- `GET /api/...`
- `POST /api/...`
- `PUT /api/...`
- `DELETE /api/...`

No versioned or legacy compatibility aliases are registered for the mobile API.

Recommended auth approach:

- `Laravel Sanctum` personal access tokens for mobile sessions
- OTP-based email login and registration
- Optional device tracking for token management and session limits
- Rotating refresh tokens with device-aware session management

Recommended API rules:

- JSON-only responses
- Consistent envelope:
  - `status`
  - `message`
  - `data`
  - `pagination`
  - `errors`
- Stable resource transformers for every mobile-facing response
- Pagination on every list endpoint that can grow
- Search/filter/query params for all catalog views
- Rate limits on auth and message-heavy actions

## 3. Mobile App Scope

The mobile client should focus on the student experience first.

### Phase 1 mobile scope

- Onboarding
- Register, login, verify OTP, reset password
- Home dashboard
- Catalog browsing
- Course detail pages
- Free preview lessons
- Enrollments
- Wishlist
- Cart
- Orders and invoice download
- Profile and settings

### Phase 2 mobile scope

- Learning player
- Progress tracking
- Quiz taking
- Quiz results
- Lesson Q&A
- Announcements
- Reviews
- Certificates

### Phase 3 mobile scope

- AI chat for students
- AI document reader and question regions
- Push notifications
- Saved downloads / offline-ready metadata

### Phase 4 mobile scope

- Instructor tools, if we decide to ship them in the same app
- Course creation and content management
- Payout and live-session settings

## 4. Domain Areas And Planned Endpoints

### Auth

Reuse the existing OTP flow, but normalize it into mobile-friendly endpoints.

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /auth/google/callback` (Google Identity Services browser callback)
- `POST /api/auth/google` (Android Credential Manager; shares the Google identity handler)
- `POST /api/auth/verify-otp`
- `POST /api/auth/resend-otp`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/refresh`
- `POST /api/auth/logout`
- `POST /api/auth/logout-all`
- `GET /api/auth/me`
- `GET /api/auth/check-token`
- `GET /api/auth/devices`
- `DELETE /api/auth/devices/{sessionId}`
- `DELETE /api/auth/devices`

### App bootstrap

These endpoints let the app configure itself on startup.

- `GET /api/bootstrap/settings`
- `GET /api/bootstrap/categories`
- `GET /api/bootstrap/languages`
- `GET /api/bootstrap/currencies`
- `GET /api/bootstrap/countries`
- `GET /api/bootstrap/social-links`
- `GET /api/bootstrap/menu`
- `GET /api/bootstrap/mobile-menu`
- `GET /api/bootstrap/onboarding`
- `GET /api/bootstrap/faqs`
- `GET /api/bootstrap/privacy-policy`
- `GET /api/bootstrap/terms-and-conditions`
- `GET /api/bootstrap/pages/{slug}`

### Catalog

This is the discoverability layer for the mobile app.

- `GET /api/catalog/courses`
- `GET /api/catalog/courses/{slug}`
- `GET /api/catalog/courses/{slug}/reviews`
- `GET /api/catalog/categories/{slug}/subcategories`
- `GET /api/catalog/course-languages`
- `GET /api/catalog/course-levels`
- `GET /api/catalog/products/{type?}`
- `GET /api/catalog/products/{type}/{slug}`
- `GET /api/catalog/search`
- `GET /api/catalog/popular`
- `GET /api/catalog/fresh`

### Learning

This is the most important part of the mobile app after auth.

- `GET /api/learning/enrolled`
- `GET /api/learning/{slug}`
- `GET /api/learning/{slug}/progress`
- `GET /api/learning/{slug}/items/{type}/{id}`
- `POST /api/learning/lessons/{id}/complete`
- `GET /api/learning/{slug}/announcements`

### Quizzes

- `GET /api/learning/{courseSlug}/quiz/{quizId}`
- `POST /api/learning/{courseSlug}/quiz/{quizId}`
- `GET /api/learning/{courseSlug}/quiz-results/{quizId}`
- `GET /api/quizzes/attempts`
- `GET /api/quizzes/attempts/{id}`

### Q&A

- `GET /api/qna/lessons/{courseSlug}/{lessonId}/questions`
- `POST /api/qna/lessons/{courseSlug}/{lessonId}/questions`
- `POST /api/qna/questions/replies/{lessonId}/{questionId}`
- `DELETE /api/qna/questions/{questionId}`
- `DELETE /api/qna/replies/{replyId}`

### Cart, Orders, Payments

- `GET /api/cart`
- `POST /api/cart/items/{slug}`
- `DELETE /api/cart/items/{slug}`
- `GET /api/orders`
- `GET /api/orders/{invoiceId}`
- `GET /api/payments/methods`
- `GET /api/payments/{method}`
- `POST /api/payments/mpesa/orders`
- `POST /api/payments/mpesa/orders/{invoiceId}/payment`
- `GET /api/payments/mpesa/orders/{invoiceId}`
- `GET /api/orders/{invoiceId}/download`

### Profile

- `GET /api/profile`
- `PUT /api/profile`
- `PUT /api/profile/bio`
- `PUT /api/profile/password`
- `PUT /api/profile/address`
- `PUT /api/profile/socials`
- `POST /api/profile/avatar`

### Reviews and Wishlist

- `GET /api/wishlist`
- `POST /api/wishlist`
- `DELETE /api/wishlist/{itemType}/{itemId}`
- `GET /api/reviews`
- `GET /api/reviews/{id}`
- `DELETE /api/reviews/{id}`
- `POST /api/courses/{slug}/reviews`

### AI

Keep this behind auth and rate limits.

- `GET /api/ai/chat/conversations`
- `POST /api/ai/chat/conversations`
- `GET /api/ai/chat/conversations/{id}/messages`
- `POST /api/ai/chat/conversations/{id}/stream`
- `POST /api/ai/chat/conversations/{id}/image`
- `DELETE /api/ai/chat/conversations/{id}`
- `GET /api/ai/credits`

## 5. Backend Work Plan

### Step 1. Consolidate route design

- Maintain a single unversioned API namespace for mobile.
- Keep mobile-facing routes centralized in `routes/api.php`.
- Do not add legacy aliases; update the unreleased mobile client when paths change.

### Step 2. Standardize responses

- Create API resource classes for every mobile screen.
- Ensure every list endpoint returns consistent pagination metadata.
- Normalize error responses into a predictable shape for the mobile client.

### Step 3. Tighten auth/session handling

- Keep OTP login as the default entry point.
- Make the mobile app store the Sanctum token securely.
- Add device/session listing and remote logout support.
- Confirm token cleanup on logout and account changes.

### Step 4. Finish core student flows

- Catalog browsing
- Enrollment views
- Learning player
- Quiz submit/results
- Q&A
- Wishlist
- Orders/invoices

### Step 5. Add operational support

- Push notification device registration
- App version check / forced upgrade flags
- Analytics events for major actions
- Better cache invalidation for catalog and menu data

## 6. Mobile Client Work Plan

### Navigation

- Bottom tabs are likely enough for the first release:
  - Home
  - Search
  - Library
  - Cart or Orders
  - Profile

### State management

- Keep auth token in secure storage
- Cache bootstrap payload locally
- Cache catalog pages and course detail payloads
- Sync progress and quiz actions immediately, with retry for temporary failures

### Offline behavior

- Allow course browsing from cached data
- Keep lesson metadata cached for the currently opened course
- Defer video/document downloads to later unless we explicitly want offline content

### UX priorities

- Fast startup
- Clear empty states
- Simple retry on failed requests
- Visible network/loading states
- Keep learning screens readable and low-friction

## 7. Data And API Contracts

The mobile client will be easier to maintain if we lock down a few contract rules:

- IDs should be stable and numeric unless a route explicitly uses a slug
- Course and lesson routes should prefer slugs for readability
- Course progress should return percentages as numbers, not formatted strings
- Currency should be returned as both raw value and display value when needed
- File responses should expose storage metadata needed by the mobile client

## 8. Suggested Version 1 Delivery Order

1. Auth and bootstrap endpoints
1. Catalog and course detail endpoints
1. Enrolled learning flow
1. Progress and quiz flow
1. Cart, orders, invoices, payments
1. Profile and devices
1. Wishlist, reviews, Q&A
1. AI features

## 9. Risks To Watch

- The current API is split across root routes and module routes, so route consolidation needs care.
- Some endpoints currently return browser-oriented responses or mix JSON with redirects.
- Payment and invoice flows need to stay compatible with existing gateway callbacks.
- Mobile learning actions should not double-count progress when requests retry.
- AI and messaging endpoints need rate limits and throttling before wider release.

## 10. Open Questions

- Is the first mobile release student-only, or do we also want instructor tools?
- Do we want a single API version for web and mobile, or separate compatibility layers?
- Should downloads be in-app only, or should we support offline file storage?
- Do we want push notifications in the first release, or after the core learning flow ships?
