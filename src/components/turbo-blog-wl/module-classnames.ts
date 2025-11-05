import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { TurboBlogWlAttrs } from './types';


/**
 * Module classnames function for Dynamic Module.
 *
 * @since ??
 *
 * @param {ModuleClassnamesParams<TurboBlogWlAttrs>} param0 Function parameters.
 */
export const moduleClassnames = ({
  classnamesInstance,
  attrs,
}: ModuleClassnamesParams<TurboBlogWlAttrs>): void => {
  // Text Options.
  classnamesInstance.add(textOptionsClassnames(attrs?.module?.advanced?.text));
};
