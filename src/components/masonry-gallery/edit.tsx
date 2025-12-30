import React from 'react';
import { ChildModulesContainer, ModuleContainer } from '@divi/module';

export const MasonryGalleryEdit = (props: any) => {
  const { attrs, elements, id, name, childrenIds } = props;
  const gap = attrs.gap?.innerContent?.desktop?.value?.gap || '10px';
  const minColumnWidth = attrs.minColumnWidth?.innerContent?.desktop?.value?.minColumnWidth || '250px';
  const rowHeight = attrs.rowHeight?.innerContent?.desktop?.value?.rowHeight || '200px';

  return (
    <ModuleContainer attrs={attrs} elements={elements} id={id} name={name}>
      {elements.styleComponents({ attrName: 'module' })}
      <div className="masonry_gallery__inner">
        {elements.render({ attrName: 'title' })}
        <div 
          className="masonry_gallery__grid"
          style={{
            display: 'grid',
            gridGap: gap,
            gridTemplateColumns: `repeat(auto-fit, minmax(${minColumnWidth}, 1fr))`,
            gridAutoRows: rowHeight,
            gridAutoFlow: 'dense',
          }}
        >
          <ChildModulesContainer ids={childrenIds} />
        </div>
      </div>
    </ModuleContainer>
  );
};