import { describe, it, expect } from 'vitest'
import { cn, sleep, exportToCSV, getPageNumbers } from '../utils'

describe('Utils', () => {
  describe('cn - className utility', () => {
    it('merges class names correctly', () => {
      const result = cn('text-red-500', 'bg-blue-500')
      expect(result).toContain('text-red-500')
      expect(result).toContain('bg-blue-500')
    })

    it('handles conditional classes', () => {
      const result = cn('base-class', false && 'hidden', true && 'visible')
      expect(result).toContain('base-class')
      expect(result).toContain('visible')
      expect(result).not.toContain('hidden')
    })

    it('handles undefined and null', () => {
      const result = cn('base', undefined, null, 'valid')
      expect(result).toContain('base')
      expect(result).toContain('valid')
    })

    it('merges Tailwind classes correctly', () => {
      const result = cn('px-2', 'px-4')
      // Should keep only px-4 due to tailwind-merge
      expect(result).toBe('px-4')
    })
  })

  describe('sleep', () => {
    it('resolves after default time (1000ms)', async () => {
      const start = Date.now()
      await sleep()
      const duration = Date.now() - start
      expect(duration).toBeGreaterThanOrEqual(1000)
      expect(duration).toBeLessThan(1100)
    })

    it('resolves after specified time', async () => {
      const start = Date.now()
      await sleep(500)
      const duration = Date.now() - start
      expect(duration).toBeGreaterThanOrEqual(500)
      expect(duration).toBeLessThan(600)
    })

    it('returns a Promise', () => {
      const result = sleep(100)
      expect(result).toBeInstanceOf(Promise)
    })
  })

  describe('exportToCSV', () => {
    let link: HTMLAnchorElement
    let originalCreateElement: any
    let clickSpy: any

    beforeEach(() => {
      originalCreateElement = document.createElement
      clickSpy = vi.fn()
      
      document.createElement = vi.fn((tag: string) => {
        if (tag === 'a') {
          link = originalCreateElement.call(document, tag) as HTMLAnchorElement
          link.click = clickSpy
          return link
        }
        return originalCreateElement.call(document, tag)
      }) as any
    })

    afterEach(() => {
      document.createElement = originalCreateElement
    })

    it('exports simple data to CSV', () => {
      const data = [
        { id: 1, name: 'John' },
        { id: 2, name: 'Jane' }
      ]
      
      exportToCSV(data, 'test')
      
      expect(clickSpy).toHaveBeenCalled()
      expect(link.download).toBe('test.csv')
    })

    it('handles data with commas and quotes', () => {
      const data = [
        { name: 'John, Doe', quote: 'He said "hello"' }
      ]
      
      exportToCSV(data, 'test')
      expect(clickSpy).toHaveBeenCalled()
    })

    it('does nothing with empty data', () => {
      const consoleWarn = vi.spyOn(console, 'warn').mockImplementation(() => {})
      
      exportToCSV([], 'test')
      
      expect(consoleWarn).toHaveBeenCalledWith('No data to export')
      expect(clickSpy).not.toHaveBeenCalled()
      
      consoleWarn.mockRestore()
    })

    it('uses default filename when not provided', () => {
      const data = [{ id: 1 }]
      exportToCSV(data)
      
      expect(link.download).toBe('export.csv')
    })

    it('handles nested objects', () => {
      const data = [
        { id: 1, user: { name: 'John', age: 30 } }
      ]
      
      exportToCSV(data, 'test')
      expect(clickSpy).toHaveBeenCalled()
    })
  })

  describe('getPageNumbers', () => {
    it('shows all pages when total is 5 or less', () => {
      expect(getPageNumbers(1, 5)).toEqual([1, 2, 3, 4, 5])
      expect(getPageNumbers(3, 4)).toEqual([1, 2, 3, 4])
      expect(getPageNumbers(1, 3)).toEqual([1, 2, 3])
    })

    it('shows correct pages near beginning', () => {
      const result = getPageNumbers(2, 10)
      expect(result).toEqual([1, 2, 3, 4, '...', 10])
    })

    it('shows correct pages in middle', () => {
      const result = getPageNumbers(5, 10)
      expect(result).toContain(1)
      expect(result).toContain(5)
      expect(result).toContain(10)
      expect(result).toContain('...')
    })

    it('shows correct pages near end', () => {
      const result = getPageNumbers(9, 10)
      expect(result).toEqual([1, '...', 7, 8, 9, 10])
    })

    it('handles edge case with current page 1', () => {
      const result = getPageNumbers(1, 10)
      expect(result[0]).toBe(1)
      expect(result[result.length - 1]).toBe(10)
    })

    it('handles edge case with current page equals total', () => {
      const result = getPageNumbers(10, 10)
      expect(result).toEqual([1, '...', 7, 8, 9, 10])
    })

    it('returns correct array length', () => {
      const result = getPageNumbers(5, 20)
      expect(result.length).toBeGreaterThan(0)
      expect(result.length).toBeLessThanOrEqual(7) // Max: 1 + ... + 3 pages + ... + last
    })
  })
})
