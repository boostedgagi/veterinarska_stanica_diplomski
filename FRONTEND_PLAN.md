# Frontend plan: vetShopFrontEnd (Angular)

Repository: `~/vetShopFrontEnd/vetShopFrontEnd` (github.com/boostedgagi/vetShopFrontEnd).
Written 2026-11-09, after the backend changes in `UPGRADE_PLAN.md` §8.

## 1. Where it stands

| | |
|---|---|
| Framework | Angular **16.2** (NgModules, Angular Material 16), out of support since Nov 2024 |
| Size | ~4.2k lines of TS, 45 components, 6 services, 1 guard |
| Auth | JWT from `POST /login_check` in `sessionStorage`; every service builds its own `Authorization` header, some forget it |
| Tests | Generated `*.spec.ts` stubs only, no CI |
| Working tree | Uncommitted WIP in `found-pet`, `pet`, `vet-card`, `vet-item`. Commit or stash it first |

**Framework choice: stay on Angular.** The app is small and the code is plain. Upgrading in place keeps every page working at each step, while a rewrite in React or Vue would cost about the same as the upgrade *plus* rebuilding all 45 components. Only rewrite if you want to learn another framework; the API stays the same either way (see §5).

## 2. Step F1: make it work with the new backend (do this first, still on Angular 16)

The backend now rejects calls without a token (401), blocks other users' data (403) and returns 404 for missing records. These calls in the frontend break:

| Call (service) | Problem | Fix |
|---|---|---|
| `addPet`, `editPet`, `deletePet`, `uploadPetImage`, `getUserPets`, `getOnePet`, `generateQRCode` (PetService) | sent **without token** → 401 | interceptor (below) |
| `getOnePetRecords`, `getHealthRecordById` (HealthRecordService) | without token → 401 | interceptor |
| `uploadImage`, `getAllTypedUsers`, `deleteUser` (UserService) | without token → 401 | interceptor |
| `register` → `uploadImage` right after `POST /user` | user isn't logged in yet → 401 (before, anyone could change anyone's picture) | upload the picture after the first login (user page already has it), or drop it from the register form |
| `found-pet` page → `GET /pet/{id}` | that's owner/vet/admin only now | use public `GET /found_pet?id=` (it returns name, dateOfBirth, animal, breed, image, owner firstName/email/phone, exactly what the page shows) |
| `contact` page, `sendContactMessage` | sends `Bearer null` when logged out; an invalid token is a 401 even on a public route | interceptor: only add the header when a token exists |
| `user-page` → `PATCH /user` | password is no longer changed there (it was stored unhashed) | make sure the form doesn't send `password`; `change_password` page already uses `/password/make_new_authenticated` |
| `getVet()` | URL is `${apiUrl}/${vetId}` (missing `/user`), and unused | delete |
| Responses that were `200 "Pet is not existing…"` | now 404 | handle in the error handler, not by reading the body |

Already fixed on the backend (commit after `1a6b898`): the home page, vets page and contact form work without a token again (`GET /vets`, `/examination`, `/examination/{id}`, `/search/examination`, `POST /contact_message`, `/initialize_chat_id` are public).

Changes:

1. **`authInterceptor`** (functional interceptor, `provideHttpClient(withInterceptors(...))` or `HTTP_INTERCEPTORS` in `app.module.ts`):
   - adds `Authorization: Bearer <token>` **only when** a token exists;
   - **401** → clear the token, redirect to `/login?returnUrl=…` (the JWT expires after 2 h; today the app just breaks);
   - **403** → "You don't have access to this" message; **404** → not-found message.
   Then delete `getAuthToken()`/`httpOptionsWithAuth()` from all 6 services (and the `Content-Type` header; `HttpClient` sets it for JSON).
2. **One `TokenStorage`/`AuthService`** that owns `sessionStorage` (now read with `JSON.parse` in 7 places). Decode the JWT `exp` so `isLoggedIn()` is false for an expired token.
3. **Fix `AuthGuard`.** It navigates to login but still returns `true`, so the page loads anyway. Return `router.createUrlTree(['login'])`.
4. **Role guards**: `admin/*` only for `typeOfUser === 1`, `vet/*` only for `2`. Today any logged-in user can open the admin pages (the backend now answers 403, but the pages shouldn't show at all). Put the numbers in one `UserType` enum.
5. **Chat**: the home page creates a chat id and opens `/chat`, but `ChatComponent` is empty. Sending messages needs a logged-in user, and the sender must be yourself. Either finish the chat (logged in only, polling `GET /chat/{chatId}` until real-time push exists, backend §8 M5) or hide the button.
6. **Leaked key**: a TheDogAPI/TheCatAPI key is hard-coded in `pet.service.ts` and in git history. Revoke it. It isn't even sent (`headers.append` returns a new object that's thrown away), and both APIs work without a key at this volume, so just remove it.

Done when: every page works as owner, vet, admin and logged out against the current backend, with no 401 in the console except after token expiry.

## 3. Step F2: upgrade Angular 16 → current

One major at a time with `ng update` (official guide: angular.dev/update-guide). Commit and smoke-test each:

1. Node: use the version the target Angular needs (newer majors need Node 20.19+ / 22+; you have 20.20).
2. `ng update @angular/core@17 @angular/cli@17 @angular/material@17`, then build and click through, then 18, 19, … up to the current version.
   Things to expect on the way:
   - **17**: new `application` builder (esbuild); accept the migration and run `ng build` to check.
   - **18**: Material switches to Material 3 theming. Keep the M2 theme (`mat.m2-*`) to avoid a visual change, or restyle on purpose.
   - **19**: components are standalone by default; the migration adds `standalone: false` to existing ones, so nothing breaks.
   - Later majors: Karma is deprecated in favour of Vitest/Jest; switch in F4.
3. `ngx-cookie-service` and `zone.js`: bump to the matching major each time. `ngx-cookie-service` looks unused (token is in sessionStorage); remove it if so.

## 4. Step F3: modernise the code (optional, only after F2)

Each of these is a schematic or a mechanical change:

- `ng g @angular/core:standalone` (standalone components, drop `app.module.ts`, `bootstrapApplication`);
- `ng g @angular/core:control-flow` (`*ngIf`/`*ngFor` → `@if`/`@for`);
- `ng g @angular/core:inject` (constructor injection → `inject()`);
- typed reactive forms, replacing `contactMessageForm: any = FormGroup` and similar;
- signals for the current user instead of `userData$` plus manual subscriptions;
- replace `alert()` + `window.location.reload()` with a snackbar (Material `MatSnackBar`) and data refresh.

## 5. Step F4: types from the API, tests, CI

- **Generate the API client from Swagger** (`/doc.json`) with `ng-openapi-gen` or `openapi-generator` (typescript-angular). That replaces the hand-written `Pet.ts`, `User.ts`, … and the 6 services, and a backend change then shows up as a compile error. It also works for any other framework, should you switch later.
- Tests: delete the generated spec stubs and write a few that matter: the interceptor (401/403/no token), the guards, login.
- GitHub Actions: `npm ci`, `ng build`, `ng test --watch=false`, `ng lint` (angular-eslint).
- `deploy.sh` commits and pushes everything (`git add .`) and pulls on a server. Since the app isn't hosted, leave it alone, or delete it.

## 6. Order and size

| Step | What | Size | Needed? |
|---|---|---|---|
| F0 | commit/stash WIP, `npm ci`, `ng build` baseline | small | yes |
| F1 | interceptor, guards, found-pet, register, key | 1–2 days | **yes**, the app is partly broken without it |
| F2 | Angular 16 → current | 1–3 days | yes, 16 gets no fixes |
| F3 | standalone / control flow / signals | 1–2 days | optional |
| F4 | generated client, tests, CI | 1–2 days | recommended |
