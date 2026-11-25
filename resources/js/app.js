

import Alpine from 'alpinejs';
import procedurePicker from './wizard';
import leafletChat from './chat';
import accessibleModal from './modal';

Alpine.data('procedurePicker', procedurePicker);
Alpine.data('leafletChat', leafletChat);
Alpine.data('accessibleModal', accessibleModal);

window.Alpine = Alpine;

Alpine.start();
