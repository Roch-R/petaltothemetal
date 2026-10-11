# Flower shop design

Date: 2026-10-08
Status: Scope, architecture, database, and checkout design approved by the user.
Working project name: Petal to the Metal (taken from the workspace folder).
Purpose: School project demonstrating a complete flower ordering and shop management flow.

## Confirmed requirements

- Frontend: HTML, CSS, and vanilla JavaScript.
- Backend: PHP.
- Database: MySQL.
- Customer storefront and admin panel.
- Customer registration and login required before adding items to the cart and placing orders.
- Public browsing of the homepage, catalog, and product details.
- Simulated online payment; no real charges or payment gateway integration.
- Delivery and store pickup, selected at checkout with a preferred date.
- Soft, elegant appearance using blush pink, cream, sage green, and large flower photographs.
- Responsive layouts for phones and computers.

## Architecture decision

Three approaches were considered: PHP pages and forms, a JavaScript frontend with a PHP API, and PHP pages with selective JavaScript. The approved approach is PHP pages with selective JavaScript. PHP handles authentication, product retrieval, checkout validation, order persistence, and admin authorization. JavaScript handles filters, quantity controls, and checkout interactions. PHP sessions hold login state and the shopping cart; MySQL holds persistent data. No frontend framework is required.

## Customer experience

The homepage introduces the shop and highlights arrangements. The catalog displays available products and category filters. Product details show a photograph, description, current price, and availability. Customers register or log in before adding products to their cart. The cart allows quantity changes and item removal.

Checkout collects the fulfillment method, a preferred date, and an optional gift message. Delivery also requires the recipient name, phone number, and address. Pickup does not require an address. The payment screen clearly states that payment is simulated. Successful checkout displays a confirmation and order reference. Customers can view their own order history, details, and status.

## Admin experience

An admin account is required for every admin page and action. Staff manage categories, arrangements, prices, stock, and orders. Orders follow Pending -> Preparing -> Out for Delivery (delivery) or Ready for Pickup (pickup) -> Completed. Eligible orders can be cancelled. PHP enforces access controls and status changes; controls in the interface are not the sole enforcement mechanism.

## Database

| Table | Stored information |
| --- | --- |
| users | Account ID, name, email, password hash, and customer/admin role |
| categories | Category ID and name |
| products | Product ID, category, name, description, image reference, price, and stock quantity |
| orders | Customer reference, fulfillment method, scheduled date, recipient/delivery details when applicable, gift message, total, and order status |
| order_items | Order reference, product reference, purchased product name, quantity, and unit price at purchase |
| payments | Order reference, simulated payment status, and demo reference number |

Use foreign keys for relationships and decimal monetary values. Email addresses should be unique. Order items retain purchase-time names and prices so later product edits do not change order history. Product management must preserve historical orders.

## Checkout and stock rules

PHP validates authentication, quantities, stock availability, fulfillment details, and the selected date. Past dates are rejected. Required fields depend on delivery versus pickup. Prices and totals are calculated from database values rather than trusted browser input.

On successful simulated payment, create the order, order items, and simulated payment record, and reduce stock in one database transaction. Recheck stock while completing the transaction to prevent overselling. If any operation fails, roll back the changes and retain the cart. Clear the cart only after the transaction succeeds. Repeated submission must not create duplicate orders or reduce stock twice.

Cancelling an eligible order restores stock once and marks the simulated payment refunded. Completed orders are not eligible for cancellation. The precise cancellation cutoff should be made explicit in the implementation plan.

## Security and error handling

Store securely hashed passwords and verify them with PHP password functions. Use prepared database queries, server-side authorization, CSRF protection for state-changing forms, escaped output, and safe session handling. Customers may access only their own orders; admin actions require an admin role.

Use clear field validation and actionable messages for invalid login, missing checkout information, unavailable stock, and failed checkout. Do not expose database errors or credentials in the interface. Simulation labels must appear wherever a demo payment or refund is presented.

## Verification

Verify registration, duplicate email handling, login, logout, required customer authentication, and admin authorization. Exercise delivery and pickup independently, including required fields and date validation. Verify totals against database prices, insufficient stock, checkout persistence, rollback, repeated submission, and cancellation stock restoration. Check that customers cannot read another customer's orders. Check purchase history after product price changes. Review mobile and desktop layouts and confirm all payment screens clearly identify the simulation.

## Implementation planning details still to settle

Confirm the displayed shop name, currency, delivery fee policy, operating timezone, and local PHP/MySQL environment during implementation planning. Define the allowed cancellation cutoff and permitted admin status transitions. These details were not chosen during brainstorming and must not be presented as user-approved requirements.

## Scope boundary

The first version is a school demonstration. Real payment processing, courier integrations, automated messaging, and additional business features are outside the approved scope. Application code has not yet been implemented.
