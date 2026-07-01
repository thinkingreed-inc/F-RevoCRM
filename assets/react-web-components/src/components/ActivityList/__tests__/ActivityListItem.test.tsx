import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ActivityListItem } from '../ActivityListItem';
import { Activity } from '@/types/activity';

describe('ActivityListItem', () => {
  const mockActivity: Activity = {
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
    description: 'Discuss project requirements',
    detailViewUrl: '?module=Calendar&view=Detail&record=123'
  };

  it('renders activity subject as a link', () => {
    render(<ActivityListItem activity={mockActivity} />);

    const link = screen.getByRole('link', { name: /meeting with client/i });
    expect(link).toBeInTheDocument();
    expect(link).toHaveAttribute('href', '?module=Calendar&view=Detail&record=123');
  });

  it('displays formatted date and time', () => {
    render(<ActivityListItem activity={mockActivity} />);

    // Date should be formatted as YYYY/MM/DD HH:mm
    expect(screen.getByText(/2024\/01\/15 14:30/)).toBeInTheDocument();
  });

  it('displays assigned user name', () => {
    render(<ActivityListItem activity={mockActivity} />);

    expect(screen.getByText('John Doe')).toBeInTheDocument();
  });

  it('displays status badge', () => {
    render(<ActivityListItem activity={mockActivity} />);

    expect(screen.getByText('Planned')).toBeInTheDocument();
  });

  it('renders correct icon for Meeting type', () => {
    const { container } = render(<ActivityListItem activity={mockActivity} />);

    // Calendar icon should be present for Meeting
    const icon = container.querySelector('svg');
    expect(icon).toBeInTheDocument();
  });

  it('renders correct icon for Task type', () => {
    const taskActivity: Activity = {
      ...mockActivity,
      activityType: 'Task'
    };

    const { container } = render(<ActivityListItem activity={taskActivity} />);

    const icon = container.querySelector('svg');
    expect(icon).toBeInTheDocument();
  });

  it('renders correct icon for Call type', () => {
    const callActivity: Activity = {
      ...mockActivity,
      activityType: 'Call'
    };

    const { container } = render(<ActivityListItem activity={callActivity} />);

    const icon = container.querySelector('svg');
    expect(icon).toBeInTheDocument();
  });

  it('handles activity without time', () => {
    const activityWithoutTime: Activity = {
      ...mockActivity,
      timeStart: ''
    };

    render(<ActivityListItem activity={activityWithoutTime} />);

    // Should show only date
    expect(screen.getByText(/2024\/01\/15/)).toBeInTheDocument();
  });

  it('applies correct status variant for completed status', () => {
    const completedActivity: Activity = {
      ...mockActivity,
      status: 'Completed'
    };

    render(<ActivityListItem activity={completedActivity} />);

    const badge = screen.getByText('Completed');
    expect(badge).toBeInTheDocument();
  });

  it('has hover effect styling', () => {
    const { container } = render(<ActivityListItem activity={mockActivity} />);

    const itemContainer = container.firstChild as HTMLElement;
    expect(itemContainer).toHaveClass('hover:border-gray-300');
  });
});
