import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

import { library } from '@fortawesome/fontawesome-svg-core';
import {
  faUser, faLock, faSignOutAlt, faHome, faUsers, faBox,
  faChartBar, faChartLine, faCog, faBars, faTimes, faEye, faEdit,
  faTrash, faPlus, faSave, faSearch, faFilter, faDownload,
  faUpload, faFileExcel, faFilePdf, faCheck, faTimesCircle,
  faExclamationTriangle, faInfoCircle, faArrowLeft,
  faArrowRight, faChevronDown, faChevronUp, faCalendar,
  faMoneyBillWave, faTruck, faClipboardList, faBoxes,
  faWarehouse, faHistory, faExchangeAlt, faTools,
  faWrench, faHardHat, faCalendarCheck, faFileInvoiceDollar,
  faShoppingCart, faFileAlt, faCalculator,
  faMoneyCheckAlt, faHandHoldingUsd, faTags, faTag,
  faUndo, faCamera, faImages, faImage, faBarcode, faQrcode,
  faTrashAlt, faBan, faArrowUp, faArrowDown, faSync,
  faFileContract, faSignature, faFileInvoice, faCoins,
  faWallet, faCreditCard, faUniversity, faUserTie,
  faBuilding, faMapMarkerAlt, faPhone, faEnvelope, faGlobe,     // New icons
  faPalette, faComments, faShieldAlt, faKey,
  faUserCircle, faUmbrellaBeach, faPlusCircle, faFileSignature, faDatabase, faToolbox, faUserCog, faShoppingBag, faCartShopping,
  faTachometerAlt, faCalendarAlt, faDollarSign, faLandmark, faReceipt, faLaptop, faCubes, faCogs, faTrademark, faCar,
  faPrint, faPaperPlane, faEnvelopeOpen, faFunnelDollar, faChevronLeft, faChevronRight, faIdCard, faUsersCog,
  faHeadset, faChartPie, faTicketAlt, faBook,
  faClock, faCashRegister, faUserTimes, faHandshake, faHourglassHalf,
  faCalendarPlus, faUserPlus, faBell, faAddressBook, faSpinner, faDesktop,
  faCloudUploadAlt, faUnlink, faFileArchive, faPlug, faListOl, faFolderOpen, faTasks, faCheckCircle,
  faPen, faTrashCan,
  faBullseye, faBullhorn, faColumns, faTrophy, faInbox, faList,
  faBalanceScale, faTruckLoading, faMedal, faFileUpload, faUserCheck,
  faCrown, faShieldHalved, faBuildingShield, faMobileAlt, faQuestion,
  faBlog, faNewspaper, faShareAlt, faMagic, faExternalLinkAlt
} from '@fortawesome/free-solid-svg-icons';

import { faWhatsapp, faFacebook, faTwitter } from '@fortawesome/free-brands-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// Agrega los íconos a la librería
library.add(
  faUser, faLock, faSignOutAlt, faHome, faUsers, faBox,
  faChartBar, faChartLine, faCog, faBars, faTimes, faEye, faEdit,
  faTrash, faPlus, faSave, faSearch, faFilter, faDownload,
  faUpload, faFileExcel, faFilePdf, faCheck, faTimesCircle,
  faExclamationTriangle, faInfoCircle, faArrowLeft,
  faArrowRight, faChevronDown, faChevronUp, faCalendar,
  faMoneyBillWave, faTruck, faClipboardList, faBoxes,
  faWarehouse, faHistory, faExchangeAlt, faTools,
  faWrench, faHardHat, faCalendarCheck, faFileInvoiceDollar,
  faShoppingCart, faFileAlt, faCalculator,
  faMoneyCheckAlt, faHandHoldingUsd, faTags, faTag,
  faUndo, faCamera, faImages, faImage, faBarcode, faQrcode,
  faTrashAlt, faBan, faArrowUp, faArrowDown, faSync,
  faFileContract, faSignature, faFileInvoice, faCoins,
  faWallet, faCreditCard, faUniversity, faUserTie,
  faBuilding, faMapMarkerAlt, faPhone, faEnvelope, faGlobe,     // New icons
  faPalette, faComments, faShieldAlt, faKey,
  faUserCircle, faUmbrellaBeach, faPlusCircle, faFileSignature, faDatabase, faToolbox, faUserCog, faShoppingBag, faCartShopping,
  faTachometerAlt, faCalendarAlt, faDollarSign, faLandmark, faReceipt, faLaptop, faCubes, faCogs, faTrademark, faCar,
  faPrint, faPaperPlane, faEnvelopeOpen, faFunnelDollar, faChevronLeft, faChevronRight, faIdCard, faUsersCog,
  faHeadset, faChartPie, faTicketAlt, faBook,
  faClock, faCashRegister, faUserTimes, faHandshake, faHourglassHalf,
  faCalendarPlus, faUserPlus, faBell, faAddressBook, faSpinner, faDesktop,
  faCloudUploadAlt, faUnlink, faFileArchive, faPlug, faListOl, faFolderOpen, faTasks, faCheckCircle,
  faPen, faTrashCan,
  faWhatsapp, faList, faBalanceScale, faTruckLoading, faMedal, faFileUpload, faUserCheck,
  faCrown, faShieldHalved, faBuildingShield, faMobileAlt, faQuestion,
  faBlog, faNewspaper, faShareAlt, faMagic, faExternalLinkAlt,
  faFacebook, faTwitter
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .component('FontAwesomeIcon', FontAwesomeIcon);

    // Directiva para click fuera
    app.directive('click-outside', {
      mounted(el, binding) {
        el.clickOutsideEvent = function (event) {
          if (!(el === event.target || el.contains(event.target))) {
            binding.value(event, el);
          }
        };
        document.body.addEventListener('click', el.clickOutsideEvent);
      },
      unmounted(el) {
        document.body.removeEventListener('click', el.clickOutsideEvent);
      },
    });

    app.config.globalProperties.$can = (permissionOrRole) => {
      const auth = app.config.globalProperties.$page.props.auth;
      if (!auth || !auth.user) return false;

      // Check if user is admin (from is_admin flag)
      if (auth.user.is_admin) return true;

      const permissions = auth.user.permissions || [];
      const roles = auth.user.roles || [];

      // Also check if user has admin or super-admin in roles array (handles both string and object formats)
      const roleNames = roles.map(r => typeof r === 'string' ? r : r.name);
      if (roleNames.includes('admin') || roleNames.includes('super-admin')) return true;

      return permissions.includes(permissionOrRole) || roleNames.includes(permissionOrRole);
    };

    app.mount(el);
  },
  progress: {
    color: '#FF6B35',
  },
});
