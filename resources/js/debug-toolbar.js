/**
 * Debug Toolbar JavaScript
 * Handles interactions, state persistence, and features
 */

(function() {
    'use strict';

    const DebugToolbar = {
        storageKey: 'debug-toolbar-state',
        
        /**
         * Initialize toolbar
         */
        init() {
            this.restoreState();
            this.attachEventListeners();
            this.initializeShortcuts();
        },

        /**
         * Toggle toolbar visibility
         */
        toggle() {
            const toolbar = document.getElementById('debug-toolbar');
            const arrow = document.getElementById('debug-toolbar-arrow');
            
            if (!toolbar || !arrow) return;
            
            const isCollapsed = toolbar.classList.toggle('collapsed');
            arrow.textContent = isCollapsed ? '▲' : '▼';
            
            // Save state
            this.saveState({
                collapsed: isCollapsed
            });
        },

        /**
         * Switch to a specific tab
         */
        switchTab(tabName) {
            // Remove active from all tabs and panels
            document.querySelectorAll('.debug-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.debug-panel').forEach(p => p.classList.remove('active'));

            // Add active to selected
            const selectedTab = document.querySelector(`.debug-tab[data-tab="${tabName}"]`);
            const selectedPanel = document.querySelector(`.debug-panel[data-panel="${tabName}"]`);
            
            if (selectedTab) selectedTab.classList.add('active');
            if (selectedPanel) selectedPanel.classList.add('active');
            
            // Save active tab
            this.saveState({
                activeTab: tabName
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard(text, button) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showCopyFeedback(button);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    this.fallbackCopy(text, button);
                });
            } else {
                this.fallbackCopy(text, button);
            }
        },

        /**
         * Fallback copy method for older browsers
         */
        fallbackCopy(text, button) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                this.showCopyFeedback(button);
            } catch (err) {
                console.error('Fallback copy failed:', err);
            }
            
            document.body.removeChild(textarea);
        },

        /**
         * Show visual feedback for copy action
         */
        showCopyFeedback(button) {
            if (!button) return;
            
            const originalText = button.textContent;
            button.textContent = '✓ Copied!';
            button.classList.add('copied');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('copied');
            }, 2000);
        },

        /**
         * Save state to localStorage
         */
        saveState(data) {
            const currentState = this.getState();
            const newState = { ...currentState, ...data };
            
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(newState));
            } catch (e) {
                console.warn('Failed to save toolbar state:', e);
            }
        },

        /**
         * Get state from localStorage
         */
        getState() {
            try {
                const state = localStorage.getItem(this.storageKey);
                return state ? JSON.parse(state) : {};
            } catch (e) {
                console.warn('Failed to load toolbar state:', e);
                return {};
            }
        },

        /**
         * Restore saved state
         */
        restoreState() {
            const state = this.getState();
            
            // Restore collapsed state
            if (state.collapsed) {
                const toolbar = document.getElementById('debug-toolbar');
                const arrow = document.getElementById('debug-toolbar-arrow');
                if (toolbar && arrow) {
                    toolbar.classList.add('collapsed');
                    arrow.textContent = '▲';
                }
            }
            
            // Restore active tab
            if (state.activeTab) {
                // Give time for DOM to be ready
                setTimeout(() => {
                    const tab = document.querySelector(`.debug-tab[data-tab="${state.activeTab}"]`);
                    if (tab) {
                        this.switchTab(state.activeTab);
                    }
                }, 100);
            }
        },

        /**
         * Clear saved state
         */
        clearState() {
            try {
                localStorage.removeItem(this.storageKey);
            } catch (e) {
                console.warn('Failed to clear toolbar state:', e);
            }
        },

        /**
         * Attach event listeners
         */
        attachEventListeners() {
            // Add copy buttons to SQL queries
            this.addCopyButtonsToQueries();
            
            // Add search functionality
            this.initializeSearch();
        },

        /**
         * Add copy buttons to all SQL queries
         */
        addCopyButtonsToQueries() {
            // Wait for DOM to be ready
            setTimeout(() => {
                const queryElements = document.querySelectorAll('.debug-panel[data-panel="queries"] pre');
                
                queryElements.forEach((pre, index) => {
                    if (pre.parentElement.querySelector('.debug-copy-btn')) {
                        return; // Already has button
                    }
                    
                    const sql = pre.textContent.trim();
                    const button = document.createElement('button');
                    button.className = 'debug-copy-btn';
                    button.textContent = 'Copy SQL';
                    button.onclick = (e) => {
                        e.stopPropagation();
                        this.copyToClipboard(sql, button);
                    };
                    
                    // Insert button before the pre element
                    pre.parentElement.insertBefore(button, pre);
                });
            }, 200);
        },

        /**
         * Initialize search functionality
         */
        initializeSearch() {
            // Can be extended with search box in future
        },

        /**
         * Initialize keyboard shortcuts
         */
        initializeShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ctrl+Shift+D - Toggle toolbar
                if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                    e.preventDefault();
                    this.toggle();
                }
                
                // Ctrl+Shift+C - Clear state
                if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                    e.preventDefault();
                    this.clearState();
                    console.log('Debug Toolbar: State cleared');
                }
                
                // Arrow keys for tab navigation (when toolbar focused)
                if (document.activeElement.classList.contains('debug-tab')) {
                    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                        e.preventDefault();
                        this.navigateTabs(e.key === 'ArrowRight' ? 1 : -1);
                    }
                }
            });
        },

        /**
         * Navigate between tabs with arrow keys
         */
        navigateTabs(direction) {
            const tabs = Array.from(document.querySelectorAll('.debug-tab'));
            const activeTab = document.querySelector('.debug-tab.active');
            
            if (!activeTab || tabs.length === 0) return;
            
            const currentIndex = tabs.indexOf(activeTab);
            let newIndex = currentIndex + direction;
            
            // Wrap around
            if (newIndex < 0) newIndex = tabs.length - 1;
            if (newIndex >= tabs.length) newIndex = 0;
            
            const newTab = tabs[newIndex];
            if (newTab) {
                const tabName = newTab.getAttribute('data-tab');
                this.switchTab(tabName);
                newTab.focus();
            }
        },

        /**
         * Format bytes to human readable
         */
        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Format time in milliseconds
         */
        formatTime(ms) {
            if (ms < 1) return (ms * 1000).toFixed(2) + 'μs';
            if (ms < 1000) return ms.toFixed(2) + 'ms';
            return (ms / 1000).toFixed(2) + 's';
        }
    };

    // Make functions globally accessible for inline onclick handlers
    window.debugToolbarToggle = () => DebugToolbar.toggle();
    window.debugToolbarSwitchTab = (tabName) => DebugToolbar.switchTab(tabName);
    window.debugToolbarCopy = (text, button) => DebugToolbar.copyToClipboard(text, button);

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => DebugToolbar.init());
    } else {
        DebugToolbar.init();
    }

    // Expose DebugToolbar globally for advanced usage
    window.DebugToolbar = DebugToolbar;

})();

