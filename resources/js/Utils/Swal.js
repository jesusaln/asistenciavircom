import Swal from 'sweetalert2';

const CustomSwal = Swal.mixin({
    customClass: {
        popup: 'swal2-dark-mode-aware',
    },
    didOpen: (popup) => {
        if (document.documentElement.classList.contains('dark')) {
            popup.style.backgroundColor = '#1e293b'; // slate-800
            popup.style.color = '#f8fafc'; // slate-50
            const confirmBtn = popup.querySelector('.swal2-confirm');
            if (confirmBtn) {
                // Keep the primary color or adjust if needed
            }
            const title = popup.querySelector('.swal2-title');
            if (title) title.style.color = '#ffffff';
            
            const content = popup.querySelector('.swal2-html-container');
            if (content) content.style.color = '#cbd5e1'; // slate-300
        }
    }
});

export default CustomSwal;
