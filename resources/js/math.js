/**
 * LaTeX rendering with KaTeX (loaded on demand — see app.js).
 *
 * Delimiters: \( … \) inline, \[ … \] and $$ … $$ display.
 * A single $ is deliberately NOT a delimiter, so "costs $5 and $10" stays plain text.
 * Chemistry works too via mhchem: \(\ce{H2SO4}\).
 *
 * Safe with user content: Blade/Alpine put the text in as TEXT (escaped); KaTeX only reads
 * text nodes and builds its own markup. trust:false blocks \href, \url, \includegraphics…;
 * throwOnError:false shows broken LaTeX as red source instead of breaking the page.
 */
import renderMathInElement from 'katex/contrib/auto-render';
import 'katex/contrib/mhchem';
import 'katex/dist/katex.min.css';

export const DELIMITERS = [
    { left: '$$', right: '$$', display: true },
    { left: '\\[', right: '\\]', display: true },
    { left: '\\(', right: '\\)', display: false },
];

export function renderMath(element) {
    if (!element) return;

    renderMathInElement(element, {
        delimiters: DELIMITERS,
        throwOnError: false,
        trust: false,
        ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code', 'option', 'input', 'select'],
    });
}
