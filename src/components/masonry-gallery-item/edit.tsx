// External Dependencies.
import React, { ReactElement, useState, useEffect } from 'react';

// Divi Dependencies.
import { ModuleContainer } from '@divi/module';

/**
 * Masonry Gallery Item edit component of visual builder.
 */
export const MasonryGalleryItemEdit = (props: any): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
    parentAttrs,
  } = props;

  const imageData = attrs.image?.innerContent?.desktop?.value;
  const caption = attrs.caption?.innerContent?.desktop?.value || '';
  const showCaption = (attrs.showCaption?.innerContent?.desktop?.value || 'off') === 'on';
  
  const [sizeClass, setSizeClass] = useState('');
  
  // Get image URL
  let imageUrl = '';
  if (typeof imageData === 'string') {
    imageUrl = imageData;
  } else if (imageData && typeof imageData === 'object' && 'url' in imageData) {
    imageUrl = imageData.url;
  }

  // Detect image orientation and apply size class
  useEffect(() => {
    if (!imageUrl) {
      setSizeClass('');
      return;
    }

    // Check for manual spans first - they override auto-detection
    const rowSpan = parseInt(attrs.rowSpan?.innerContent?.desktop?.value?.rowSpan || '1');
    const colSpan = parseInt(attrs.columnSpan?.innerContent?.desktop?.value?.columnSpan || '1');
    const hasManualSpans = rowSpan > 1 || colSpan > 1;
    
    if (hasManualSpans) {
      // Manual spans override everything
      if (rowSpan > 1 && colSpan > 1) {
        setSizeClass('masonry_gallery__item--big');
      } else if (rowSpan > 1) {
        setSizeClass('masonry_gallery__item--tall');
      } else if (colSpan > 1) {
        setSizeClass('masonry_gallery__item--wide');
      }
      return;
    }

    // Check auto-detection
    const autoDetect = (parentAttrs?.autoDetectOrientation?.innerContent?.desktop?.value || 'on') === 'on';
    
    if (!autoDetect) {
      // No manual spans and auto-detect is OFF = default 1x1
      setSizeClass('');
      return;
    }

    // Auto mode - detect from image
    const img = new Image();
    img.onload = () => {
      const width = img.naturalWidth;
      const height = img.naturalHeight;
      const aspectRatio = width / height;
      
      let newClass = '';
      
      if (aspectRatio < 0.85) {
        // Portrait
        newClass = 'masonry_gallery__item--tall';
      } else if (aspectRatio > 1.15) {
        // Landscape
        newClass = 'masonry_gallery__item--wide';
      }
      
      // 5% chance for big (simulate the random logic)
      if (newClass && Math.random() > 0.95) {
        newClass = 'masonry_gallery__item--big';
      }
      
      setSizeClass(newClass);
    };
    img.src = imageUrl;
  }, [imageUrl, parentAttrs, attrs]);

  // Build class string
  const itemClass = `masonry_gallery__item${sizeClass ? ' ' + sizeClass : ''}`;

  // Build inline styles string for manual mode
  let manualStyleString = '';
  const rowSpan = parseInt(attrs.rowSpan?.innerContent?.desktop?.value?.rowSpan || '1');
  const colSpan = parseInt(attrs.columnSpan?.innerContent?.desktop?.value?.columnSpan || '1');
  
  // Apply manual spans if set (overrides auto-detection)
  const styles: string[] = [];
  if (rowSpan > 1) {
    styles.push(`grid-row: span ${rowSpan}`);
  }
  if (colSpan > 1) {
    styles.push(`grid-column: span ${colSpan}`);
  }
  if (styles.length > 0) {
    manualStyleString = styles.join('; ');
  }

  return (
    <ModuleContainer
      attrs={attrs}
      parentAttrs={parentAttrs}
      elements={elements}
      id={id}
      name={name}
      htmlAttrs={{
        className: itemClass,
        ...(manualStyleString ? { style: manualStyleString } : {}),
      }}
    >
      {elements.styleComponents({
        attrName: 'module',
      })}
      <div className="masonry_gallery__item-content">
        {imageUrl ? (
          <>
            <img 
              src={imageUrl} 
              alt={caption || ''}
              style={{
                width: '100%',
                height: '100%',
                objectFit: 'cover',
                display: 'block',
              }}
            />
            {showCaption && caption && (
              <div className="masonry_gallery__caption">
                {caption}
              </div>
            )}
          </>
        ) : (
          <div className="masonry_gallery_item__preview">
            <div className="masonry_gallery_item__preview-icon">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" strokeWidth="2" fill="none" strokeDasharray="4 4"/>
                <path d="M12 8V16M8 12H16" stroke="currentColor" strokeWidth="2"/>
              </svg>
              <p>Add image</p>
            </div>
          </div>
        )}
      </div>
    </ModuleContainer>
  );
};