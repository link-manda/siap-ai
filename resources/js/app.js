import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import mammoth from 'mammoth/mammoth.browser.js';
import * as XLSX from 'xlsx';
import DOMPurify from 'dompurify';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.mammoth = mammoth;
window.XLSX = XLSX;
window.DOMPurify = DOMPurify;

Alpine.start();

/**
 * SIAP-AI Centralized Flux Toast Web Component & Helper
 */
class UiToast extends HTMLElement {
    connectedCallback() {
        this.template = this.querySelector('template');
        this.position = this.getAttribute('position') || 'top right';
    }

    showToast(detail) {
        if (!this.template) {
            this.template = this.querySelector('template');
        }
        if (!this.template) return;

        // Clone dialog from template
        const clone = this.template.content.cloneNode(true);
        const dialog = clone.querySelector('[data-flux-toast-dialog]');
        if (!dialog) return;

        const rawVariant = detail?.dataset?.variant || 'info';
        const variant = rawVariant === 'error' ? 'danger' : rawVariant;
        dialog.setAttribute('data-variant', variant);

        const headingSlot = dialog.querySelector('slot[name="heading"]');
        const textSlot = dialog.querySelector('slot[name="text"]');
        const headingContainer = headingSlot?.parentElement;

        if (detail?.slots?.heading && headingSlot) {
            headingSlot.replaceWith(document.createTextNode(detail.slots.heading));
        } else if (headingContainer && headingSlot) {
            headingSlot.remove();
        }

        if (detail?.slots?.text && textSlot) {
            textSlot.replaceWith(document.createTextNode(detail.slots.text));
        }

        // Close button dismiss handler
        const closeBtn = dialog.querySelector('ui-close button');
        const dismiss = () => {
            dialog.classList.remove('showing');
            setTimeout(() => {
                dialog.remove();
                if (this.querySelectorAll('[data-flux-toast-dialog]').length === 0) {
                    try {
                        if (typeof this.hidePopover === 'function') {
                            this.hidePopover();
                        }
                    } catch (e) {}
                }
            }, 350);
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                dismiss();
            });
        }

        // Append to ui-toast element
        this.appendChild(dialog);

        // Show popover
        try {
            if (typeof this.showPopover === 'function') {
                if (this.matches(':not(:popover-open)')) {
                    this.showPopover();
                }
            }
        } catch (e) {}

        // Trigger transition animation
        requestAnimationFrame(() => {
            dialog.classList.add('showing');
        });

        // Determine duration: 0 for persistent danger/error, 5000ms for others
        let duration = detail?.duration;
        if (duration === undefined || duration === null) {
            duration = (variant === 'danger' || variant === 'error') ? 0 : 5000;
        }

        if (duration > 0) {
            setTimeout(dismiss, duration);
        }
    }
}

if (!customElements.get('ui-toast')) {
    customElements.define('ui-toast', UiToast);
}

// Global SiapToast Helper
window.SiapToast = function (text, variant = 'info', heading = null, duration = null) {
    const isError = variant === 'danger' || variant === 'error';
    const finalDuration = duration !== null ? duration : (isError ? 0 : 5000);
    const finalHeading = heading || (variant === 'success' ? 'Sukses' : (isError ? 'Kesalahan' : (variant === 'warning' ? 'Perhatian' : 'Informasi')));

    window.dispatchEvent(new CustomEvent('toast-show', {
        detail: {
            slots: {
                text: text,
                ...(finalHeading ? { heading: finalHeading } : {})
            },
            dataset: {
                variant: isError ? 'danger' : variant
            },
            duration: finalDuration
        }
    }));
};
