# Badging users API

This API lets the external badging service retrieve attendee identity and organization details from HYGIE-CLEAN EXPO.

## Authentication

Every request must include the shared API token in the `Authorization` header:

```http
Authorization: Bearer <BADGING_API_TOKEN>
Accept: application/json
```

The server administrator configures `BADGING_API_TOKEN` in the Laravel application's environment. The token must be shared with the badging team through a secure channel. Requests without a valid token receive `401 Unauthorized`.

## Endpoint

```http
GET /api/integrations/badging/users
```

Production base URL depends on the deployment. Append the endpoint path above to that base URL.

## Query parameters

All parameters are optional.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `profile_complete` | `true` or `false` | `false` | Use `true` to include only users whose password is set and whose required profile fields do not contain the app's `N/A` placeholder. `false` or omitted includes all users. |
| `company_id` | integer | — | Return users linked to this company ID. |
| `search` | string, max 100 characters | — | Case-insensitive partial match on first name, last name, job title, or company name. |
| `page` | positive integer | `1` | Page number. |
| `per_page` | integer, 1–500 | `100` | Number of records per page. |

### Examples

Retrieve all users:

```http
GET /api/integrations/badging/users
```

Retrieve only profile-complete users:

```http
GET /api/integrations/badging/users?profile_complete=true
```

Search within one company, 50 users per page:

```http
GET /api/integrations/badging/users?company_id=12&search=manager&per_page=50&page=1
```

## Successful response

```json
{
  "success": true,
  "data": [
    {
      "name": "Amina",
      "last_name": "Example",
      "company_name": "Example Company",
      "job_title": "Operations Manager"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 100,
    "total": 1,
    "last_page": 1
  }
}
```

`company_name` and `job_title` may be `null`. The external service should follow the pagination values until it has retrieved all pages. Records are returned in ascending user ID order for stable pagination during a sync.

## Errors

- `401 Unauthorized`: token is missing or invalid.
- `422 Unprocessable Entity`: one or more query parameters are invalid; the response contains an `errors` object with details.
