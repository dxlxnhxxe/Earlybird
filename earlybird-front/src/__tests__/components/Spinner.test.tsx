import React from 'react';
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import Spinner from '../../components/Spinner';

describe('Spinner Component', () => {
  it('renders with default size', () => {
    const { container } = render(<Spinner />);
    const wrapper = container.firstChild as HTMLElement;
    const spinner = wrapper.firstChild as HTMLElement;
    
    expect(wrapper).toHaveStyle({ width: '64px', height: '64px' }); // 48 + 16
    expect(spinner).toHaveStyle({ 
      width: '48px', 
      height: '48px',
      borderRadius: '50%'
    });
  });

  it('renders with custom size', () => {
    const { container } = render(<Spinner size={32} />);
    const wrapper = container.firstChild as HTMLElement;
    const spinner = wrapper.firstChild as HTMLElement;
    
    expect(wrapper).toHaveStyle({ width: '48px', height: '48px' }); // 32 + 16
    expect(spinner).toHaveStyle({ width: '32px', height: '32px' });
  });

  it('applies spin animation', () => {
    const { container } = render(<Spinner />);
    const spinner = (container.firstChild as HTMLElement).firstChild as HTMLElement;
    
    expect(spinner).toHaveStyle({ animation: 'spin 1s linear infinite' });
  });

  it('has correct border styles', () => {
    const { container } = render(<Spinner size={48} />);
    const spinner = (container.firstChild as HTMLElement).firstChild as HTMLElement;
    
    expect(spinner).toHaveStyle({
      border: '8px solid #f3f3f3',
      borderTop: '8px solid #3498db'
    });
  });

  it('is centered in its container', () => {
    const { container } = render(<Spinner />);
    const wrapper = container.firstChild as HTMLElement;
    
    expect(wrapper).toHaveStyle({
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    });
  });

  it('includes keyframes for animation', () => {
    const { container } = render(<Spinner />);
    const style = container.querySelector('style');
    
    expect(style?.textContent).toContain('@keyframes spin');
    expect(style?.textContent).toContain('transform: rotate(0deg)');
    expect(style?.textContent).toContain('transform: rotate(360deg)');
  });
});
