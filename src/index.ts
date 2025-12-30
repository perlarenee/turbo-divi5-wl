import { omit } from 'lodash';

import { addAction } from '@wordpress/hooks';

import { registerModule } from '@divi/module-library';

import { turboBlogWl } from './components/turbo-blog-wl';
import { masonryGallery } from './components/masonry-gallery'; 
import { masonryGalleryItem } from './components/masonry-gallery-item'; 

import './module-icons';

// Register modules.
addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'extensionExample', () => {
  registerModule(turboBlogWl.metadata, omit(turboBlogWl, 'metadata'));  
  registerModule(masonryGallery.metadata, omit(masonryGallery, 'metadata'));
  registerModule(masonryGalleryItem.metadata, omit(masonryGalleryItem, 'metadata'));
});
