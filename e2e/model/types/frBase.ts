export type FRResponse<T> = {
  success: boolean;
  result: T;
}

export type FRDescribeType = {
  allowDuplicates: boolean;
  createable: boolean;
  deleteable: boolean;
  fields: FRDescribeFieldsType[];
  idPrefix: string;
  isEntity: boolean;
  isShowModule: boolean;
  label: string;
  labelFields: string;
  name: string;
  retrieveable: boolean;
  updateable: boolean;
  relatedModules: FRRelatedModuleType[];
};

export type FRRelatedModuleType = {
  label: string;
  relatedModuleName: string;
  relationId: string;
};

export type FRDescribeFieldsType = {
  default: string;
  editable: boolean;
  isunique: boolean;
  label: string;
  mandatory: boolean;
  name: string;
  nullable: string;
  portaleditable: boolean;
  type: {
    name: string;
    defaultValue?: string;
    picklistValues?: {
      label: string;
      value: string;
    }[];
    refersTo?: string[];
    refersToLabelFields?: {
      [modulename: string]: {
        entityidfield: string;
        fields: string[];
      };
    };
  };
};

export type FRRetrieveItems = {
  [key: string]: string;
} & {
  ListItems?: {
    [key: string]: string;
  } & {
    LineItems: {
      [key: string]: string;
    }[];
    LineItems_FinalDetails: {
      [key: string]: {
        [key: string]: string;
      };
    }[];
  };
};