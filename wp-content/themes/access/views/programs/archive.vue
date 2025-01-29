<template>
  <div class="hidden" aria-hidden="true" data-js="loaded">
    <span class="hidden" aria-hidden="true">{{ initialized }}</span>

    <header class="c-header p-2 screen-tablet:p-3 mt-3 mb-3 print:mt-0">
      <div>
        <h1 class="c-header__descriptor text-blue-dark">{{ strings.PROGRAMS }}</h1>

        <h2 class="c-header__information text-grey-mid mb-0 list-inline-comma">
          <span v-if="init" v-for="c in categories" v-html="c"></span> &nbsp;
        </h2>
      </div>
    </header>

    <div class="wrap pb-3 screen-desktop:layout-sidebar-small-gutter">
      <aside id="filter-programs">
        <h2 class="type-h4 mb-0 hidden screen-desktop:inline-block">{{ strings.FILTER_PROGRAMS }}:</h2>

        <span class="text-small screen-desktop:hidden">{{ strings.FILTER_PROGRAMS }}</span>

        <div class="hidden:preload" v-bind:class="{'loaded': init}" v-bind:aria-hidden="!init">
          <c-filter-multi v-bind:terms="terms" v-bind:strings="strings" v-on:fetch="click" v-on:reset="toggle"></c-filter-multi>

          <div class="sticky bottom-0 pb-2 text-center screen-desktop:hidden animated fadeInUpBig" v-if="filtering">
            <a class="btn btn-small btn-primary" href="#see-programs">
              <span v-if="none">{{ strings.NO_RESULTS }}</span>
              <span v-else-if="!loading" v-html="'See ' + headers.total + ' Programs'">{{ strings.SEE_PROGRAMS }}</span>
              <span v-else>{{ strings.LOADING }}</span>
            </a>
          </div>
        </div>
      </aside>

      <div id="see-programs" class="pt-2 screen-desktop:pt-0">
        <div class="h-full hidden:preload" v-bind:class="{'loaded': init}" v-bind:aria-hidden="!init">
          <div class="px-3 pt-3 mb-2 bg-grey-lightest items-center" v-if="!loading">
            <div class="layout-gutter pb-3" v-for="page in posts" v-if="page && page.show">
              <c-card v-for="post in page.posts" :key="post.id" v-bind="post" v-bind:strings="strings" taxonomy="programs" v-if="page.show"></c-card>
            </div>
          </div>

          <div class="min-h-full flex items-center justify-center" v-if="none">
            <div class="sticky top-0 bottom-0 flex items-center justify-center py-4">
              <p>{{ strings.NO_RESULTS }} <a href="#filter-programs">{{ strings.NO_RESULTS_INSTRUCTIONS }}</a>.</p>
            </div>
          </div>

          <div class="min-h-full flex items-center justify-center" v-else-if="loading">
            <div class="sticky top-0 bottom-0 flex items-center justify-center py-4">
              <svg class="spinner icon-4 block text-yellow-access" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <circle class="spinner__path" cx="12" cy="12" r="10" fill="none"></circle>
              </svg>

              &nbsp;&nbsp;

              {{ strings.LOADING }}
            </div>
          </div>

          <div class="text-center screen-desktop:text-left" v-if="!loading">
            <a class="btn btn-secondary btn-small screen-tablet:btn" :href="paginationNextLink" v-on:click="paginate" v-if="next" data-amount="1">
              {{ strings.MORE_RESULTS }}
            </a>
          </div>

          <div class="sticky bottom-0 py-2 text-center screen-desktop:hidden" v-if="!loading">
            <a class="btn btn-small btn-primary" href="#filter-programs">
              {{ strings.FILTER_PROGRAMS }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  /** The script for this component is stored with all of the other JavaScript Modules */
  import ProgramsArchive from '../../src/js/modules/programs-archive.js';
  export default ProgramsArchive;
</script>