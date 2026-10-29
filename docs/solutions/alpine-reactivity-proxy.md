# Alpine.js Reactivity Proxy on Array Mutability

## Problem
After an asynchronous `fetch` request completed, updating object properties (like `status = 'success'`) did not trigger the UI to update. The DOM elements bound via `x-show` or `x-text` remained stuck on their old state, unless the user manually triggered another reactive property (like clicking a tab), which forced a partial re-render.

## Root Cause
When pushing a raw Javascript object into an Alpine.js (or Vue) reactive array, the reactivity system wraps the object in a `Proxy`.
However, if you pass the *original raw object reference* to an async function, any mutations made inside that function will bypass the proxy. Because the proxy is bypassed, Alpine's reactivity system is never notified that the properties changed, and the DOM is not updated.

**Bad Pattern:**
```javascript
const item = { status: 'pending' };
this.items.push(item);

// Passing the RAW object reference
this.processAsync(item);

async processAsync(item) {
    await fetch(...);
    // Modifies raw object, bypassing Alpine's tracking!
    item.status = 'success'; 
}
```

## Solution
Always retrieve the object from the reactive proxy array *after* pushing it, so you are passing and mutating the `Proxy`, not the raw object.

**Good Pattern:**
```javascript
const item = { status: 'pending' };
this.items.push(item);

// Retrieve and pass the PROXY from the array
this.processAsync(this.items[this.items.length - 1]);

async processAsync(itemProxy) {
    await fetch(...);
    // Modifies the proxy, successfully notifying Alpine to update the DOM!
    itemProxy.status = 'success'; 
}
```
