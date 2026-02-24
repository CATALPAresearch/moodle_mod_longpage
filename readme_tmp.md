Additional Performance Optimization Suggestions

6. Defer i18n String Loading (High Impact)

Current: All mod_longpage language strings loaded via blocking AJAX before app mounts (line 77 in main.js)
Impact: ~200-500ms blocking time depending on backend response
Solution: Load critical strings only, defer rest to on-demand
Estimated Savings: 200-500ms faster initial render

8. Remove jQuery $(document).ready (Low Impact)

Current: Quiz component uses $(document).ready() (line 612)
Impact: Unnecessary jQuery dependency check
Solution: Vue's mounted() already guarantees DOM ready
Estimated Savings: Cleaner code, ~10-20ms

9. Batch AJAX Calls (Medium Impact)

Current: 27+ individual ajax.call() scattered across components
Impact: Multiple round-trips to backend
Solution: Batch related calls, add request caching layer
Estimated Savings: 100-300ms for pages with multiple requests 10. Use Native Fetch API (Low Impact)

Current: Using Moodle's core/ajax AMD wrapper
Impact: AMD module loading overhead
Solution: Use native fetch() where possible, fallback to ajax.call
Estimated Savings: ~20-40KB less AMD dependencies 11. Virtual Scrolling for Long Lists (High Impact for long content)

Current: All DOM rendered at once
Impact: Heavy DOM with 100+ posts/questions
Solution: Use virtual-scroller for Posts/Quiz tabs
Estimated Savings: 70-90% reduction in initial DOM nodes 12. Preconnect to Backend (Low Impact)

Add: <link rel="preconnect" href="yourserver"> to view.php
Impact: DNS/TCP already established when AJAX fires
Estimated Savings: 50-150ms on first AJAX call 13. Image Lazy Loading (Medium Impact)

Current: All images in content loaded immediately
Solution: Add loading="lazy" to images or use IntersectionObserver
Estimated Savings: 500KB-2MB saved on initial load (content dependent) 14. Replace lodash Functions (Low Impact)

Current: lodash imported but tree-shaking already enabled
Solution: Replace with native JS where simple (.forEach, .map, .filter)
Estimated Savings: 10-30KB additional reduction 15. Code Splitting by AMD Modules (Can't do - AMD limitation)

❌ Not possible with Moodle's AMD single-file requirement
Already confirmed in earlier analysis
Which of these would you like me to implement? The high-impact ones are:

#6 (i18n defer) - Fastest to implement, clear 200-500ms gain
#11 (virtual scrolling) - Biggest impact for heavy content
#9 (batch AJAX) - Requires more analysis but worthwhile
