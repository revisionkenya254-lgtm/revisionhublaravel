<div class="main-sidebar">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}"><img class="admin_logo" src="{{ asset($setting->logo) ?? '' }}"
                    alt="{{ $setting->app_name ?? '' }}"></a>
        </div>

        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('admin.dashboard') }}"><img src="{{ asset($setting->favicon) ?? '' }}"
                    alt="{{ $setting->app_name ?? '' }}"></a>
        </div>

        <ul class="sidebar-menu">
            @adminCan('dashboard.view')
                <li class="{{ isRoute('admin.dashboard', 'active') }}">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>
                </li>
            @endadminCan

            @if(checkAdminHasPermission('course.management') || checkAdminHasPermission('course.certificate.management') || checkAdminHasPermission('badge.management') || checkAdminHasPermission('blog.view'))
                <li class="menu-header">{{ __('Manage Contents') }}</li>

                @if (Module::isEnabled('Course') && checkAdminHasPermission('course.management'))
                    @include('course::sidebar')

                    <li
                        class="nav-item dropdown {{ isRoute(['admin.course-category.*', 'admin.course-sub-category.*', 'admin.course-sub-categories.*', 'admin.course-grand-categories.*'], 'active') }}">
                        <a href="javascript:void()" class="nav-link has-dropdown">
                            <i class="fas fa-layer-group"></i>
                            <span>{{ __('Manage Categories') }}</span>
                        </a>

                        <ul class="dropdown-menu">
                            <li class="{{ isRoute('admin.course-category.*', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.course-category.index') }}">
                                    {{ __('Categories') }}
                                </a>
                            </li>
                            <li class="{{ isRoute('admin.course-sub-categories.*', 'active') }} {{ isRoute('admin.course-sub-category.*', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.course-sub-categories.index') }}">
                                    {{ __('Sub Categories') }}
                                </a>
                            </li>
                            <li class="{{ isRoute('admin.course-grand-categories.*', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.course-grand-categories.index') }}">
                                    {{ __('Grand Categories') }}
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="{{ isRoute('admin.course-language.*', 'active') }}">
                        <a class="nav-link" href="{{ route('admin.course-language.index') }}">
                            <i class="fas fa-language"></i>
                            <span>{{ __('Languages') }}</span>
                        </a>
                    </li>

                    <li class="nav-item dropdown {{ isRoute(['admin.past-papers.*'], 'active') }}">
                        <a href="javascript:void()" class="nav-link has-dropdown">
                            <i class="fas fa-file-alt"></i>
                            <span>{{ __('Manage Past Papers') }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="{{ isRoute(['admin.past-papers.index', 'admin.past-papers.create', 'admin.past-papers.edit'], 'active') }}">
                                <a class="nav-link" href="{{ route('admin.past-papers.index') }}">{{ __('Past Papers') }}</a>
                            </li>
                            <li class="{{ isRoute('admin.past-papers.reviews.*', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.past-papers.reviews.index') }}">{{ __('Past Paper Reviews') }}</a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown {{ isRoute(['admin.predictions.*'], 'active') }}">
                        <a href="javascript:void()" class="nav-link has-dropdown">
                            <i class="fas fa-file-signature"></i>
                            <span>{{ __('Manage Predictions') }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="{{ isRoute(['admin.predictions.index', 'admin.predictions.create', 'admin.predictions.edit'], 'active') }}">
                                <a class="nav-link" href="{{ route('admin.predictions.index') }}">{{ __('Predictions') }}</a>
                            </li>
                            <li class="{{ isRoute('admin.predictions.reviews.*', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.predictions.reviews.index') }}">{{ __('Prediction Reviews') }}</a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown {{ isRoute(['admin.notes.*'], 'active') }}">
                        <a href="javascript:void()" class="nav-link has-dropdown">
                            <i class="fas fa-sticky-note"></i>
                            <span>{{ __('Manage Notes') }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="{{ isRoute(['admin.notes.index', 'admin.notes.create', 'admin.notes.edit'], 'active') }}">
                                <a class="nav-link" href="{{ route('admin.notes.index') }}">{{ __('Notes') }}</a>
                            </li>
                            <li class="{{ isRoute('admin.notes.reviews.*', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.notes.reviews.index') }}">{{ __('Note Reviews') }}</a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown {{ isRoute(['admin.quizzes.*'], 'active') }}">
                        <a href="javascript:void()" class="nav-link has-dropdown">
                            <i class="fas fa-question-circle"></i>
                            <span>{{ __('Manage Quizzes') }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="{{ isRoute(['admin.quizzes.index', 'admin.quizzes.create', 'admin.quizzes.edit'], 'active') }}">
                                <a class="nav-link" href="{{ route('admin.quizzes.index') }}">{{ __('Quizzes') }}</a>
                            </li>
                            <li class="{{ isRoute('admin.quizzes.reviews.*', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.quizzes.reviews.index') }}">{{ __('Quiz Reviews') }}</a>
                            </li>
                        </ul>
                    </li>
                @endif

                @if (Module::isEnabled('CertificateBuilder') && checkAdminHasPermission('course.certificate.management'))
                    @include('certificatebuilder::sidebar')
                @endif

                @if (Module::isEnabled('Badges') && checkAdminHasPermission('badge.management'))
                    @include('badges::sidebar')
                @endif

                @if (Module::isEnabled('Blog'))
                    @include('blog::sidebar')
                @endif
            @endif

            @if(checkAdminHasPermission('order.management') || checkAdminHasPermission('coupon.management') || checkAdminHasPermission('withdraw.management') && checkAdminHasPermission('refund'))
                <li class="menu-header">{{ __('Manage Orders') }}</li>

                @if (Module::isEnabled('Order') && checkAdminHasPermission('order.management'))
                    @include('order::sidebar')
                @endif

                @if (Module::isEnabled('Coupon') && checkAdminHasPermission('coupon.management'))
                    @include('coupon::sidebar')
                @endif

                @if (Module::isEnabled('PaymentWithdraw') && checkAdminHasPermission('withdraw.management'))
                    @include('paymentwithdraw::admin.sidebar')
                @endif
                @if (Module::isEnabled('Refund') && checkAdminHasPermission('refund'))
                    @include('refund::admin.sidebar')
                @endif
                @adminCan('enrollment.manual.view')
                    <li class="{{ isRoute('admin.manual-enrollment.*', 'active') }}">
                        <a class="nav-link" href="{{ route('admin.manual-enrollment.index') }}">
                            <i class="fas fa-user-check"></i>
                            <span>{{ __('Manual Enrollment') }}</span>
                        </a>
                    </li>
                @endadminCan
            @endif

            @if(checkAdminHasPermission('instructor.request.list') || checkAdminHasPermission('customer.view') || checkAdminHasPermission('location.view'))
                <li class="menu-header">{{ __('Manage Users') }}</li>
                @if (
                    (Module::isEnabled('InstructorRequest') && checkAdminHasPermission('instructor.request.list')) ||
                        checkAdminHasPermission('instructor.request.setting'))
                    @include('instructorrequest::sidebar')
                @endif

                @if (Module::isEnabled('Customer') && checkAdminHasPermission('customer.view'))
                    @include('customer::sidebar')
                @endif

                @if (Module::isEnabled('Location') && checkAdminHasPermission('location.view'))
                    @include('location::sidebar')
                @endif
            @endif

            @if(checkAdminHasPermission('appearance.management') || checkAdminHasPermission('section.management') || checkAdminHasPermission('footer.management') || checkAdminHasPermission('brand.managemen'))
                <li class="menu-header">{{ __('Site Contents') }}</li>
                @if (Module::isEnabled('SiteAppearance') && checkAdminHasPermission('appearance.management'))
                    @include('siteappearance::sidebar')
                @endif

                @if (Module::isEnabled('Frontend') && checkAdminHasPermission('section.management'))
                    @include('frontend::sidebar')
                @endif

                @if (Module::isEnabled('Brand') && checkAdminHasPermission('brand.management'))
                    @include('brand::sidebar')
                @endif

                @if (Module::isEnabled('FooterSetting') && checkAdminHasPermission('footer.management'))
                    @include('footersetting::sidebar')
                @endif
            @endif


            @if(checkAdminHasPermission('menu.view') || checkAdminHasPermission('page.management') || checkAdminHasPermission('social.link.management') || checkAdminHasPermission('faq.view'))
                <li class="menu-header">{{ __('Manage Website') }}</li>

                @if (Module::isEnabled('MenuBuilder') && checkAdminHasPermission('menu.view'))
                    @include('menubuilder::sidebar')
                @endif
                
                @if (Module::isEnabled('PageBuilder') && checkAdminHasPermission('page.management'))
                    @include('pagebuilder::sidebar')
                @endif

                @if (Module::isEnabled('SocialLink') && checkAdminHasPermission('social.link.management'))
                    @include('sociallink::sidebar')
                @endif

                @if (Module::isEnabled('Faq') && checkAdminHasPermission('faq.view'))
                    @include('faq::sidebar')
                @endif
            @endif

            @if(checkAdminHasPermission('setting.view') || checkAdminHasPermission('basic.payment.view') || checkAdminHasPermission('payment.view') || checkAdminHasPermission('currency.view') || checkAdminHasPermission('role.view') || checkAdminHasPermission('admin.view') || checkAdminHasPermission('addon.view'))
                <li class="menu-header">{{ __('Settings') }}</li>
                <li class="{{ isRoute('admin.settings', 'active') }}">
                    <a class="nav-link" href="{{ route('admin.settings') }}"><i class="fas fa-cog"></i>
                        <span>{{ __('Settings') }}</span>
                    </a>
                </li>
                @if (checkAdminHasPermission('setting.view'))
                    <li class="nav-item dropdown {{ isRoute(['admin.crediential-setting', 'admin.bunny-dashboard'], 'active') }}">
                        <a href="javascript:void(0)" class="nav-link has-dropdown">
                            <i class="fas fa-cloud"></i><span>{{ __('Bunny Cloud') }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="{{ isRoute('admin.crediential-setting', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.crediential-setting') }}">
                                    {{ __('Bunny Settings') }}
                                </a>
                            </li>
                            <li class="{{ isRoute('admin.bunny-dashboard', 'active') }}">
                                <a class="nav-link" href="{{ route('admin.bunny-dashboard') }}">
                                    {{ __('Bunny Usage') }}
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @endif

            @if(checkAdminHasPermission('newsletter.view') || checkAdminHasPermission('testimonial.view') || checkAdminHasPermission('contect.message.view'))
                <li class="menu-header">{{ __('Utility') }}</li>

                @if (Module::isEnabled('NewsLetter') && checkAdminHasPermission('newsletter.view'))
                    @include('newsletter::sidebar')
                @endif

                @if (Module::isEnabled('Testimonial') && checkAdminHasPermission('testimonial.view'))
                    @include('testimonial::sidebar')
                @endif

                @if (Module::isEnabled('ContactMessage') && checkAdminHasPermission('contect.message.view'))
                    @include('contactmessage::sidebar')
                @endif
            @endif
            <li class="nav-item dropdown {{ isRoute('admin.addon.*') ? 'active' : '' }}" id="addon_sidemenu">
                <a class="nav-link has-dropdown" data-toggle="dropdown" href="#"><i class="fas fa-gem"></i>
                    <span>{{ __('Manage Addons') }} </span>
                </a>
                <ul class="dropdown-menu addon_menu">
                    @includeIf('admin.addons')
                </ul>
            </li>
        </ul>
        <div class="py-3 text-center">
            <div class="btn-sm-group-vertical version_button" role="group" aria-label="Responsive button group">
                <button class="btn btn-primary logout_btn mt-2" disabled>{{ __('version') }}
                    {{ $setting->version ?? '1.0.0' }}</button>
                <button class="btn btn-danger mt-2"
                    onclick="event.preventDefault(); $('#admin-logout-form').trigger('submit');"><i
                        class="fas fa-sign-out-alt"></i></button>
            </div>
        </div>
    </aside>
</div>
