<v-category-tree
    @category-selected="setCategoryFilter($event)"
>
    <div class="shimmer h-10 w-full rounded"></div>
    <div class="shimmer mt-2 h-8 w-3/4 rounded"></div>
    <div class="shimmer mt-2 h-8 w-2/3 rounded"></div>
</v-category-tree>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-category-tree-template"
    >
        <div class="pb-4">
            <div class="flex items-center justify-between border-b border-zinc-200 pb-2.5">
                <p class="text-lg font-semibold">
                    @lang('shop::app.layouts.catalog')
                </p>

                <p
                    class="cursor-pointer text-xs font-medium"
                    @click="clear"
                    v-if="selectedCategoryId"
                >
                    @lang('shop::app.categories.filters.clear-all')
                </p>
            </div>

            <template v-if="isLoading">
                <div class="shimmer mt-3 h-10 w-full rounded"></div>
                <div class="shimmer mt-2 h-8 w-3/4 rounded"></div>
                <div class="shimmer mt-2 h-8 w-2/3 rounded"></div>
            </template>

            <template v-else>
                <ul class="mt-3 space-y-1">
                    <li
                        v-for="category in categories"
                        :key="category.id"
                    >
                        <div
                            class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-zinc-100"
                            :class="{ 'bg-zinc-100 font-medium': selectedCategoryId == category.id }"
                            @click="selectCategory(category)"
                        >
                            <span
                                v-if="category.children && category.children.length"
                                class="icon-arrow-right text-sm transition-transform rtl:icon-arrow-left"
                                :class="{ 'rotate-90': expandedCategories[category.id] }"
                                @click.stop="toggleExpand(category.id)"
                            ></span>

                            <span v-else class="text-sm"></span>

                            @{{ category.name }}
                        </div>

                        <ul
                            v-if="category.children && category.children.length && expandedCategories[category.id]"
                            class="ml-4 space-y-1 border-l border-zinc-200 pl-2"
                        >
                            <li
                                v-for="child in category.children"
                                :key="child.id"
                            >
                                <div
                                    class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-zinc-100"
                                    :class="{ 'bg-zinc-100 font-medium': selectedCategoryId == child.id }"
                                    @click="selectCategory(child)"
                                >
                                    <span
                                        v-if="child.children && child.children.length"
                                        class="icon-arrow-right text-sm transition-transform rtl:icon-arrow-left"
                                        :class="{ 'rotate-90': expandedCategories[child.id] }"
                                        @click.stop="toggleExpand(child.id)"
                                    ></span>

                                    <span v-else class="text-sm"></span>

                                    @{{ child.name }}
                                </div>

                                <ul
                                    v-if="child.children && child.children.length && expandedCategories[child.id]"
                                    class="ml-4 space-y-1 border-l border-zinc-200 pl-2"
                                >
                                    <li
                                        v-for="grandchild in child.children"
                                        :key="grandchild.id"
                                    >
                                        <div
                                            class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-zinc-100"
                                            :class="{ 'bg-zinc-100 font-medium': selectedCategoryId == grandchild.id }"
                                            @click="selectCategory(grandchild)"
                                        >
                                            @{{ grandchild.name }}
                                        </div>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-category-tree', {
            template: '#v-category-tree-template',

            data() {
                return {
                    isLoading: true,

                    categories: [],

                    expandedCategories: {},

                    selectedCategoryId: null,
                };
            },

            mounted() {
                this.getCategories();
            },

            methods: {
                getCategories() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.isLoading = false;

                            this.categories = response.data.data;
                        })
                        .catch(error => {
                            this.isLoading = false;

                            console.log(error);
                        });
                },

                selectCategory(category) {
                    if (this.selectedCategoryId === category.id) {
                        this.selectedCategoryId = null;

                        this.$emit('category-selected', null);
                    } else {
                        this.selectedCategoryId = category.id;

                        this.$emit('category-selected', category.id);
                    }
                },

                toggleExpand(categoryId) {
                    this.expandedCategories[categoryId] = !this.expandedCategories[categoryId];
                },

                clear() {
                    this.selectedCategoryId = null;

                    this.$emit('category-selected', null);
                },
            },
        });
    </script>
@endPushOnce
