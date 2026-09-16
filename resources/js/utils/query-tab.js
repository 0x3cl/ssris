import { ref, watch } from 'vue';

/**
 * Persist an allowed tab value in the current page URL without a navigation.
 */
export const useQueryTab = (allowedTabs, defaultTab) => {
    const selectedTab = new URLSearchParams(window.location.search).get('tab');
    const activeTab = ref(allowedTabs.includes(selectedTab) ? selectedTab : defaultTab);

    watch(activeTab, (tab) => {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState(window.history.state, '', url);
    });

    return activeTab;
};
