<x-shop::layouts.account>
    <x-slot:title>
        @lang('shop::app.layouts.catalog')
    </x-slot>

    <div class="w-full">
        <v-customer-catalog>
            <x-shop::shimmer.categories.view />
        </v-customer-catalog>
    </div>

    @push('scripts')
        <script
            type="text/x-template"
            id="v-customer-catalog-template"
        >
            <div class="flex items-start gap-10 max-lg:gap-5">
                <div v-if="! isMobile" class="panel-side journal-scroll grid max-h-[1320px] min-w-[342px] grid-cols-[1fr] overflow-y-auto overflow-x-hidden max-xl:min-w-[270px] md:max-w-[342px] md:ltr:pr-7 md:rtl:pl-7">
                    @include('shop::customers.account.catalog.category-tree')

                    <div class="border-t border-zinc-200 pt-4">
                        <v-filters
                            @filter-applied="setFilters('filter', $event)"
                            @filter-clear="clearFilters('filter', $event)"
                        >
                            <x-shop::shimmer.categories.filters />
                        </v-filters>
                    </div>
                </div>

                <div class="flex-1">
                    <div class="max-md:hidden">
                        @include('shop::categories.toolbar')
                    </div>

                    <div
                        class="mt-8 grid grid-cols-1 gap-6"
                        v-if="(filters.toolbar.applied.mode ?? filters.toolbar.default.mode) === 'list'"
                    >
                        <template v-if="isLoading">
                            <x-shop::shimmer.products.cards.list count="12" />
                        </template>

                        <template v-else>
                            <template v-if="products.length">
                                <x-shop::products.card
                                    ::mode="'list'"
                                    v-for="product in products"
                                />
                            </template>

                            <template v-else>
                                <div class="m-auto grid w-full place-content-center items-center justify-items-center py-32 text-center">
                                    <img
                                        class="max-md:h-[100px] max-md:w-[100px]"
                                        src="{{ bagisto_asset('images/thank-you.png') }}"
                                        alt="@lang('shop::app.categories.view.empty')"
                                        loading="lazy"
                                        decoding="async"
                                    />

                                    <p
                                        class="text-xl max-md:text-sm"
                                        role="heading"
                                    >
                                        @lang('shop::app.categories.view.empty')
                                    </p>
                                </div>
                            </template>
                        </template>
                    </div>

                    <div v-else class="mt-8 max-md:mt-5">
                        <template v-if="isLoading">
                            <div class="grid grid-cols-3 gap-8 max-1060:grid-cols-2 max-md:justify-items-center max-md:gap-x-4">
                                <x-shop::shimmer.products.cards.grid count="12" />
                            </div>
                        </template>

                        <template v-else>
                            <template v-if="products.length">
                                <div class="grid grid-cols-3 gap-8 max-1060:grid-cols-2 max-md:justify-items-center max-md:gap-x-4">
                                    <x-shop::products.card
                                        ::mode="'grid'"
                                        v-for="product in products"
                                    />
                                </div>
                            </template>

                            <template v-else>
                                <div class="m-auto grid w-full place-content-center items-center justify-items-center py-32 text-center">
                                    <img
                                        class="max-md:h-[100px] max-md:w-[100px]"
                                        src="{{ bagisto_asset('images/thank-you.png') }}"
                                        alt="@lang('shop::app.categories.view.empty')"
                                        loading="lazy"
                                        decoding="async"
                                    />

                                    <p
                                        class="text-xl max-md:text-sm"
                                        role="heading"
                                    >
                                        @lang('shop::app.categories.view.empty')
                                    </p>
                                </div>
                            </template>
                        </template>
                    </div>

                    <button
                        class="secondary-button mx-auto mt-14 block w-max rounded-2xl px-11 py-3 text-center text-base max-md:rounded-lg max-sm:mt-6 max-sm:px-6 max-sm:py-1.5 max-sm:text-sm"
                        @click="loadMoreProducts"
                        v-if="links.next && ! loader"
                    >
                        @lang('shop::app.categories.view.load-more')
                    </button>

                    <button
                        v-else-if="links.next"
                        class="secondary-button mx-auto mt-14 block w-max rounded-2xl px-[74.5px] py-3.5 text-center text-base max-md:rounded-lg max-md:py-3 max-sm:mt-6 max-sm:px-[50.8px] max-sm:py-1.5"
                    >
                        <img
                            class="h-5 w-5 animate-spin text-navyBlue"
                            src="{{ bagisto_asset('images/spinner.svg') }}"
                            alt="Loading"
                        />
                    </button>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-customer-catalog', {
                template: '#v-customer-catalog-template',

                data() {
                    return {
                        isMobile: window.innerWidth <= 767,

                        isLoading: true,

                        filters: {
                            toolbar: {
                                default: {},
                                applied: {},
                            },
                            filter: {},
                        },

                        products: [],

                        links: {},

                        loader: false,
                    }
                },

                computed: {
                    queryParams() {
                        let queryParams = Object.assign({}, this.filters.filter, this.filters.toolbar.applied);

                        return this.removeJsonEmptyValues(queryParams);
                    },

                    queryString() {
                        return this.jsonToQueryString(this.queryParams);
                    },
                },

                watch: {
                    queryParams() {
                        this.getProducts();
                    },

                    queryString() {
                        window.history.pushState({}, '', '?' + this.queryString);
                    },
                },

                methods: {
                    setFilters(type, filters) {
                        this.filters[type] = filters;
                    },

                    clearFilters(type, filters) {
                        this.filters[type] = {};
                    },

                    getProducts() {
                        this.isLoading = true;

                        this.$axios.get("{{ route('shop.api.products.index') }}", {
                            params: this.queryParams
                        })
                            .then(response => {
                                this.isLoading = false;

                                this.products = response.data.data;

                                this.links = response.data.links;
                            }).catch(error => {
                                console.log(error);
                            });
                    },

                    loadMoreProducts() {
                        if (! this.links.next) {
                            return;
                        }

                        this.loader = true;

                        this.$axios.get(this.links.next)
                            .then(response => {
                                this.loader = false;

                                this.products = [...this.products, ...response.data.data];

                                this.links = response.data.links;
                            }).catch(error => {
                                console.log(error);
                            });
                    },

                    setCategoryFilter(categoryId) {
                        if (categoryId) {
                            this.filters.filter.category_id = categoryId;
                        } else {
                            delete this.filters.filter.category_id;
                        }
                    },

                    removeJsonEmptyValues(params) {
                        Object.keys(params).forEach(function (key) {
                            if ((! params[key] && params[key] !== undefined)) {
                                delete params[key];
                            }

                            if (Array.isArray(params[key])) {
                                params[key] = params[key].join(',');
                            }
                        });

                        return params;
                    },

                    jsonToQueryString(params) {
                        let parameters = new URLSearchParams();

                        for (const key in params) {
                            parameters.append(key, params[key]);
                        }

                        return parameters.toString();
                    }
                },
            });

            app.component('v-filters', {
                template: '#v-filters-template',

                data() {
                    return {
                        isLoading: true,
                        filters: { available: {}, applied: {} },
                    };
                },

                mounted() {
                    this.getFilters();
                    this.setFilters();
                },

                methods: {
                    getFilters() {
                        this.$axios.get('{{ route("shop.api.categories.attributes") }}')
                            .then((response) => {
                                this.isLoading = false;
                                this.filters.available = response.data.data;
                            })
                            .catch((error) => { console.log(error); });
                    },

                    setFilters() {
                        let queryParams = new URLSearchParams(window.location.search);
                        queryParams.forEach((value, filter) => {
                            if (! ['sort', 'limit', 'mode'].includes(filter)) {
                                this.filters.applied[filter] = value.split(',');
                            }
                        });
                        this.$emit('filter-applied', this.filters.applied);
                    },

                    applyFilter(filter, values) {
                        if (values.length) {
                            this.filters.applied[filter.code] = values;
                        } else {
                            delete this.filters.applied[filter.code];
                        }
                        this.$emit('filter-applied', this.filters.applied);
                    },

                    clear() {
                        this.filters.applied = {};
                        this.$emit('filter-applied', this.filters.applied);
                    },
                },
            });

            app.component('v-filter-item', {
                template: '#v-filter-item-template',

                props: ['filter'],

                data() {
                    return {
                        options: [],
                        meta: null,
                        appliedValues: null,
                        currentPage: 1,
                        searchQuery: '',
                        isLoadingMore: true,
                        refreshKey: 0,
                    }
                },

                created() {
                    if (this.filter.type === 'price') {
                        this.appliedValues = this.$parent.$data.filters.applied[this.filter.code]?.join(',');
                    } else {
                        this.appliedValues = this.$parent.$data.filters.applied[this.filter.code] ?? [];
                    }
                },

                mounted() {
                    this.fetchFilterOptions();
                },

                watch: {
                    appliedValues: {
                        handler(newVal, oldVal) {
                            if (this.filter.type === 'price' && newVal !== oldVal && !newVal) {
                                this.refreshKey++;
                            }
                        }
                    }
                },

                methods: {
                    applyValue($event) {
                        if (this.filter.type === 'price') {
                            this.appliedValues = $event;
                            this.$emit('values-applied', this.appliedValues);
                            return;
                        }
                        this.$emit('values-applied', this.appliedValues);
                    },

                    searchOptions() {
                        this.currentPage = 1;
                        this.fetchFilterOptions(true);
                    },

                    loadMoreOptions() {
                        this.currentPage++;
                        this.fetchFilterOptions(false);
                    },

                    fetchFilterOptions(replace = true) {
                        this.isLoadingMore = true;
                        const url = '{{ route("shop.api.categories.attribute_options", "attribute_id") }}'.replace('attribute_id', this.filter.id);

                        this.$axios.get(url, {
                            params: { page: this.currentPage, search: this.searchQuery, }
                        })
                        .then(response => {
                            this.isLoadingMore = false;
                            this.options = replace ? response.data.data : [...this.options, ...response.data.data];
                            this.meta = response.data.meta;
                        })
                        .catch(error => { this.isLoadingMore = false; });
                    },
                },
            });

            app.component('v-price-filter', {
                template: '#v-price-filter-template',

                props: ['defaultPriceRange', 'defaultAttributeCode'],

                data() {
                    return {
                        refreshKey: 0,
                        isLoading: true,
                        allowedMaxPrice: 100,
                        priceRange: null,
                    };
                },

                computed: {
                    minRange() {
                        let priceRange = (this.priceRange || '0,100').split(',');
                        return priceRange[0];
                    },
                    maxRange() {
                        let priceRange = (this.priceRange || '0,100').split(',');
                        return priceRange[1];
                    }
                },

                created() {
                    let defaultRange = Array.isArray(this.defaultPriceRange)
                        ? this.defaultPriceRange.join(',')
                        : this.defaultPriceRange;
                    this.priceRange = defaultRange || [0, 100].join(',');
                },

                mounted() {
                    this.getMaxPrice();
                },

                methods: {
                    getMaxPrice() {
                        this.$axios.get('{{ route("shop.api.categories.max_price") }}', {
                            params: { attribute_code: this.defaultAttributeCode || 'price', }
                        })
                        .then((response) => {
                            this.isLoading = false;
                            if (response.data.data.max_price) {
                                this.allowedMaxPrice = response.data.data.max_price;
                            }
                            if (! this.defaultPriceRange) {
                                this.priceRange = [0, this.allowedMaxPrice].join(',');
                            }
                            ++this.refreshKey;
                        })
                        .catch((error) => { console.log(error); });
                    },

                    setPriceRange($event) {
                        this.priceRange = [$event.minRange, $event.maxRange].join(',');
                        this.$emit('set-price-range', this.priceRange);
                    },
                },
            });
        </script>

        <script
            type="text/x-template"
            id="v-filters-template"
        >
            <template v-if="isLoading">
                <x-shop::shimmer.categories.filters />
            </template>

            <template v-else>
                <div>
                    <div class="flex h-[50px] items-center justify-between border-b border-zinc-200 pb-2.5 max-md:hidden">
                        <p class="text-lg font-semibold max-sm:font-medium">
                            @lang('shop::app.categories.filters.filters')
                        </p>
                        <p
                            class="cursor-pointer text-xs font-medium"
                            tabindex="0"
                            @click="clear()"
                        >
                            @lang('shop::app.categories.filters.clear-all')
                        </p>
                    </div>

                    <v-filter-item
                        ref="filterItemComponent"
                        :key="filterIndex"
                        :filter="filter"
                        v-for='(filter, filterIndex) in filters.available'
                        @values-applied="applyFilter(filter, $event)"
                    >
                    </v-filter-item>
                </div>
            </template>
        </script>

        <script
            type="text/x-template"
            id="v-filter-item-template"
        >
            <x-shop::accordion class="last:border-b-0">
                <x-slot:header class="px-0 py-2.5 max-sm:!pb-1.5">
                    <div class="flex items-center justify-between">
                        <p class="text-lg font-semibold max-sm:text-base max-sm:font-medium">
                            @{{ filter.name }}
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!p-0">
                    <ul v-if="filter.type === 'price'">
                        <li>
                            <v-price-filter
                                :key="refreshKey"
                                :default-price-range="appliedValues"
                                :default-attribute-code="filter.code"
                                @set-price-range="applyValue($event)"
                            >
                            </v-price-filter>
                        </li>
                    </ul>

                    <template v-else>
                        <div class="flex flex-col gap-1" v-if="filter.type !== 'boolean'">
                            <div class="relative">
                                <div class="icon-search pointer-events-none absolute top-3 flex items-center text-2xl max-md:text-xl max-sm:top-2.5 ltr:left-3 rtl:right-3"></div>
                                <input
                                    type="text"
                                    class="block w-full rounded-xl border border-zinc-200 px-11 py-3.5 text-sm font-medium text-gray-900 max-md:rounded-lg max-md:px-10 max-md:py-3 max-md:font-normal max-sm:text-xs"
                                    placeholder="@lang('shop::app.categories.filters.search.title')"
                                    v-model="searchQuery"
                                    v-debounce:500="searchOptions"
                                />
                            </div>
                            <p
                                class="mt-1 flex flex-row-reverse text-xs text-gray-600"
                                v-text="
                                    '@lang('shop::app.categories.filters.search.results-info', ['currentCount' => 'currentCount', 'totalCount' => 'totalCount'])'
                                        .replace('currentCount', options.length)
                                        .replace('totalCount', meta.total)
                                "
                                v-if="meta && meta.total > 0"
                            ></p>
                        </div>

                        <ul class="pb-3 text-base text-gray-700">
                            <template v-if="options.length">
                                <li :key="`${filter.id}_${option.id}`" v-for="(option, optionIndex) in options">
                                    <div class="flex select-none items-center gap-x-4 rounded hover:bg-gray-100 max-sm:gap-x-1 max-sm:!p-0 ltr:pl-2 rtl:pr-2">
                                        <input
                                            type="checkbox"
                                            :id="`filter_${filter.id}_option_ ${option.id}`"
                                            class="peer hidden"
                                            :value="option.id"
                                            v-model="appliedValues"
                                            @change="applyValue"
                                        />
                                        <label
                                            class="icon-uncheck peer-checked:icon-check-box cursor-pointer text-2xl text-navyBlue peer-checked:text-navyBlue max-sm:text-xl"
                                            role="checkbox"
                                            aria-checked="false"
                                            :aria-label="option.name"
                                            :aria-labelledby="'label_option_' + option.id"
                                            tabindex="0"
                                            :for="`filter_${filter.id}_option_ ${option.id}`"
                                        ></label>
                                        <label
                                            class="w-full cursor-pointer p-2 text-base text-gray-900 max-sm:p-1 max-sm:text-sm ltr:pl-0 rtl:pr-0"
                                            :id="'label_option_' + option.id"
                                            :for="`filter_${filter.id}_option_ ${option.id}`"
                                            role="button"
                                            tabindex="0"
                                        >
                                            @{{ option.name }}
                                        </label>
                                    </div>
                                </li>
                            </template>

                            <template v-else>
                                <li class="flex flex-col items-center justify-center gap-2 py-2" v-if="! isLoadingMore">
                                    @lang('shop::app.categories.filters.search.no-options-available')
                                </li>
                            </template>
                        </ul>

                        <div class="flex justify-center pb-3" v-if="meta && meta.current_page < meta.last_page">
                            <button
                                type="button"
                                class="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @click="loadMoreOptions"
                                :disabled="isLoadingMore"
                            >
                                <span v-if="isLoadingMore">@lang('shop::app.categories.filters.search.loading')</span>
                                <span v-else>@lang('shop::app.categories.filters.search.load-more')</span>
                            </button>
                        </div>
                    </template>
                </x-slot>
            </x-shop::accordion>
        </script>

        <script
            type="text/x-template"
            id="v-price-filter-template"
        >
            <div>
                <template v-if="isLoading">
                    <x-shop::shimmer.range-slider />
                </template>

                <template v-else>
                    <x-shop::range-slider
                        ::key="refreshKey"
                        default-type="price"
                        ::default-allowed-max-range="allowedMaxPrice"
                        ::default-min-range="minRange"
                        ::default-max-range="maxRange"
                        @change-range="setPriceRange($event)"
                    />
                </template>
            </div>
        </script>
    @endpush
</x-shop::layouts.account>
