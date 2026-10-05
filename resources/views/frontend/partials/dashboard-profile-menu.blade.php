@php
    $name = $name ?? '';
    $role = $role ?? '';
    $image = $image ?? '';
    $profileUrl = $profileUrl ?? '#';
    $logoutFormId = $logoutFormId ?? 'logout-form';
@endphp

<div class="dashboard-profile-menu" data-dashboard-profile-menu>
    <button type="button" class="dashboard-profile-link" data-dashboard-profile-toggle aria-expanded="false">
        <span class="dashboard-profile-link__avatar">
            <img src="{{ asset($image) }}" alt="{{ $name }}">
        </span>
        <span class="dashboard-profile-link__text">
            <strong>{{ $name }}</strong>
            <small>{{ $role }}</small>
        </span>
        <i class="fas fa-chevron-down dashboard-profile-link__caret"></i>
    </button>

    <div class="dashboard-profile-menu__panel" data-dashboard-profile-panel>
        <a href="{{ $profileUrl }}" class="dashboard-profile-menu__item">
            <i class="far fa-user"></i>
            <span>{{ __('Profile') }}</span>
        </a>
        <a href="{{ route('logout') }}" class="dashboard-profile-menu__item dashboard-profile-menu__item--danger" data-dashboard-logout-link>
            <i class="fas fa-sign-out-alt"></i>
            <span>{{ __('Logout') }}</span>
        </a>
    </div>
</div>

@once
    @push('styles')
        <style>
            .dashboard-profile-menu {
                position: relative;
            }

            .dashboard-profile-link {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 5px 9px 5px 5px;
                border: 1px solid #d9e0eb;
                border-radius: 17px;
                background: #fff;
                color: #24304f;
                box-shadow: 0 10px 20px rgba(20, 33, 61, 0.05);
                transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
            }

            .dashboard-profile-link:hover {
                transform: translateY(-1px);
            }

            .dashboard-profile-link__avatar {
                width: 36px;
                height: 36px;
                overflow: hidden;
                border-radius: 50%;
                background: linear-gradient(135deg, #5b8def, #35c78a);
                flex: 0 0 auto;
            }

            .dashboard-profile-link__avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .dashboard-profile-link__text {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 1px;
                text-align: left;
                min-width: 0;
                line-height: 1.1;
            }

            .dashboard-profile-link__text strong {
                font-size: 13px;
                font-weight: 700;
                color: #16213f;
            }

            .dashboard-profile-link__text small {
                color: #73809b;
                font-size: 11px;
            }

            .dashboard-profile-link__caret {
                font-size: 11px;
                color: #8a94ad;
            }

            .dashboard-profile-menu__panel {
                position: absolute;
                top: calc(100% + 10px);
                right: 0;
                z-index: 60;
                display: none;
                min-width: 220px;
                padding: 8px;
                border: 1px solid #d9e0eb;
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 18px 40px rgba(20, 33, 61, 0.12);
            }

            .dashboard-profile-menu.is-open .dashboard-profile-menu__panel {
                display: grid;
                gap: 4px;
            }

            .dashboard-profile-menu__item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 11px 12px;
                border-radius: 12px;
                color: #24304f;
                font-size: 13px;
                font-weight: 600;
                transition: background-color 0.18s ease, color 0.18s ease;
            }

            .dashboard-profile-menu__item i {
                width: 18px;
                color: #73809b;
                text-align: center;
            }

            .dashboard-profile-menu__item:hover {
                background: rgba(91, 141, 239, 0.08);
                color: #5f57d6;
            }

            .dashboard-profile-menu__item--danger {
                color: #dc2626;
            }

            .dashboard-profile-menu__item--danger i {
                color: #dc2626;
            }

            .dashboard-profile-menu__item--danger:hover {
                background: rgba(220, 38, 38, 0.08);
                color: #b91c1c;
            }

            @media (max-width: 767.98px) {
                .dashboard-profile-link__text,
                .dashboard-profile-link__caret {
                    display: none;
                }

                .dashboard-profile-menu__panel {
                    right: 0;
                    left: auto;
                    min-width: 200px;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const menu = document.querySelector('[data-dashboard-profile-menu]');
                const toggle = document.querySelector('[data-dashboard-profile-toggle]');
                const panel = document.querySelector('[data-dashboard-profile-panel]');
                const logoutLink = document.querySelector('[data-dashboard-logout-link]');
                const logoutFormId = @json($logoutFormId);

                const closeMenu = () => {
                    menu?.classList.remove('is-open');
                    toggle?.setAttribute('aria-expanded', 'false');
                };

                toggle?.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (!menu) {
                        return;
                    }

                    const willOpen = !menu.classList.contains('is-open');
                    menu.classList.toggle('is-open', willOpen);
                    toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });

                logoutLink?.addEventListener('click', function (event) {
                    event.preventDefault();
                    document.getElementById(logoutFormId)?.submit();
                });

                panel?.addEventListener('click', function (event) {
                    event.stopPropagation();
                });

                document.addEventListener('click', function (event) {
                    if (menu && !menu.contains(event.target)) {
                        closeMenu();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeMenu();
                    }
                });
            });
        </script>
    @endpush
@endonce
