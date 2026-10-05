@extends('frontend.layouts.master')
        @section('meta_title', $page->translation->seo_title ?? $page->translation->name)
        @section('meta_description', $page->translation->seo_description ?? $page->translation->name)
        @section('body_class', $page?->fixed_header ? 'wsus_fixed_header' : '')
        @section('contents')
        <div id="inzk" data-background="{{ url('/') }}/build-pagebuilder/assets/breadcrumb-50fd8084.webp" class="breadcrumb__area breadcrumb__bg"><div class="container"><div class="row"><div class="col-12"><div class="breadcrumb__content"><h3 class="title">Privacy Policy</h3><nav class="breadcrumb"><span property="itemListElement" typeof="ListItem"><a href="/">Home</a></span><span class="breadcrumb-separator"><i class="fas fa-angle-right"></i></span><span property="itemListElement" typeof="ListItem"><a>Privacy Policy</a></span></nav></div></div></div></div><div class="breadcrumb__shape-wrap"><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape01.svg" alt="img" class="alltuchtopdown"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape02.svg" alt="img" data-aos="fade-right" data-aos-delay="300"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape03.svg" alt="img" data-aos="fade-up" data-aos-delay="400"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape04.svg" alt="img" data-aos="fade-down-left" data-aos-delay="400"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape05.svg" alt="img" data-aos="fade-left" data-aos-delay="400"/></div></div><section class="section-py-120"><div class=" container"><div id="ixti"><p>Welcome to SkillGro! We value your privacy and are committed to protecting your personal information. This Privacy Policy outlines how we collect, use, and safeguard your data when you visit our website.</p>
<p><strong>Information We Collect</strong></p>
<ol>
<li>
<p><strong>Personal Information</strong>: When you register on our site, subscribe to our newsletter, or fill out a form, we may collect personal information such as your name, email address, phone number, and any other details you provide.</p>
</li>
<li>
<p><strong>Non-Personal Information</strong>: We may collect non-personal information about visitors whenever they interact with our site. This may include the browser name, the type of computer, and technical information about users' means of connection to our site, such as the operating system and the Internet service providers utilized and other similar information.</p>
</li>
<li>
<p><strong>Cookies and Tracking Technologies</strong>: Our site may use "cookies" to enhance user experience. Users' web browsers place cookies on their hard drive for record-keeping purposes and sometimes to track information about them. Users may choose to set their web browser to refuse cookies or to alert them when cookies are being sent. If they do so, note that some parts of the site may not function properly.</p>
</li>
</ol>
<p><strong>How We Use Collected Information</strong></p>
<p>SkillGro may collect and use users' personal information for the following purposes:</p>
<ol>
<li>
<p><strong>To Improve Customer Service</strong>: Information you provide helps us respond to your customer service requests and support needs more efficiently.</p>
</li>
<li>
<p><strong>To Personalize User Experience</strong>: We may use information in the aggregate to understand how our users as a group use the services and resources provided on our site.</p>
</li>
<li>
<p><strong>To Improve Our Site</strong>: We may use feedback you provide to improve our products and services.</p>
</li>
<li>
<p><strong>To Process Payments</strong>: We may use the information users provide about themselves when placing an order only to provide service to that order. We do not share this information with outside parties except to the extent necessary to provide the service.</p>
</li>
<li>
<p><strong>To Send Periodic Emails</strong>: We may use the email address to send user information and updates pertaining to their order. It may also be used to respond to their inquiries, questions, and/or other requests.</p>
</li>
</ol>
<p><strong>How We Protect Your Information</strong></p>
<p>We adopt appropriate data collection, storage, and processing practices and security measures to protect against unauthorized access, alteration, disclosure, or destruction of your personal information, username, password, transaction information, and data stored on our site.</p></div></div></section>
        @endsection