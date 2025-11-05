import React, { ReactElement } from 'react';
import {
  StyleContainer,
  StylesProps,
} from '@divi/module';
import { TurboBlogWlAttrs } from './types';

const ModuleStyles = ({
  settings,
  mode,
  state,
  noStyleTag,
  elements,
  attrs,
  orderClass,
}: StylesProps<TurboBlogWlAttrs>): ReactElement => {

  const attrsAny = attrs as any;

  // Helper: safely read responsive values, mirror PHP normalization
  const getResponsiveValue = (
    attrObj: any,
    attrName: string,
    device: 'desktop' | 'tablet' | 'phone',
    key?: string,
    fallback: string = ''
  ): string => {
    if (!attrObj) return fallback;

    const attr = attrObj[attrName];
    if (!attr || !attr.innerContent) return fallback;

    // Prefer the `value` object when present, but allow direct key as well.
    const deviceObj = attr.innerContent[device] || {};
    let value = deviceObj.value ?? deviceObj;

    // If a sub-key is provided (e.g. 'rowGap' or 'columnGap' or 'gridColumns'), drill into it.
    if (key && value && typeof value === 'object' && key in value) {
      value = value[key];
    }

    // If value is array -> join like PHP implodeThis
    if (Array.isArray(value)) {
      return value.map((v) => String(v).trim()).join(' ');
    }

    if (typeof value === 'string') {
      return value.trim();
    }

    // final fallback
    return fallback;
  };

  // Because your module.json uses separate attributes for gaps, try those names.
  const layoutType = (attrsAny?.layoutType?.innerContent?.desktop?.value) || 'off';

  let gridCss = '';

  if (layoutType === 'on') {
    // gridColumns in module.json stores the string under value.gridColumns
    const gridColsDesktop = getResponsiveValue(attrsAny, 'gridColumns', 'desktop', 'gridColumns', '1fr 1fr 1fr');

    // Gaps: use gridRowGap and gridColumnGap (per your module.json)
    const rowGapDesktop = getResponsiveValue(attrsAny, 'gridRowGap', 'desktop', 'rowGap', '30px');
    const colGapDesktop = getResponsiveValue(attrsAny, 'gridColumnGap', 'desktop', 'columnGap', '30px');

    gridCss = `
      ${orderClass} .turbo_blog_wl__post-items--grid {
        grid-template-columns: ${gridColsDesktop};
        row-gap: ${rowGapDesktop};
        column-gap: ${colGapDesktop};
      }
    `;

    // Tablet
    const gridColsTablet = getResponsiveValue(attrsAny, 'gridColumns', 'tablet', 'gridColumns');
    const rowGapTablet   = getResponsiveValue(attrsAny, 'gridRowGap', 'tablet', 'rowGap');
    const colGapTablet   = getResponsiveValue(attrsAny, 'gridColumnGap', 'tablet', 'columnGap');

    if (gridColsTablet || rowGapTablet || colGapTablet) {
      gridCss += `
        @media only screen and (max-width: 980px) {
          ${orderClass} .turbo_blog_wl__post-items--grid {
            ${gridColsTablet ? `grid-template-columns: ${gridColsTablet};` : ''}
            ${rowGapTablet ? `row-gap: ${rowGapTablet};` : ''}
            ${colGapTablet ? `column-gap: ${colGapTablet};` : ''}
          }
        }
      `;
    }

    // Phone
    const gridColsPhone = getResponsiveValue(attrsAny, 'gridColumns', 'phone', 'gridColumns');
    const rowGapPhone   = getResponsiveValue(attrsAny, 'gridRowGap', 'phone', 'rowGap');
    const colGapPhone   = getResponsiveValue(attrsAny, 'gridColumnGap', 'phone', 'columnGap');

    if (gridColsPhone || rowGapPhone || colGapPhone) {
      gridCss += `
        @media only screen and (max-width: 767px) {
          ${orderClass} .turbo_blog_wl__post-items--grid {
            ${gridColsPhone ? `grid-template-columns: ${gridColsPhone};` : ''}
            ${rowGapPhone ? `row-gap: ${rowGapPhone};` : ''}
            ${colGapPhone ? `column-gap: ${colGapPhone};` : ''}
          }
        }
      `;
    }
  }

  return (
    <>
      <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
        {elements.style({
          attrName: 'module',
          styleProps: {
            disabledOn: {
              disabledModuleVisibility: settings?.disabledModuleVisibility,
            },
            advancedStyles: [
              {
                componentName: "divi/text",
                props: {
                  selector:`${orderClass} .turbo_blog_wl__inner`,
                  attr:attrs?.module?.advanced?.text,
                }
              }
            ]
          },
        })}
        {elements.style({
          attrName: 'title',
        })}
        {elements.style({
          attrName: 'postTitle',
        })}
      </StyleContainer>
      {gridCss && !noStyleTag && (
        <style dangerouslySetInnerHTML={{ __html: gridCss }} />
      )}
    </>
  );
}

export {
  ModuleStyles,
};
