import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import DateTime from '../../components/DateTime';

describe('DateTime Component', () => {
  beforeEach(() => {
    jest.useFakeTimers();
    jest.setSystemTime(new Date('2025-01-15T14:30:00'));
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  it('renders the current date and time correctly', () => {
    render(<DateTime />);
    
    expect(screen.getByText(/Wednesday, 15 Jan/)).toBeInTheDocument();
    expect(screen.getByText('14:30')).toBeInTheDocument();
  });

  it('updates time every second', async () => {
    render(<DateTime />);
    
    expect(screen.getByText('14:30')).toBeInTheDocument();
    
    // Advance time by 61 seconds
    jest.advanceTimersByTime(61000);
    
    await waitFor(() => {
      expect(screen.getByText('14:31')).toBeInTheDocument();
    });
  });

  it('displays day name correctly', () => {
    render(<DateTime />);
    expect(screen.getByText(/Wednesday/)).toBeInTheDocument();
  });

  it('formats time with leading zeros', () => {
    jest.setSystemTime(new Date('2025-01-15T09:05:00'));
    render(<DateTime />);
    
    expect(screen.getByText('09:05')).toBeInTheDocument();
  });

  it('cleans up interval on unmount', () => {
    const { unmount } = render(<DateTime />);
    const clearIntervalSpy = jest.spyOn(global, 'clearInterval');
    
    unmount();
    
    expect(clearIntervalSpy).toHaveBeenCalled();
    clearIntervalSpy.mockRestore();
  });
});
