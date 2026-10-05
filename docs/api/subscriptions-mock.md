# Subscription API mock requests and responses

Use `{{baseUrl}} = http://revisionhub.test/api` locally (or your staging API URL). All endpoints except **List plans** require:

```http
Authorization: Bearer {{accessToken}}
Accept: application/json
```

## 1. List plans

```http
GET {{baseUrl}}/subscriptions/plans
```

```json
{
  "status": "success",
  "data": [
    {
      "id": "month-1",
      "name": "1 Month Plan",
      "amount": 500,
      "price": "KES 500",
      "period": "month",
      "billing": "Billed every month",
      "months": 1,
      "ai_bonus_credits": 1500
    },
    {
      "id": "month-3",
      "name": "3 Months Plan",
      "amount": 1000,
      "price": "KES 1,000",
      "period": "3 months",
      "billing": "Billed every 3 months",
      "months": 3,
      "ai_bonus_credits": 3200
    }
  ]
}
```

## 2. Current subscription

```http
GET {{baseUrl}}/subscriptions/me
```

Active subscription response:

```json
{
  "status": "success",
  "data": {
    "active": true,
    "plan": {
      "id": "month-1",
      "name": "1 Month Plan",
      "amount": 500,
      "months": 1
    },
    "plan_id": "month-1",
    "started_at": "2026-09-27T10:00:00+03:00",
    "expires_at": "2026-10-27T10:00:00+03:00"
  }
}
```

No active subscription response:

```json
{
  "status": "success",
  "data": {
    "active": false,
    "plan": null,
    "plan_id": null,
    "started_at": null,
    "expires_at": null
  }
}
```

## 3. Create a checkout order

```http
POST {{baseUrl}}/subscriptions/checkout
Content-Type: application/json

{
  "plan_id": "month-1"
}
```

```json
{
  "status": "success",
  "state": "ready_for_payment",
  "message": "Subscription order created. Enter your M-Pesa number to continue.",
  "data": {
    "order_id": "SUBABC1234",
    "state": "ready_for_payment",
    "payment_status": "pending",
    "amount": 500,
    "currency": "KES",
    "plan": {
      "id": "month-1",
      "name": "1 Month Plan",
      "amount": 500,
      "months": 1
    },
    "ai_bonus_credits": 1500
  }
}
```

Invalid plan (`422`):

```json
{
  "status": "error",
  "message": "The selected subscription plan is invalid."
}
```

## 4. Start the M-Pesa payment

```http
POST {{baseUrl}}/subscriptions/orders/SUBABC1234/payment
Content-Type: application/json

{
  "phone_number": "254712345678"
}
```

```json
{
  "status": "pending",
  "state": "awaiting_pin",
  "message": "Enter your M-Pesa PIN on your phone to complete the payment.",
  "data": {
    "order_id": "SUBABC1234",
    "checkout_request_id": "ws_CO_270920261000000001",
    "amount": 500,
    "currency": "KES",
    "source": "subscription",
    "customer_message": "Success. Request accepted for processing."
  }
}
```

Invalid phone (`422`):

```json
{
  "status": "error",
  "message": "Use a valid Safaricom number like 07XXXXXXXX or 2547XXXXXXXX."
}
```

## 5. Poll payment status

```http
GET {{baseUrl}}/subscriptions/orders/SUBABC1234/payment
```

Still awaiting the M-Pesa PIN (`202`):

```json
{
  "status": "pending",
  "state": "awaiting_pin",
  "message": "Enter your M-Pesa PIN on your phone to complete the payment.",
  "data": {
    "order_id": "SUBABC1234",
    "checkout_request_id": "ws_CO_270920261000000001",
    "amount": 500,
    "currency": "KES",
    "source": "subscription"
  }
}
```

Paid (`200`):

```json
{
  "status": "success",
  "state": "paid",
  "message": "Payment received. Your order is complete.",
  "data": {
    "order_id": "SUBABC1234",
    "checkout_request_id": "RKD91A8X2",
    "amount": 500,
    "currency": "KES",
    "source": "subscription"
  }
}
```

## 6. Read the subscription order

```http
GET {{baseUrl}}/subscriptions/orders/SUBABC1234
```

```json
{
  "status": "success",
  "data": {
    "order_id": "SUBABC1234",
    "state": "paid",
    "payment_status": "paid",
    "amount": 500,
    "currency": "KES",
    "plan": {
      "id": "month-1",
      "name": "1 Month Plan",
      "amount": 500,
      "months": 1
    },
    "ai_bonus_credits": 1500
  }
}
```

Unknown order (`404`):

```json
{
  "status": "error",
  "message": "Subscription order not found."
}
```
