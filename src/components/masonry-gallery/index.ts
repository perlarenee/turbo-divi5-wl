import {
  type Metadata,
  type ModuleLibrary,
} from '@divi/types';
import { MasonryGalleryEdit } from './edit';
import metadata from './module.json';
import { MasonryGalleryAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';

export const masonryGallery: ModuleLibrary.Module.RegisterDefinition<MasonryGalleryAttrs> = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values<MasonryGalleryAttrs>,
  placeholderContent,
  renderers: {
    edit: MasonryGalleryEdit,
  },
};
