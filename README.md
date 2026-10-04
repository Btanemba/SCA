<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Payroll

Install dependencies with `composer install` and run `php artisan migrate` before opening the payroll screens.

- CEO or ADM maintains **Academy Settings**. Only CEO can set the approver name and upload/preview the private signature. Use PNG/JPEG images, preferably with a white or transparent background.
- Complete academy details, logo, CEO name/signature, debit bank and academy account details before approval. Bank email is optional for physical delivery.
- ACC creates one payroll per month/year and enters employee bank details, basic salary, allowances and named deductions in NGN. Tax amounts are entered manually; the academy is responsible for verifying applicable Nigerian requirements.
- ACC submits; CEO approves or returns with a reason. Submitted amounts are locked. Returned payrolls can be corrected, then ACC enters a required **Response to Return** and uses **Respond & Resubmit**. The approver sees the response alongside the return reason, and all exchanges remain in audit history. Approved payrolls are immutable and cannot be deleted or reopened.
- Approval releases staff payslips and freezes letterhead, bank recipient and signature details for that payroll. **My Payslips** is available in navigation and the person's own profile.
- Approved bank letters include a beneficiary schedule, green approval stamp and CEO signature. ACC can confirm the configured recipient and email the PDF, or download/open it for printing. Email/download does not mark payroll paid.
- Configure a real Laravel mail transport before bank dispatch. The default log/array mailers do not deliver email. ACC records payment date and bank reference after payment confirmation.
- Old uploaded files are retained privately; never make the private storage directory publicly accessible. Changing settings does not change approved documents.

Focused checks: `php vendor/bin/phpunit tests/Unit/PayrollAmountsTest.php tests/Feature/PayrollTest.php`.

## Expenses

Run `php artisan migrate` to create the expense tables, then open **Expenses** as ACC or CEO.

- ACC creates an expense with title, category, purpose, date, supplier/payee and amount in NGN. Invoice and purchase receipt uploads are optional (PDF/JPEG/PNG, up to 10 MB each).
- Payee bank name, account name and a 10-digit account number must be completed before submission. Drafts and returned expenses are editable; only never-submitted drafts can be deleted.
- ACC submits; CEO approves or returns with a reason. Returned expenses require ACC's **Response to Return** before **Respond & Resubmit**. The response and original reason are displayed to the approver and retained in audit history. A preparer cannot approve their own expense. Submitted and approved financial details are locked.
- Approval requires the same Academy Settings as payroll and preserves the school letterhead, signature and debit account. Download or print the approved **Bank Payment Instruction** for physical delivery to the bank. Downloading does not mark an expense paid.
- ACC records the payment date/reference after payment. The purchase receipt can also be uploaded or replaced after approval or payment without changing approved financial details.
- Attachments use private local storage and authorized downloads. Creating, updating, approving, returning, paying, downloading and post-approval receipt changes are recorded in audit history.

Focused checks: `php vendor/bin/phpunit tests/Unit/ExpenseTest.php tests/Feature/ExpenseTest.php`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
