# UI Component Capability Reference (Agent-Readable)

## Purpose

This document defines a **capability-level inventory of UI components** commonly found in advanced application UI libraries.

It is optimized for **AI agents** that need to:
- Design a new UI component library
- Decide component scope and priority
- Understand intent, complexity, and relationships
- Avoid framework-specific assumptions

This is a **semantic reference**, not a framework comparison.

---

## Component Entry Schema

Each component follows a strict schema:

- **Intent / Description** → What problem this component solves
- **Common Variants** → Expected configurations or extensions
- **Complexity**
  - Basic → Stateless or minimally stateful primitive
  - Composite → Built from multiple primitives
  - Advanced → Heavy state, performance, or interaction logic
- **Notes** → Architectural or UX considerations

---

# 1. Navigation Components

## Breadcrumb
- **Intent / Description**: Displays the user’s current location within a hierarchical structure and allows navigation to parent levels.
- **Common Variants**: Icons, collapsible middle items, router-aware
- **Complexity**: Basic
- **Notes**: Often derived automatically from routing metadata.

## Menu / Dropdown
- **Intent / Description**: Displays a list of actions or navigation links triggered by click or hover.
- **Common Variants**: Nested menus, icons, keyboard navigation
- **Complexity**: Composite
- **Notes**: Accessibility and focus management are critical.

## Mega Menu
- **Intent / Description**: Displays large navigation structures in a wide, multi-column layout.
- **Common Variants**: Grid-based, category headers, mixed content
- **Complexity**: Advanced
- **Notes**: Layout-heavy and easy to overuse.

## Menubar
- **Intent / Description**: Provides persistent top-level navigation across the application.
- **Common Variants**: Horizontal, responsive collapse
- **Complexity**: Composite

## Drawer / Sidebar
- **Intent / Description**: Off-canvas panel used for navigation or contextual tools.
- **Common Variants**: Modal, persistent, mini
- **Complexity**: Composite
- **Notes**: Requires overlay handling and focus trapping.

## Dock
- **Intent / Description**: A floating launcher for frequently used actions or views.
- **Common Variants**: Hover-expand, animated magnification
- **Complexity**: Advanced
- **Notes**: Primarily decorative; animation-heavy.

## Steps / Stepper
- **Intent / Description**: Visualizes progress through a multi-step process.
- **Common Variants**: Linear, non-linear, vertical
- **Complexity**: Composite
- **Notes**: Often coupled with form validation state.

## Tabs
- **Intent / Description**: Switches between related views without full navigation.
- **Common Variants**: Card-style, vertical, scrollable
- **Complexity**: Composite
- **Notes**: Optional URL synchronization improves UX.

## Pagination
- **Intent / Description**: Enables navigation through large datasets.
- **Common Variants**: Page-based, cursor-based
- **Complexity**: Composite
- **Notes**: Closely tied to backend data strategy.

## Speed Dial (FAB)
- **Intent / Description**: Reveals secondary actions contextually from a floating button.
- **Common Variants**: Linear, radial
- **Complexity**: Composite
- **Notes**: Common in mobile-first designs.

## Anchor Navigation
- **Intent / Description**: Enables navigation within long pages by section.
- **Common Variants**: Scroll-spy, affixed
- **Complexity**: Composite
- **Notes**: Requires scroll position observation.

## Back To Top
- **Intent / Description**: Allows quick return to the top of long pages.
- **Common Variants**: Auto-show, animated scroll
- **Complexity**: Basic

---

# 2. Form Input Components

## Text Input
- **Intent / Description**: Captures short freeform text input.
- **Common Variants**: Filled, outlined, floating label
- **Complexity**: Basic

## Number Input
- **Intent / Description**: Captures numeric values with validation and constraints.
- **Common Variants**: Spinner, currency, min/max
- **Complexity**: Composite

## Masked Input
- **Intent / Description**: Enforces a predefined input format.
- **Common Variants**: Phone, ID, date
- **Complexity**: Composite
- **Notes**: Often regex-driven.

## Textarea
- **Intent / Description**: Captures long-form text input.
- **Common Variants**: Auto-resize, character counter
- **Complexity**: Basic

## Select (Single)
- **Intent / Description**: Allows selection of one option from a list.
- **Common Variants**: Searchable, virtualized
- **Complexity**: Composite

## Multi Select
- **Intent / Description**: Allows selection of multiple options.
- **Common Variants**: Tag-based, checkbox list
- **Complexity**: Composite

## Cascader
- **Intent / Description**: Enables hierarchical option selection.
- **Common Variants**: Lazy-loaded children
- **Complexity**: Advanced

## Tree Select
- **Intent / Description**: Allows selection from a tree-structured dataset.
- **Common Variants**: Multi-select, checkbox
- **Complexity**: Advanced

## Checkbox / Radio
- **Intent / Description**: Captures boolean or exclusive choices.
- **Common Variants**: Grouped
- **Complexity**: Basic

## Switch
- **Intent / Description**: Toggles a binary state immediately.
- **Common Variants**: Size, icon-based
- **Complexity**: Basic

## Slider / Range
- **Intent / Description**: Selects numeric values via dragging.
- **Common Variants**: Single value, range
- **Complexity**: Composite

## Knob / Dial
- **Intent / Description**: Selects values using a rotary control.
- **Common Variants**: Min/max bounds
- **Complexity**: Advanced
- **Notes**: Primarily visual; accessibility risk.

## Rating
- **Intent / Description**: Captures qualitative feedback using icons.
- **Common Variants**: Half-values, custom icons
- **Complexity**: Basic

## Color Picker
- **Intent / Description**: Allows users to select colors.
- **Common Variants**: Palette, spectrum, hex input
- **Complexity**: Advanced

## Transfer List
- **Intent / Description**: Moves items between two sets.
- **Common Variants**: Searchable lists
- **Complexity**: Advanced

## Rich Text Editor
- **Intent / Description**: Enables formatted text and document editing.
- **Common Variants**: Toolbar customization
- **Complexity**: Advanced
- **Notes**: Usually wraps third-party engines.

## File Upload
- **Intent / Description**: Handles file selection and uploading.
- **Common Variants**: Drag-drop, preview, auto-upload
- **Complexity**: Composite
- **Notes**: Async and validation heavy.

---

# 3. Date & Time Components

## Date Picker
- **Intent / Description**: Allows selection of calendar dates.
- **Common Variants**: Single, range, multiple
- **Complexity**: Composite

## Time Picker
- **Intent / Description**: Allows selection of time values.
- **Common Variants**: 12h / 24h
- **Complexity**: Composite

## Date-Time Picker
- **Intent / Description**: Combines date and time selection.
- **Common Variants**: Inline or modal
- **Complexity**: Advanced

## Calendar (Inline)
- **Intent / Description**: Displays a full calendar view embedded in the page.
- **Common Variants**: Event indicators
- **Complexity**: Composite

## Time Select
- **Intent / Description**: Selects time from predefined slots.
- **Common Variants**: Configurable intervals
- **Complexity**: Basic

---

# 4. Button Components

## Button
- **Intent / Description**: Triggers an action.
- **Common Variants**: Primary, secondary, text
- **Complexity**: Basic

## Icon Button
- **Intent / Description**: Triggers an action using only an icon.
- **Common Variants**: Tooltip integration
- **Complexity**: Basic

## Button Group
- **Intent / Description**: Groups related actions visually.
- **Common Variants**: Segmented
- **Complexity**: Composite

## Split Button
- **Intent / Description**: Combines a primary action with secondary options.
- **Common Variants**: Dropdown-based
- **Complexity**: Composite

## Loading Button
- **Intent / Description**: Indicates an action is processing.
- **Common Variants**: Spinner or progress
- **Complexity**: Composite

---

# 5. Data Display Components

## Data Table
- **Intent / Description**: Displays structured tabular data.
- **Common Variants**: Sorting, filtering, pagination
- **Complexity**: Advanced
- **Notes**: One of the hardest components to implement correctly.

## Tree View
- **Intent / Description**: Displays hierarchical data visually.
- **Common Variants**: Expand/collapse, drag-drop
- **Complexity**: Composite

## Organization Chart
- **Intent / Description**: Visualizes hierarchical relationships.
- **Common Variants**: Horizontal, vertical
- **Complexity**: Advanced

## Timeline
- **Intent / Description**: Displays events in chronological order.
- **Common Variants**: Vertical, horizontal
- **Complexity**: Composite

## Card
- **Intent / Description**: Containers for grouping related content.
- **Common Variants**: Media headers, actions
- **Complexity**: Basic

## Carousel
- **Intent / Description**: Cycles through content or media.
- **Common Variants**: Auto-play, manual
- **Complexity**: Composite

## List
- **Intent / Description**: Displays collections vertically.
- **Common Variants**: Virtualized
- **Complexity**: Composite

## Virtual Scroller
- **Intent / Description**: Optimizes rendering of large datasets.
- **Common Variants**: Fixed or dynamic height
- **Complexity**: Advanced

## Descriptions
- **Intent / Description**: Displays key-value data pairs.
- **Common Variants**: Column-based layouts
- **Complexity**: Composite

## Statistic
- **Intent / Description**: Displays prominent numeric metrics.
- **Common Variants**: Trend indicators
- **Complexity**: Basic

## Empty State
- **Intent / Description**: Communicates absence of data.
- **Common Variants**: Illustration + CTA
- **Complexity**: Basic

---

# 6. Feedback & Status Components

## Alert / Message
- **Intent / Description**: Displays inline feedback messages.
- **Common Variants**: Success, error, warning
- **Complexity**: Basic

## Toast / Notification
- **Intent / Description**: Displays transient messages.
- **Common Variants**: Stacking, actions
- **Complexity**: Composite

## Dialog / Modal
- **Intent / Description**: Blocks interaction for focused tasks.
- **Common Variants**: Confirmation, form
- **Complexity**: Advanced

## Confirm Popup
- **Intent / Description**: Asks for confirmation near an action.
- **Common Variants**: Inline or tooltip-like
- **Complexity**: Composite

## Progress Bar
- **Intent / Description**: Visualizes task completion.
- **Common Variants**: Determinate/indeterminate
- **Complexity**: Basic

## Spinner
- **Intent / Description**: Indicates loading state.
- **Common Variants**: Size variants
- **Complexity**: Basic

## Skeleton Loader
- **Intent / Description**: Placeholder UI while loading data.
- **Common Variants**: Text, avatar, card
- **Complexity**: Composite

## Result Page
- **Intent / Description**: Displays success, error, or empty outcomes.
- **Common Variants**: Status illustrations
- **Complexity**: Composite

## Banner
- **Intent / Description**: Persistent system-wide message.
- **Common Variants**: Dismissible
- **Complexity**: Composite

---

# 7. Overlay & Floating Components

## Tooltip
- **Intent / Description**: Displays brief contextual help.
- **Common Variants**: Delay, placement
- **Complexity**: Basic

## Popover
- **Intent / Description**: Displays rich contextual content.
- **Common Variants**: Click or hover trigger
- **Complexity**: Composite

## Overlay Panel
- **Intent / Description**: Floating panel anchored to an element.
- **Common Variants**: Positioning options
- **Complexity**: Composite

---

# 8. Specialized / Power Components

## Terminal Emulator
- **Intent / Description**: Simulates a command-line interface.
- **Complexity**: Advanced

## Spreadsheet Grid
- **Intent / Description**: Enables spreadsheet-like data editing.
- **Complexity**: Advanced

## Watermark
- **Intent / Description**: Overlays repeated watermark text or images.
- **Complexity**: Composite

## Guided Tour
- **Intent / Description**: Walks users through UI features step-by-step.
- **Complexity**: Advanced

## Media Player
- **Intent / Description**: Plays audio or video content.
- **Complexity**: Composite

## Chat UI Primitives
- **Intent / Description**: Message bubbles and chat layouts.
- **Complexity**: Composite

## Avatar Group
- **Intent / Description**: Displays grouped user avatars.
- **Complexity**: Basic

## Parallax Container
- **Intent / Description**: Creates depth effects during scrolling.
- **Complexity**: Advanced

## Image Compare
- **Intent / Description**: Compares two images with a draggable slider.
- **Complexity**: Composite

## Scroll Observer
- **Intent / Description**: Triggers effects based on scroll position.
- **Complexity**: Advanced

## Text Ellipsis
- **Intent / Description**: Truncates overflowing text.
- **Complexity**: Basic

## Badge
- **Intent / Description**: Displays small status indicators.
- **Complexity**: Basic

## Tag / Chip
- **Intent / Description**: Displays compact categorical labels.
- **Complexity**: Basic

---

## Closing Guidance for Agents

- Prefer **controlled components** over internal state.
- Optimize **accessibility first**, visuals second.
- Build **primitives → composites → advanced**.
- Defer specialized components unless justified by product scope.