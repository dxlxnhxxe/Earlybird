import { sayHello } from '../../utility/functions';

describe('Utility Functions', () => {
  describe('sayHello', () => {
    it('returns a greeting message with the user name', () => {
      const result = sayHello('John');
      expect(result).toBe('Welcome John!');
    });

    it('works with different names', () => {
      expect(sayHello('Alice')).toBe('Welcome Alice!');
      expect(sayHello('Bob')).toBe('Welcome Bob!');
      expect(sayHello('Charlie')).toBe('Welcome Charlie!');
    });

    it('handles empty string', () => {
      const result = sayHello('');
      expect(result).toBe('Welcome !');
    });

    it('handles names with spaces', () => {
      const result = sayHello('John Doe');
      expect(result).toBe('Welcome John Doe!');
    });

    it('handles special characters', () => {
      expect(sayHello('Jean-Paul')).toBe('Welcome Jean-Paul!');
      expect(sayHello("O'Brien")).toBe("Welcome O'Brien!");
    });

    it('returns a string', () => {
      const result = sayHello('Test');
      expect(typeof result).toBe('string');
    });
  });
});
