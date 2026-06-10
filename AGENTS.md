# Agent Guidance for Gratis Popup

## Visual interpretation tooling

- For browser/UI/visual-parity work, prefer `mcp-for-page-builders` first: https://github.com/juslintek/mcp-for-page-builders
- If `mcp-for-page-builders` is unavailable, use **PinchTab** as the fallback.

---

## Project context

Gratis Popup is a WordPress plugin for exit-intent, scroll-triggered, and timed popups and slide-ins with customizable styling.

### Key focus areas for agents

- **Modal positioning** — Proper centering and viewport alignment
- **Animation timing** — Entry/exit animations (fade, slide, scale)
- **Mobile responsiveness** — Popup sizing and positioning on small screens
- **Overlay styling** — Background opacity, blur effects, z-index stacking
- **Form elements** — Input styling, button placement within popup

### When to use mcp-for-page-builders

- Test popup animations across browsers
- Verify responsive behavior on mobile/tablet
- Check overlay contrast and accessibility
- Test exit animations and timing
- Validate button styling and hover states
- Ensure animations don't exceed 60fps on low-end devices

---

## Related

- `/Volumes/Projects/AGENTS.md` — Machine-wide agent guidance
- `/Volumes/Projects/gratis/AGENTS.md` — Gratis plugin suite guidance
- `mcp-for-page-builders` — Visual auditing MCP for page builders
