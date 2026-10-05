<div class="header_language_area d-flex flex-wrap">
    <ul>
        <li>
            @if (count(allLanguages()?->where('status', 1)) > 1)
                <form action="{{ route('set-language') }}" class="change-language-header-mobile" method="GET">
                    <select name="code" class="select_js set-language-header-mobile">
                        @forelse (allLanguages()?->where('status', 1) as $language)
                            <option value="{{ $language->code }}"
                                {{ getSessionLanguage() == $language->code ? 'selected' : '' }}>
                                {{ $language->name }}
                            </option>
                        @empty
                            <option value="en" {{ getSessionLanguage() == 'en' ? 'selected' : '' }}>
                                {{ __('English') }}
                            </option>
                        @endforelse
                    </select>
                </form>
            @endif
        </li>
        <li>
            @if (count(allCurrencies()?->where('status', 'active')) > 1)
                <form action="{{ route('set-currency') }}" class="change-currency-header-mobile" method="GET">
                    <select name="currency" class="set-currency-header-mobile select_js">
                        @forelse (allCurrencies()?->where('status', 'active') as $currency)
                            <option value="{{ $currency->currency_code }}"
                                {{ getSessionCurrency() == $currency->currency_code ? 'selected' : '' }}>
                                {{ $currency->currency_name }}
                            </option>
                        @empty
                            <option value="KES" {{ getSessionCurrency() == 'KES' ? 'selected' : '' }}>
                                {{ __('KES') }}
                            </option>
                        @endforelse
                    </select>
                </form>
            @endif
        </li>
    </ul>
</div>
