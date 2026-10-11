# Customer experience improvements

**Goal:** Improve the flower shop's navigation, account controls, forms, bag, and checkout while preserving the approved HTML/CSS/vanilla JavaScript/PHP stack and business rules.

**Design:** Keep cream #FFF9F3, blush #E8C2C5, sage #72806A, deep green #283A30, Caslon headings, and DM Sans text. Use 16px reading text, 14px labels/actions, at least 44px controls, visible keyboard focus, high-contrast text, and reduced-motion support. The user selected a named account menu containing My orders and Log out. It will use native details/summary, work without JavaScript, and support enhanced Escape/outside-click dismissal. Log out remains a CSRF-protected POST.

UI/UX Pro Max's SKILL.md was read and its accessibility, interaction, responsive, typography, and feedback rules applied. Its installed scripts/data entries are unresolved text links; the CLI search helpers could not run. This design system is derived manually from those rules and the project's approved visual direction.

## Implementation plan

1. **Account and navigation:** update templates/header.php, public/assets/js/main.js, and a shared public/assets/css/experience.css. Clearly separate browsing, bag, and account actions; show signed-in identity, order access, and a distinct logout menu action. Improve mobile navigation, focus, touch targets, and shared feedback. Add browser interaction coverage first and observe missing-menu failure.
2. **Forms and checkout:** update public/login.php, register.php, checkout.php, payment.php and shared checkout progress template. Add field-linked errors, error-summary focus, optional password visibility, readable progress and back links, gift-message count, and submitting states. Keep no-JavaScript forms functional. Test invalid input recovery and password controls before implementing behavior.
3. **Bag and catalog:** update public/cart.php, product.php, shop.php, JS controls, and shared login return routing. Quantity changes should save the bag and refresh trusted totals; guests should return to the arrangement they were viewing after login. Filters should apply explicitly and have a reset action. Test quantity saving and safe product return routes first.
4. **Order/admin clarity and verification:** improve readable order progress, admin logout treatment, field/action sizing and contrast. Run PHP lint, domain suites, focused UX browser checks, and full workflow QA at phone/tablet/desktop widths. Review screenshots and request code review before completion. Bring verified changes into the main project so the existing localhost preview shows the improved experience.

## Boundaries

No frontend framework, account-profile feature, payment gateway, new business workflow, or changes to transactional order/cancellation rules. Preserve native forms, CSRF, ownership checks, and historical receipts. Work in an ignored isolated worktree, use project-specific staging, and leave sibling projects untouched.
