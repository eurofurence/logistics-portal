<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

defineProps({
    title: { type: String, required: true },
    wide: Boolean,
})

const page = usePage()
const ui = computed(() => page.props.ui)
const language = ref(page.props.locale)
const changingLanguage = ref(false)
const preferenceError = ref(false)
const appearance = ref('system')
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)')

try {
    const storedTheme = localStorage.getItem('member-portal-theme')
    if (['light', 'dark', 'system'].includes(storedTheme)) appearance.value = storedTheme
} catch {}

function applyAppearance() {
    const dark = appearance.value === 'dark' || (appearance.value === 'system' && systemTheme.matches)
    document.documentElement.classList.toggle('dark', dark)
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light'
}

watch(appearance, () => {
    try {
        localStorage.setItem('member-portal-theme', appearance.value)
    } catch {}
    applyAppearance()
})

async function changeLanguage() {
    changingLanguage.value = true
    preferenceError.value = false
    try {
        await axios.post(page.props.links.locale, { locale: language.value })
        window.location.reload()
    } catch {
        language.value = page.props.locale
        preferenceError.value = true
        changingLanguage.value = false
    }
}

onMounted(() => {
    applyAppearance()
    systemTheme.addEventListener('change', applyAppearance)
})
onUnmounted(() => systemTheme.removeEventListener('change', applyAppearance))
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100 antialiased">
        <Head :title="title + ' · ' + ui.portal" />

        <header class="border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
            <div class="mx-auto flex min-h-20 max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <img :src="page.props.logo" alt="" class="h-11 max-w-36 object-contain">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ ui.portal }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                            </svg>
                            {{ ui.language }}
                        </span>
                        <select v-model="language" class="portal-input !mt-1 !min-h-9 !py-1 !text-sm" :disabled="changingLanguage" @change="changeLanguage">
                            <option v-for="(label, locale) in page.props.locales" :key="locale" :value="locale">{{ label }}</option>
                        </select>
                    </label>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path v-if="appearance === 'light'" stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                <path v-else-if="appearance === 'dark'" stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                            </svg>
                            {{ ui.appearance }}
                        </span>
                        <select v-model="appearance" class="portal-input !mt-1 !min-h-9 !py-1 !text-sm">
                            <option value="system">{{ ui.system }}</option>
                            <option value="light">{{ ui.light }}</option>
                            <option value="dark">{{ ui.dark }}</option>
                        </select>
                    </label>
                    <slot name="header" />
                </div>
            </div>
        </header>

        <main class="mx-auto w-full px-5 py-9 sm:px-8 sm:py-14" :class="wide ? 'max-w-6xl' : 'max-w-lg'">
            <h1 class="mb-6 text-3xl font-semibold tracking-tight">{{ title }}</h1>
            <p v-if="preferenceError" role="alert" class="mb-5 text-sm text-red-700 dark:text-red-300">{{ ui.preferences_error }}</p>
            <p v-if="page.props.status" role="status" class="mb-5 rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-950 px-4 py-3 text-sm text-teal-950 dark:text-teal-100">
                {{ page.props.status }}
            </p>
            <slot />
        </main>
    </div>
</template>
