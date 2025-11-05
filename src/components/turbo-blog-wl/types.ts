// Divi dependencies.
import { ModuleEditProps } from '@divi/module-library';
import {
  FormatBreakpointStateAttr,
  InternalAttrs,
  type Element,
} from '@divi/types';

export interface TurboBlogWlAttrs extends InternalAttrs {
  module?: {
    meta?: Element.Meta.Attributes;
    advanced?: {
      link?: Element.Advanced.Link.Attributes;
      htmlAttributes?: Element.Advanced.IdClasses.Attributes;
      text?: Element.Advanced.Text.Attributes;
    };
    decoration?: Element.Decoration.PickedAttributes<
      'animation' |
      'background' |
      'border' |
      'boxShadow' |
      'disabledOn' |
      'filters' |
      'overflow' |
      'position' |
      'scroll' |
      'sizing' |
      'spacing' |
      'sticky' |
      'transform' |
      'transition' |
      'zIndex'
    >;
  };

  // Title
  title?: Element.Types.Title.Attributes;
  
  postItems?: {
    innerContent?: FormatBreakpointStateAttr<{
      postsNumber?: string;
    }>;
  };
  
  postType?: {
    innerContent?: FormatBreakpointStateAttr<{
      postType?: string;
    }>;
  };
  
  categories?: {
    innerContent?: FormatBreakpointStateAttr<{
      categories?: string;
    }>;
  };
  
  tags?: {
    innerContent?: FormatBreakpointStateAttr<{
      tags?: string;
    }>;
  };
  showFeaturedImage?: {
  innerContent?: FormatBreakpointStateAttr<{
    showFeaturedImage?: string;
  }>;
};

showAuthor?: {
  innerContent?: FormatBreakpointStateAttr<{
    showAuthor?: string;
  }>;
};

showDate?: {
  innerContent?: FormatBreakpointStateAttr<{
    showDate?: string;
  }>;
};

showCategories?: {
  innerContent?: FormatBreakpointStateAttr<{
    showCategories?: string;
  }>;
};

showTags?: {
  innerContent?: FormatBreakpointStateAttr<{
    showTags?: string;
  }>;
};
  postTitle?: {
    decoration?: Element.Decoration.PickedAttributes<'font'>;
  };
}

export type TurboBlogWlEditProps = ModuleEditProps<TurboBlogWlAttrs>;