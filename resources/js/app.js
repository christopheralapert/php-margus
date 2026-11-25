

import Alpine from 'alpinejs';
import procedurePicker from './wizard';
import leafletChat from './chat';

Alpine.data('procedurePicker', procedurePicker);
Alpine.data('leafletChat', leafletChat);

window.Alpine = Alpine;

Alpine.start();
