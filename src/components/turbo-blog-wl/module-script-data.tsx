import React, {
  Fragment,
  ReactElement,
} from 'react';

import {
  ModuleScriptDataProps,
} from '@divi/module';
import { TurboBlogWlAttrs } from './types';


/**
 * Dynamic module's script data component.
 *
 * @since ??
 *
 * @param {ModuleScriptDataProps<TurboBlogWlAttrs>} props React component props.
 *
 * @returns {ReactElement}
 */
export const ModuleScriptData = ({
  elements,
}: ModuleScriptDataProps<TurboBlogWlAttrs>): ReactElement => (
  <Fragment>
    {elements.scriptData({
      attrName: 'module',
    })}
  </Fragment>
);

