# ActivityList Components

Activity list display components for F-RevoCRM.

## Components

### ActivityListItem

Displays a single activity item with icon, subject, date/time, assigned user, and status.

#### Props

```typescript
interface ActivityListItemProps {
  activity: Activity;
}
```

#### Activity Type

```typescript
interface Activity {
  id: string;
  subject: string;
  activityType: 'Task' | 'Meeting' | 'Call' | string;
  status: string;
  dateStart: string;      // YYYY-MM-DD
  timeStart: string;      // HH:MM:SS
  dueDate: string;        // YYYY-MM-DD
  timeEnd: string;        // HH:MM:SS
  assignedTo: {
    id: string;
    name: string;
  };
  description?: string;
  detailViewUrl: string;
}
```

#### Usage

```tsx
import { ActivityListItem } from '@/components/ActivityList';

const activity = {
  id: '123',
  subject: 'Meeting with client',
  activityType: 'Meeting',
  status: 'Planned',
  dateStart: '2024-01-15',
  timeStart: '14:30:00',
  dueDate: '2024-01-15',
  timeEnd: '15:30:00',
  assignedTo: {
    id: '1',
    name: 'John Doe'
  },
  detailViewUrl: '?module=Calendar&view=Detail&record=123'
};

<ActivityListItem activity={activity} />
```

#### Features

- **Activity Type Icons**:
  - Meeting: Calendar icon (blue)
  - Task: CheckSquare icon (green)
  - Call: Phone icon (purple)

- **Status Badge**: Color-coded based on status
  - Completed: Green (success)
  - In Progress: Yellow (warning)
  - Cancelled: Red (destructive)
  - Planned: Gray (secondary)

- **Date/Time Formatting**: User-friendly Japanese format (e.g., "2024年1月15日 14:30")

- **Hover Effects**: Subtle background and border color changes

- **Accessibility**:
  - Semantic HTML with proper ARIA attributes
  - Keyboard navigation support
  - Focus indicators

## File Structure

```
ActivityList/
├── ActivityListItem.tsx       # Single activity item component
├── index.ts                   # Exports
├── __tests__/
│   └── ActivityListItem.test.tsx  # Unit tests
└── README.md                  # This file
```

## Related Components

- Badge (`@/components/ui/badge`) - Status badge display
- Icons from `lucide-react` - Activity type icons

## Testing

Run tests with:

```bash
npm test -- ActivityListItem
```
