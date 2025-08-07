import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route } from 'ziggy-js';
import { Ziggy } from './ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Shorts';

// Make route function globally available
declare global {
    var route: typeof import('ziggy-js').route;
}
window.route = (name, params, absolute, config = Ziggy) => route(name, params, absolute, config);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
