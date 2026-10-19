(() => {
    const storageKey = 'pupick-theme';
    const scriptUrl = document.currentScript.src;
    let savedTheme = 'light';

    try {
        const storedTheme = localStorage.getItem(storageKey);
        if (storedTheme === 'light' || storedTheme === 'dark') {
            savedTheme = storedTheme;
        }
    } catch (error) {
        console.warn('PUPick could not read the saved theme preference.', error);
    }

    const root = document.documentElement;
    root.dataset.theme = savedTheme;
    root.style.colorScheme = savedTheme;
    if (document.body.classList.contains('auth-page')) {
        document.body.classList.add('auth-controls-top');
    }

    const stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.href = new URL('theme.css?v=14', scriptUrl).href;
    document.head.appendChild(stylesheet);

    const languageScript = document.createElement('script');
    languageScript.src = new URL('language.js?v=16', scriptUrl).href;
    document.head.appendChild(languageScript);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'theme-toggle';
    button.setAttribute('aria-pressed', String(savedTheme === 'dark'));
    button.addEventListener('click', () => {
        const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = nextTheme;
        root.style.colorScheme = nextTheme;
        button.setAttribute('aria-pressed', String(nextTheme === 'dark'));
        updateButton();

        try {
            localStorage.setItem(storageKey, nextTheme);
        } catch (error) {
            console.warn('PUPick could not save the theme preference.', error);
        }
    });

    function updateButton() {
        const isDark = root.dataset.theme === 'dark';
        button.textContent = isDark ? '☀ Light mode' : '☾ Dark mode';
        button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        button.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
    }

    updateButton();
    document.addEventListener('DOMContentLoaded', () => {
        document.body.appendChild(button);
    }, { once: true });
})();
