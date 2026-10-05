@extends('layouts.auth2')

@section('title', 'Privacy Policy')

@section('content')
    <div class="row tw-justify-center">
        <div class="col-md-8 col-sm-10 col-xs-12">
            <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 md:tw-p-8 tw-mt-6 tw-mb-8">
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-4 tw-flex-wrap tw-mb-5">
                    <div>
                        <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-m-0">Privacy Policy</h1>
                        <p class="tw-text-sm tw-text-gray-500 tw-mt-2 tw-mb-0">Effective date: {{ now()->format('F j, Y') }}</p>
                    </div>
                    <a href="{{ url('/') }}" class="tw-inline-flex tw-items-center tw-rounded-full tw-bg-blue-50 tw-text-blue-700 tw-px-4 tw-py-2 tw-text-sm tw-font-medium hover:tw-text-blue-800 hover:tw-bg-blue-100">
                        Back to site
                    </a>
                </div>

                <div class="tw-space-y-6 tw-text-sm tw-leading-7 tw-text-gray-700">
                    <section>
                        <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900">Information we collect</h2>
                        <p>We collect the information needed to operate the BreMac360 POS platform, including account details, business profile data, sales transactions, customer records, device information, and activity logs generated when you use the website or mobile app.</p>
                    </section>

                    <section>
                        <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900">How we use information</h2>
                        <p>We use this information to authenticate users, process sales, manage inventory, generate reports, improve reliability, troubleshoot support issues, and protect the platform against fraud, abuse, and unauthorized access.</p>
                    </section>

                    <section>
                        <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900">Mobile app data</h2>
                        <p>The mobile app may send device name, app version, signed-in account details, and usage data required to sync sales, retrieve receipts, and securely open connected website pages inside the app.</p>
                    </section>

                    <section>
                        <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900">Sharing of information</h2>
                        <p>We do not sell your personal information. Data may be shared only with service providers and integrations necessary to run the system, such as payment providers, hosting infrastructure, notification channels, and technical support workflows.</p>
                    </section>

                    <section>
                        <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900">Security and retention</h2>
                        <p>We use authentication controls, encrypted HTTPS connections, access restrictions, and server-side logging to protect your data. We retain information for operational, legal, accounting, and security purposes for as long as reasonably necessary.</p>
                    </section>

                    <section>
                        <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900">Your choices</h2>
                        <p>You may request updates to your account information, review business data stored in the system, or contact the administrator for account deactivation, data correction, or support regarding privacy questions.</p>
                    </section>

                    <section>
                        <h2 class="tw-text-lg tw-font-semibold tw-text-gray-900">Contact</h2>
                        <p>If you have questions about this Privacy Policy, please contact the business or system administrator responsible for your BreMac360 POS deployment.</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection