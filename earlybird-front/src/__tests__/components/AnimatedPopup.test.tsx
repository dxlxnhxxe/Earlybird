import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import AnimatedPopup from '../../components/AnimatedPopup';

describe('AnimatedPopup Component', () => {
  beforeEach(() => {
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  it('renders nothing when closed', () => {
    const { container } = render(
      <AnimatedPopup open={false} title="Test Title" />
    );
    expect(container.firstChild).toBeNull();
  });

  it('renders popup when open', () => {
    render(
      <AnimatedPopup open={true} title="Test Title" subtitle="Test Subtitle" />
    );
    
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test Subtitle')).toBeInTheDocument();
  });

  it('displays countdown timer', () => {
    render(
      <AnimatedPopup 
        open={true} 
        title="Test" 
        countdown={5}
        countdownText="Closing in"
      />
    );
    
    expect(screen.getByText(/Closing in 5/)).toBeInTheDocument();
  });

  it('updates countdown every second', async () => {
    render(
      <AnimatedPopup 
        open={true} 
        title="Test" 
        countdown={3}
        countdownText="Closing in"
      />
    );
    
    expect(screen.getByText(/Closing in 3/)).toBeInTheDocument();
    
    jest.advanceTimersByTime(1000);
    await waitFor(() => {
      expect(screen.getByText(/Closing in 2/)).toBeInTheDocument();
    });
    
    jest.advanceTimersByTime(1000);
    await waitFor(() => {
      expect(screen.getByText(/Closing in 1/)).toBeInTheDocument();
    });
  });

  it('calls onClose after countdown finishes', async () => {
    const onCloseMock = jest.fn();
    render(
      <AnimatedPopup 
        open={true} 
        title="Test" 
        countdown={2}
        onClose={onCloseMock}
      />
    );
    
    // Advance countdown (2 seconds) + animation delay (500ms)
    jest.advanceTimersByTime(2500);
    
    await waitFor(() => {
      expect(onCloseMock).toHaveBeenCalledTimes(1);
    });
  });

  it('renders custom icon when provided', () => {
    const icon = <span data-testid="custom-icon">✓</span>;
    render(
      <AnimatedPopup open={true} title="Test" icon={icon} />
    );
    
    expect(screen.getByTestId('custom-icon')).toBeInTheDocument();
  });

  it('applies custom background color', () => {
    const { container } = render(
      <AnimatedPopup 
        open={true} 
        title="Test" 
        background="rgba(255,0,0,0.9)"
      />
    );
    
    const popup = container.firstChild as HTMLElement;
    expect(popup).toHaveStyle({ background: 'rgba(255,0,0,0.9)' });
  });

  it('has proper accessibility attributes', () => {
    render(<AnimatedPopup open={true} title="Test" />);
    
    const dialog = screen.getByRole('dialog');
    expect(dialog).toHaveAttribute('aria-modal', 'true');
  });

  it('resets countdown when reopened', async () => {
    const { rerender } = render(
      <AnimatedPopup open={true} title="Test" countdown={5} />
    );
    
    expect(screen.getByText(/5/)).toBeInTheDocument();
    
    jest.advanceTimersByTime(2000);
    await waitFor(() => {
      expect(screen.getByText(/3/)).toBeInTheDocument();
    });
    
    // Close and reopen
    rerender(<AnimatedPopup open={false} title="Test" countdown={5} />);
    rerender(<AnimatedPopup open={true} title="Test" countdown={5} />);
    
    expect(screen.getByText(/5/)).toBeInTheDocument();
  });
});
