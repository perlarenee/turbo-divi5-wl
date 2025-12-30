import {
  type Metadata,
  type ModuleLibrary,
} from '@divi/types';
import { MasonryGalleryItemEdit } from './edit';
import metadata from './module.json';
import { MasonryGalleryItemAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';

export const masonryGalleryItem: ModuleLibrary.Module.RegisterDefinition<MasonryGalleryItemAttrs> = {
  metadata: metadata as Metadata.Values<MasonryGalleryItemAttrs>,
  placeholderContent,
  renderers: {
    edit: MasonryGalleryItemEdit,
  },
};
