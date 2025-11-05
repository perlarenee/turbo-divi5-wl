import {
  type Metadata,
  type ModuleLibrary,
} from '@divi/types';
import { TurboBlogWlEdit } from './edit';
import metadata from './module.json';
import { TurboBlogWlAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';


export const turboBlogWl: ModuleLibrary.Module.RegisterDefinition<TurboBlogWlAttrs> = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values<TurboBlogWlAttrs>,
  placeholderContent,
  renderers: {
    edit: TurboBlogWlEdit,
  },
};
