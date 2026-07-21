# Notify.Events – Ultimate notifications for OpenCart 3

Real‑time store notifications for [Notify.Events](https://notify.events). The extension listens to core OpenCart events (new order, order status change, new customer, returns, out‑of‑stock) and pushes a customizable message to a Notify.Events channel, which then fans out to 50+ delivery methods (Telegram, Viber, Slack, SMS, e‑mail, push, etc.).

Messages are built from templates with placeholder **tags**, so each event can carry exactly the data you need — including a full **product list with options** for orders.

---

## Supported OpenCart versions

OpenCart **3.0.x** — from 3.0.0.0 through 3.0.3.8. Tested on **3.0.3.8**.

> Not compatible with OpenCart 2.x (different template engine and directory structure).

---

## Repository layout

```
upload/                      Extension source (admin + catalog, extension/report/)
install.xml                  OCMOD metadata (name, version)
notify.events.ocmod.zip      Installable package
```

---

## Installation

1. **Admin → Extensions → Installer** → upload `notify.events.ocmod.zip`.
2. **Admin → Extensions → Modifications** → click **Refresh**.
3. **Admin → Extensions → Extensions → Reports** → find **Notify.Events** → click **Install** (green **+**).
4. Click **Edit** to open the configuration screen.

Uninstalling removes the plugin tables and unregisters its event triggers.

---

## Configuration

1. **Channels** — create a channel and paste the **source token** from your Notify.Events channel. You can add several channels (e.g. one per team / delivery method).
2. **Events** — add an event: pick the event type, write the **subject** and **message** template (using the tags below), choose the target channel(s) and a **priority**, then enable it.
3. Use **Test** to send a sample message to the selected channel.

Messages support simple multi‑line text formatting; line breaks in the template are preserved.

### Priorities

`highest` · `high` · `normal` · `low` · `lowest` — subscribers can filter what they receive per priority.

---

## Supported events

| Event | Fires when |
|---|---|
| `order_new` | A new order is placed |
| `order_status_change` | An order's status changes |
| `user_new` | A customer registers |
| `return_new` | A product return is submitted |
| `return_status_change` | A return's status changes |
| `product_out_of_stock` | An ordered product has zero stock |

---

## Template tags

Tags are written as `[tag_name]` in the subject and message. Available tags depend on the event.

### Store
`[store_name]` · `[store_url]`

### Order
`[order_id]` · `[order_total]` · `[order_status]` · `[order_currency]` · `[order_created_at]` · **`[order_products]`**

`[order_products]` renders a multi‑line list of the order's line items — product name, quantity, price (in the order currency) and each product's selected **options**, for example:

```
- Apple Cinema 30" (x2) - $110.00
    * Delivery Date: 2026-08-01
    * Size: Large
- MacBook Pro (x1) - $2,000.00
```

### Payment / Shipping address
`[order_payment_firstname]` · `[order_payment_lastname]` · `[order_payment_company]` · `[order_payment_postcode]` · `[order_payment_city]` · `[order_payment_zone]` · `[order_payment_country]` · `[order_payment_method]`
(and the matching `[order_shipping_*]` tags)

### Customer
`[customer_id]` · `[customer_firstname]` · `[customer_lastname]` · `[customer_email]` · `[customer_telephone]`

### Product (out‑of‑stock / return events)
`[product_id]` · `[product_name]` · `[product_model]` · `[product_quantity]` · `[product_stock_status]` · `[product_manufacturer]` · `[product_price]`

### Return
`[return_id]` · `[return_reason]` · `[return_action]` · `[return_status]` · `[return_created_at]` · `[return_comment]`

### Tags exposed per event

| Event | Store | Order | Payment/Shipping | Customer | Product | Return |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `order_new` | ✅ | ✅ | ✅ | ✅ | | |
| `order_status_change` | ✅ | ✅ | ✅ | ✅ | | |
| `user_new` | ✅ | | | ✅ | | |
| `return_new` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `return_status_change` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `product_out_of_stock` | ✅ | | | | ✅ | |

---

## How it works

On install the extension creates two tables (`*_ne_channel`, `*_ne_event`) and registers OpenCart event triggers pointing at its event handler. When a trigger fires, the handler resolves the subscribed events, builds each message from its template by substituting tags, and POSTs the result to the Notify.Events channel API using the channel token. Order line items and their options are read from the order via the core order model.

---

## Development notes

The `.ocmod.zip` package must use forward‑slash entry paths so it installs correctly on Linux hosts. After editing files under `upload/`, rebuild the package and bump the version in `install.xml`.

---

## Version history

- **1.1** — Added `[order_products]` tag: order line items with quantity, price and selected options.
- **1.0** — Initial release.

---

## Links

- Notify.Events: https://notify.events
- Marketplace: https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=42809
