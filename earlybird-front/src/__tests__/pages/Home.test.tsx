import React from 'react';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import '@testing-library/jest-dom';
import KioskHomePage, { users } from '../../pages/Home';

// Mock FullCalendar to avoid complex rendering
jest.mock('@fullcalendar/react', () => {
  return function MockFullCalendar(props: any) {
    return (
      <div data-testid="fullcalendar" data-initial-view={props.initialView}>
        Calendar Component
      </div>
    );
  };
});

describe('KioskHomePage Component', () => {
  const renderWithRouter = (component: React.ReactElement) => {
    return render(
      <BrowserRouter>
        {component}
      </BrowserRouter>
    );
  };

  it('renders without crashing', () => {
    renderWithRouter(<KioskHomePage />);
    expect(screen.getByTestId('fullcalendar')).toBeInTheDocument();
  });

  it('renders FullCalendar component', () => {
    renderWithRouter(<KioskHomePage />);
    expect(screen.getByText('Calendar Component')).toBeInTheDocument();
  });

  it('configures calendar with correct initial view', () => {
    renderWithRouter(<KioskHomePage />);
    const calendar = screen.getByTestId('fullcalendar');
    expect(calendar).toHaveAttribute('data-initial-view', 'timeGridWeek');
  });

  it('has correct structure', () => {
    const { container } = renderWithRouter(<KioskHomePage />);
    const app = container.querySelector('.app');
    expect(app).toBeInTheDocument();
  });
});

describe('users constant', () => {
  it('exports users array with correct structure', () => {
    expect(users).toHaveLength(7);
    expect(users[0]).toHaveProperty('id');
    expect(users[0]).toHaveProperty('name');
    expect(users[0]).toHaveProperty('status');
  });

  it('has users with different roles', () => {
    const adminUsers = users.filter(u => u.role === 'admin');
    const managerUsers = users.filter(u => u.role === 'manager');
    
    expect(adminUsers.length).toBeGreaterThan(0);
    expect(managerUsers.length).toBeGreaterThan(0);
  });

  it('has users with different statuses', () => {
    const statuses = users.map(u => u.status);
    expect(statuses).toContain('available');
    expect(statuses).toContain('unavailable');
    expect(statuses).toContain('away');
  });
});
