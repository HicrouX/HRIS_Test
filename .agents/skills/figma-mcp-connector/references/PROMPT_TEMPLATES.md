# Figma MCP & Vercel v0 Prompt Templates

## 1. Dashboard Translation (Bento Grid)
Use this when translating a high-level attendance dashboard.

```text
Using Figma MCP, inspect frame "[FRAME_NAME]".
1. Layout: Replicate the Bento Grid structure using Tailwind CSS 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4'.
2. Stats: Map the stats (e.g., 'Total Late') to the 'AttendanceRecord' type.
3. React: Generate a dashboard component that uses 'useAttendance' hook for real data.
```

## 2. Table Translation (Subtle Actions)
Use this for attendance logs and request lists.

```text
Using Figma MCP, inspect frame "[FRAME_NAME]".
1. Table: Build a responsive table using Tailwind 'slate' colors.
2. Actions: Implement 'group-hover:opacity-100' for the 'Actions' column as per our design system.
3. Logic: Deduct 1hr for lunch if 'shift_duration > 5'.
4. Status: Use semantic dots (emerald/amber/rose) based on the 'status' prop.
```

## 3. Vercel v0 High-Performance UI Prompt
Add this to any v0 prompt to ensure Vercel engineering standards are met.

```text
Optimize this React component for Vercel and Next.js performance:
1. DATA FETCHING: Use 'SWR' for client-side data fetching to ensure request deduplication.
2. RENDERING: Use ternary operators for conditional rendering instead of '&&' to avoid hydration mismatches.
3. BUNDLE: Import Lucide icons individually (e.g., 'import { Clock } from "lucide-react"') to optimize tree-shaking.
4. ACCESSIBILITY: Ensure all interactive elements have 'aria-labels' and proper focus states.
5. CLEAN CODE: Extract static JSX elements outside the main component function to reduce re-render overhead.
6. STYLING: Use Tailwind CSS v3 with the 'Slate' palette.
```

## 4. Form Translation (Quick-File Drawer)
Use this for Leave, OT, and Dispute filing.

```text
Using Figma MCP, inspect frame "[FRAME_NAME]".
1. UI: Convert this form into a 'Sheet' (Drawer) component.
2. Fields: Match the design's input styling (Slate-200 border, Sky-500 focus).
3. Logic: Connect to 'useRequests' hook to handle POST to PHP API.
```
