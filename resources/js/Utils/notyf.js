// resources/js/utils/notyf.js
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

export const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  dismissible: true,
  ripple: true,
  types: [
    {
      type: 'success',
      background: 'linear-gradient(135deg, #10b981, #059669)',
      icon: { className: 'fas fa-check-circle', tagName: 'i', color: '#fff' }
    },
    {
      type: 'error',
      background: 'linear-gradient(135deg, #ef4444, #dc2626)',
      icon: { className: 'fas fa-exclamation-circle', tagName: 'i', color: '#fff' }
    },
    {
      type: 'warning',
      background: 'linear-gradient(135deg, #f59e0b, #d97706)',
      icon: { className: 'fas fa-exclamation-triangle', tagName: 'i', color: '#fff' }
    },
    {
      type: 'info',
      background: 'linear-gradient(135deg, #3b82f6, #2563eb)',
      icon: { className: 'fas fa-info-circle', tagName: 'i', color: '#fff' }
    }
  ],
});
