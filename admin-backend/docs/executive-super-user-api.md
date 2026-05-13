# Executive (Super User) admin API

Admin JWT (`Authorization: Bearer …`) required for all routes below. Base path: `/api`.

Bizwy mapping: `user_type = 4` for Executive (legacy UI label “Super User”).

## List modules enabled for a company

`GET /companies/{company_id}/super-users/modules`

Returns `{ success, data: [{ module_id, module_name, defaults: { Access_priv, Read_priv, … } }] }`.

## Create executive

`POST /companies/{company_id}/super-user`

JSON or `multipart/form-data` (when avatar included).

Body fields: `first_name`, `last_name`, `mobile`, `email`, **`branch_ids`** (required array of **exactly one** `branch_id` integer — must reference an **active** branch for that company), optional profile fields, optional `permissions`, optional `confirm_convert_owner`, `confirm_promote_customer` (after `422` conflict).

Conflict response `422`: `{ success: false, conflict: "OWNER_REQUIRES_CONFIRM" | "CUSTOMER_REQUIRES_PROMOTE", message, data }`.

## Update executive

`PUT /companies/{company_id}/super-users/{user_id}`

JSON or multipart. Fields: profile fields (optional), `permissions` (optional), `is_active` (boolean), `avatar`, `remove_avatar`. **`mobile` is not accepted** — the login mobile stays unchanged.

## Reactivate / PIN email

- `PATCH /companies/{company_id}/super-users/{user_id}/reactivate` — sets mapping active.
- `POST …/reset-pin`, `POST …/resend-pin` — regenerate PIN and email (active executive only).

## Global list

`GET /admin/super-users?company_id=&search=&status=all|active|inactive&page=&per_page=`

Legacy `user_type` values (e.g. old Super Users stored as `3` Owner) are not migrated automatically; handle per tenant if needed.
