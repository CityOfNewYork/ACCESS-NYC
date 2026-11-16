import * as react_jsx_runtime from 'react/jsx-runtime';
import * as React$1 from 'react';
import React__default, { SVGProps } from 'react';
import { GroupBase, Props } from 'react-select';

interface ButtonBaseProps {
    tagName?: string;
    buttonType?: 'primary' | 'secondary' | 'link';
    classModifier?: string;
    children?: React__default.ReactNode;
    underlined?: boolean;
    fontSize?: 'xs' | 'sm' | 'base' | 'lg' | 'xl';
}

interface ButtonLinkProps extends ButtonBaseProps, React__default.ComponentPropsWithoutRef<"a"> {
}

/**
 * ButtonLink.
 * Display a link ("<a>" tag) with a default classname. Accepts other props from React `<a>` component.
 *
 * @param {React.ReactNode} children Children
 * @param {string} classModifier CSS class modifier (e.g "airwpsync-c-button--icon")
 * @param {('primary' | 'secondary' | 'link')} buttonType Button style type.
 * @param {underlined} Underlined? (default true if buttonType is "link")
 * @param {object} props Other HTML button props.
 *
 * @constructor
 */
declare const ButtonLink: ({ children, classModifier, underlined, fontSize, buttonType, ...props }: ButtonLinkProps) => react_jsx_runtime.JSX.Element;

interface ButtonIconBaseProps extends ButtonBaseProps {
    icon: 'arrow-right' | 'arrow-left' | 'open-external' | 'new-connection' | 'verify' | 'circle-loading' | 'cross' | 'sync' | 'download';
    iconPos?: 'before' | 'after';
}

interface ButtonLinkIconProps extends ButtonIconBaseProps, ButtonLinkProps {
}

/**
 * ButtonLinkIcon. <br />
 * Display a link (`<a>`) with an icon defined by the `icon` property.
 *
 * @param {React.ReactNode} children Children
 * @param {( 'arrow-right' | 'arrow-left' | 'open-external' | 'new-connection' | 'verify' | 'circle-loading' | 'cross' | 'sync' | 'download' )} icon Button's icon.
 * @param {('before' | 'after')} iconPos Icon position.
 * @param {object} ...props Other HTML button props.
 *
 * @constructor
 */
declare function ButtonLinkIcon({ children, icon, iconPos, ...props }: ButtonLinkIconProps): react_jsx_runtime.JSX.Element;

interface CalloutProps {
    children: React.ReactNode;
    icon?: React.ReactNode;
    bgColor: string;
    iconColor?: string;
    fontSize?: 'xs' | 'sm' | 'base' | 'lg' | 'xl';
    iconAlignment?: 'start' | 'center';
}
interface PrebuiltCalloutProps {
    type: 'neutral' | 'info' | 'success' | 'warning' | 'error' | 'loading';
    children: React.ReactNode;
    icon?: React.ReactNode;
    iconColor?: string;
    fontSize?: 'xs' | 'sm' | 'base' | 'lg' | 'xl';
    iconAlignment?: 'start' | 'center';
}

/**
 * Callout. <br />
 * Highlight a text and display meaningful icon.
 *
 * @param {React.ReactNode} children Children
 * @param {React.ReactNode} icon An icon (React node)
 * @param {string} iconColor A color from the theme (e.g "primary-50", "black")
 * @param {string} bgColor A color from the theme (e.g "primary-50", "black")
 * @param {('xs' | 'sm' | 'base' | 'lg' | 'xl')} fontSize Font size.
 * @param {('start' | 'center' )} iconAlignment Icon alignment
 * @constructor
 */
declare const Callout: ({ children, icon, iconColor, bgColor, fontSize, iconAlignment }: CalloutProps) => react_jsx_runtime.JSX.Element;

/**
 * Prebuilt callout.
 * Use `type` parameter to display preconfigured Callout component.
 *
 * @param {( 'neutral' | 'success' | 'warning' | 'error' | 'loading' )} type Callout type.
 * @param {React.ReactNode} children Children
 * @param {React.ReactNode} icon An icon (React node)
 * @param {('xs' | 'sm' | 'base' | 'lg' | 'xl')} fontSize Font size.
 * @constructor
 */
declare const PrebuiltCallout: ({ type, icon, children, fontSize, ...props }: PrebuiltCalloutProps) => react_jsx_runtime.JSX.Element | null;

interface HeadingProps {
    level: 1 | 2 | 3 | 4 | 5 | 6;
    semanticLevel?: 1 | 2 | 3 | 4 | 5 | 6;
    children: React.ReactNode;
    emoji?: string;
}

/**
 * Heading.
 *
 * `semanticLevel` follows `level` parameter if not defined.
 *
 * @param {React.ReactNode} children Children
 * @param {(1 | 2 | 3 | 4 | 5 | 6)} semanticLevel Semantic level (h1, h2, ...)
 * @param {(1 | 2 | 3 | 4 | 5 | 6)} level Level (visual)
 * @param {string} emoji Emoji that will be displayed in front of the title.
 * @return {Element}
 */
declare const Heading: ({ children, semanticLevel, emoji, level }: HeadingProps) => react_jsx_runtime.JSX.Element;

interface HelpLinkPreview {
    src: string;
    alt?: string;
}
interface HelpLinkProps {
    text: string;
    href: string;
    preview?: HelpLinkPreview;
    direction?: 'vertical' | 'horizontal';
}

/**
 * HelpLink. <br />
 * Display a link to an external help page with an optional preview.
 *
 * @param {string} text Link text
 * @param {string} href Link href
 * @param {{ src: string, alt?: string }} preview Preview config (img src and alt properties)
 * @param {('vertical' | 'horizontal')} direction Stack direction
 * @constructor
 */
declare const HelpLink: ({ text, href, preview, direction }: HelpLinkProps) => react_jsx_runtime.JSX.Element;

interface ParagraphProps extends React__default.ComponentPropsWithoutRef<"p"> {
    weight?: 'bold' | 'medium';
    fontSize?: 'xs' | 'sm' | 'base' | 'lg' | 'xl';
    color?: string;
    children: React__default.ReactNode;
}

/**
 * Paragraph.
 * @param {('bold' | 'medium')} weight Strong weight.
 * @param {('xs' | 'sm' | 'base' | 'lg' | 'xl')} fontSize Font size.
 * @param string color A color from the theme (e.g "primary-50", "black")
 * @param {React.ReactNode} children Children
 * @constructor
 */
declare const Paragraph: ({ children, style, color, weight, fontSize }: ParagraphProps) => react_jsx_runtime.JSX.Element;

interface StepIndex {
    stepKey: string;
    title: string;
}
interface StepsIndexProps extends React__default.ComponentPropsWithoutRef<"ol"> {
    steps: StepIndex[];
    currentStepKey: string;
    stepsDone?: string[];
}

/**
 * StepsIndex. <br />
 * Display an index of steps.
 *
 * @param {StepIndex[]} steps Steps.
 * @param {string} currentStepKey Current step key.
 * @constructor
 */
declare const StepsIndex: ({ steps, currentStepKey, stepsDone, ...props }: StepsIndexProps) => react_jsx_runtime.JSX.Element;

interface ButtonProps extends ButtonBaseProps, React__default.ComponentPropsWithoutRef<"button"> {
}

/**
 * Button.
 * Display a button with a default classname. Accepts other props from React `<button>` component.
 *
 * @param {React.ReactNode} children Children
 * @param {string} classModifier CSS class modifier (e.g "airwpsync-c-button--icon")
 * @param {boolean} disabled Disabled button prop.
 * @param {('primary' | 'secondary' | 'link')} buttonType Button style type.
 * @param {underlined} Underlined? (default true if buttonType is "link")
 * @param {object} props Other HTML button props.
 *
 * @constructor
 */
declare const Button: ({ children, classModifier, underlined, fontSize, disabled, buttonType, ...props }: ButtonProps) => react_jsx_runtime.JSX.Element;

interface ButtonGroupProps {
    children: React.ReactNode;
    gap?: 10 | 16 | 24;
}

/**
 *
 * @param {React.ReactNode} children Children
 * @param {(10 | 16 | 24)} gap Gap size
 * @constructor
 */
declare const ButtonGroup: ({ children, gap }: ButtonGroupProps) => react_jsx_runtime.JSX.Element;

interface ButtonIconProps extends ButtonIconBaseProps, ButtonProps {
}

/**
 * ButtonIcon. <br />
 * Display a button with an icon defined by the `icon` property.
 *
 * @param {React.ReactNode} children Children
 * @param {( 'arrow-right' | 'arrow-left' | 'open-external' | 'new-connection' | 'verify' | 'circle-loading' | 'cross' )} icon Button's icon.
 * @param {('before' | 'after')} iconPos Icon position.
 * @param {object} ...props Other HTML button props.
 *
 * @constructor
 */
declare function ButtonIcon({ children, icon, iconPos, ...props }: ButtonIconProps): react_jsx_runtime.JSX.Element;

interface BaseInputProps {
    label: React__default.ReactNode;
    instructions?: React__default.ReactNode;
    status?: 'idle' | 'valid' | 'invalid';
    errorMessage?: string;
    labelHidden?: boolean;
}

interface SelectLabelProps extends BaseInputProps {
}
interface SelectOption {
    value: string;
    label: string;
    isDisabled?: boolean;
    description?: string;
}

interface InputProps extends BaseInputProps, React__default.ComponentPropsWithoutRef<"input"> {
    id?: string;
    name?: string;
    type?: InputType;
    disabled?: boolean;
    value?: string;
    placeholder?: string;
    props?: object;
}

declare enum InputType {
    Text = "text",
    Password = "password",
    Number = "number",
    Date = "date"
}
/**
 * Input. <br />
 * Versatile input component. Displays a controlled html input with a label.
 *
 * @param {React.ReactNode} label
 * @param {string} id
 * @param {string} name
 * @param {string} value
 * @param {string} placeholder
 * @param {React.ReactNode} instructions
 * @param {('idle' | 'valid' | 'invalid')} status
 * @param {boolean} disabled
 * @param {('text' | 'password')} type
 * @param {object} ...props Other props from `<input />` React component.
 *
 * @constructor
 */
declare const Input: ({ label, labelHidden, id, name, value, placeholder, instructions, errorMessage, status, disabled, type, ...props }: InputProps) => react_jsx_runtime.JSX.Element;

interface FetchJsonResult {
    success: boolean;
    data?: object | Array<any>;
    error?: string;
}

interface FetcherProps {
    fetchFn: (key: string, formData: FormData) => Promise<FetchJsonResult>;
}
interface FilterGroupProps {
    conjunction: 'and' | 'or';
    filters: (FilterProps | FilterGroupProps)[];
}
interface FiltersTexts {
    addFilterBtnText: string;
    addFilterGroupBtnText: string;
    where: string;
    and: string;
    or: string;
    conjunction: string;
    maxNestedFilterGroupReached: string;
    checked: string;
    compare: {
        contains: string;
        does_not_contains: string;
        is: string;
        is_not: string;
        is_empty: string;
        is_not_empty: string;
        is_within: string;
        is_before: string;
        is_after: string;
        is_on_or_before: string;
        is_on_or_after: string;
        is_any_of: string;
        is_none_of: string;
        has_any_of: string;
        has_all_of: string;
        is_exactly: string;
        has_none_of: string;
        filenames_contains: string;
        has_file_type: string;
    };
    selectFieldPlaceholder: string;
    selectComparisonPlaceholder: string;
    inputValuePlaceholder: string;
    periods: {
        past_week: string;
        past_month: string;
        past_year: string;
        next_week: string;
        next_month: string;
        next_year: string;
        calendar_week: string;
        calendar_month: string;
        calendar_year: string;
        next_number_of_days: string;
        past_number_of_days: string;
        exact_date: string;
        today: string;
        tomorrow: string;
        yesterday: string;
        one_week_ago: string;
        one_week_from_now: string;
        one_month_ago: string;
        one_month_from_now: string;
        number_of_days_ago: string;
        number_of_days_from_now: string;
    };
    file_types: {
        image: string;
        text: string;
    };
}
interface FilterGroupInheritedProps extends FilterGroupProps, FetcherProps {
    texts: FiltersTexts;
    depth?: number;
    onChange: (props: FilterGroupProps) => void;
    airtableFilterOptions: FilterOption[];
    filtersComparisonOptions: FiltersComparisonOptions;
}
declare enum FilterPropsOperators {
    GreaterThan = ">",
    Is = "=",
    IsNot = "!=",
    GreaterOrEqualThan = ">=",
    LesserThan = "<",
    LesserOrEqualThan = "<=",
    Contains = "contains",
    DoesNotContain = "doesNotContain",
    IsEmpty = "isEmpty",
    IsNotEmpty = "isNotEmpty",
    IsWithin = "isWithin",
    IsAnyOf = "isAnyOf",
    IsNoneOf = "isNoneOf",
    HasAnyOf = "|",
    HasAllOf = "&",
    HasNoneOf = "hasNoneOf",
    Filename = "filename",
    Filetype = "filetype"
}
interface FilterProps {
    columnId: string;
    columnName: string;
    operator: FilterPropsOperators;
    value: string | string[] | PeriodValue;
}
interface FilterOption {
    name: string;
    id: string;
    type: keyof FiltersComparisonOptions;
    options?: SelectOption[];
}
interface FilterComparisonOption extends SelectOption {
    value: FilterPropsOperators;
    hideInput?: boolean;
    inputType?: InputType | 'period' | 'user' | 'multi_user' | 'select' | 'multi_select' | 'link_to_another_record' | 'filetype' | 'checkbox';
}
interface FiltersComparisonOptions {
    string: FilterComparisonOption[];
    number: FilterComparisonOption[];
    date: FilterComparisonOption[];
    user: FilterComparisonOption[];
    select: FilterComparisonOption[];
    multi_select: FilterComparisonOption[];
    link_to_another_record: FilterComparisonOption[];
    attachment: FilterComparisonOption[];
    checkbox: FilterComparisonOption[];
}
declare enum PeriodSelectValues {
    PastWeek = "pastWeek",
    PastMonth = "pastMonth",
    PastYear = "pastYear",
    NextWeek = "nextWeek",
    NextMonth = "nextMonth",
    NextYear = "nextYear",
    CalendarWeek = "calendarWeek",
    CalendarMonth = "calendarMonth",
    CalendarYear = "calendarYear",
    NextNumberOfDays = "nextNumberOfDays",
    PastNumberOfDays = "pastNumberOfDays",
    ExactDate = "exactDate",
    Today = "today",
    Tomorrow = "tomorrow",
    Yesterday = "yesterday",
    OneWeekAgo = "oneWeekAgo",
    OneWeekFromNow = "oneWeekFromNow",
    OneMonthAgo = "oneMonthAgo",
    OneMonthFromNow = "oneMonthFromNow",
    DaysAgo = "daysAgo",
    DaysFromNow = "daysFromNow"
}
interface PeriodValue {
    mode: PeriodSelectValues;
    numberOfDays?: string;
    input?: string;
}

/**
 * Filters. <br />
 * Filters based on Airtable's ones. Can be nested with groups (up to 3 levels). <br />
 *
 *
 * @constructor
 */
declare const Filters: ({ conjunction, filters, texts, onChange, airtableFilterOptions, fetchFn }: FilterGroupInheritedProps) => react_jsx_runtime.JSX.Element;

interface RowProps extends React__default.ComponentPropsWithoutRef<"div"> {
    children: React__default.ReactNode;
}

/**
 * FormRow. <br />
 * Manages spacing between form elements within the form row.
 *
 * @param {React.ReactNode} children Children
 *
 * @constructor
 */
declare const FormRow: ({ children, className, ...props }: RowProps) => react_jsx_runtime.JSX.Element;

interface Mapping {
    key?: string;
    wordpress: string;
    airtable: string;
    options: FieldOptions;
    airtableFieldName?: string;
    readonly?: boolean;
    error?: React.ReactNode;
}
interface FieldOptions {
    name?: string;
    form_options_values?: FormOptionsValues;
}
interface FormOptionsValues {
    [key: string]: string | number;
}
type SetMappingSignature = (mapping: Mapping[]) => void;
interface AirtableField {
    id: string;
    name: string;
    value: string;
    type: string;
    group: string;
}
interface WordPressField extends MappingOption {
}
type SupportedSource = string;
interface MappingOption {
    supported_sources: SupportedSource[];
    allow_multiple: boolean;
    value: string;
    enabled: boolean;
    label: string;
    notice?: React.ReactNode;
    form_options?: MappingOptionFormOptions[];
}
interface MappingOptionFormOptions {
    name: string;
    type: string;
    label: string;
}
interface MappingGroupOption {
    label: string;
    options: MappingOption[];
}
type MappingGroupOptions = Record<string, MappingGroupOption>;
type AirtableSelectFieldGroupOptions = Record<string, AirtableSelectFieldGroupOption>;
interface AirtableSelectFieldGroupOption {
    label: string;
    options: AirtableFieldOption[];
}
interface AirtableFieldOption extends AirtableField {
    enabled?: boolean;
}
type WordPressSelectFieldGroupOptions = Record<string, WordPressSelectFieldGroupOption>;
interface WordPressSelectFieldGroupOption extends MappingGroupOption {
}
type WordPressOptionEnablingStrategy = (option: MappingOption, wordPressFieldsSelected: string[], airtableFieldsSelected: string[]) => boolean;
type AirtableOptionEnablingStrategy = (option: AirtableField, wordPressFieldsSelected: string[], airtableFieldsSelected: string[]) => boolean;

declare class MappingManager {
    fields: AirtableField[];
    setMapping: SetMappingSignature;
    mapping: Mapping[];
    allSupportedAirtableTypes: SupportedSource[];
    private _airtableSelectFieldGroupsOptions;
    private _wordPressSelectFieldsOptions;
    indexedWordPressFields: Record<string, MappingOption>;
    indexedAirtablesFields: Record<string, AirtableField>;
    constructor(mappingInit: Mapping[], setMapping: SetMappingSignature, fields: AirtableField[], defaultMappingOptions: MappingGroupOptions, isOptionAvailable: (featurePath: string) => boolean);
    isOptionDisabled(option: MappingOption): boolean;
    getAirtableFirstOption(): string;
    addMappingRow(): void;
    updateAirtableField(index: number, airtableFieldId: string): void;
    updateWordPressField(index: number, wordPressFieldId: string): void;
    updateFieldOption(index: number, optionName: string, optionValue: any): void;
    removeMappingRow(index: number): void;
    moveMappingRow(oldIndex: number, newIndex: number): void;
    getWordPressFieldById(wordPressFieldId: string): MappingOption;
    getAirtableFieldById(airtableFieldId: string): AirtableField;
    get airtableSelectFieldGroupsOptions(): AirtableSelectFieldGroupOptions;
    get wordPressSelectFieldsOptions(): WordPressSelectFieldGroupOptions[];
}

interface MappingRowPropsBase extends React__default.ComponentPropsWithoutRef<"tr"> {
    index: number;
    airtableFieldId: string;
    wordPressFieldId: string;
    fieldOptions: FieldOptions;
    error?: React__default.ReactNode;
}
interface MappingRowProps extends MappingRowPropsBase, React__default.ComponentPropsWithoutRef<"tr"> {
    texts: MappingRowTexts;
    mappingManager: MappingManager;
}
interface MappingRowTexts {
    custom_field: string;
    import_as: string;
    required_field_indicator: string;
    airtable_field: string;
    fields: string;
    sort: string;
    remove: string;
}

declare function MappingRow({ texts, index, airtableFieldId, wordPressFieldId, fieldOptions, mappingManager, ...props }: MappingRowProps): react_jsx_runtime.JSX.Element;

interface MappingRowGroupProps extends React__default.ComponentPropsWithRef<"tbody"> {
    label: string;
}

declare const MappingRowTemplate$1: React$1.ForwardRefExoticComponent<Omit<MappingRowGroupProps, "ref"> & React$1.RefAttributes<HTMLTableSectionElement>>;

declare class MappingManagerReversed {
    fields: AirtableField[];
    wordPressFields: WordPressField[];
    setMapping: SetMappingSignature;
    mapping: Mapping[];
    allSupportedAirtableTypes: SupportedSource[];
    private _airtableSelectFieldGroupsOptions;
    private _wordPressSelectFieldsOptions;
    indexedWordPressFields: Record<string, MappingOption>;
    indexedAirtablesFields: Record<string, AirtableField>;
    template: Record<string, Mapping> | null;
    constructor(mappingInit: Mapping[], setMapping: SetMappingSignature, fields: AirtableField[], wordPressFields: WordPressField[], defaultMappingOptions: MappingGroupOptions, isOptionAvailable: (featurePath: string) => boolean, template?: Record<string, Mapping> | null, wordPressOptionEnablingStrategy?: WordPressOptionEnablingStrategy, airtableOptionEnablingStrategy?: AirtableOptionEnablingStrategy);
    getAirtableFirstOption(): string;
    getWordPressFirstOption(): string;
    addMappingRow(): void;
    updateAirtableField(index: number, airtableFieldId: string): void;
    updateWordPressField(index: number, wordPressFieldId: string): void;
    updateFieldOption(index: number, optionName: string, optionValue: any): void;
    removeMappingRow(index: number): void;
    moveMappingRow(oldIndex: number, newIndex: number): void;
    getWordPressFieldById(wordPressFieldId: string): MappingOption;
    getAirtableFieldById(airtableFieldId: string): AirtableField;
    get airtableSelectFieldGroupsOptions(): AirtableSelectFieldGroupOptions[];
    get wordPressSelectFieldsOptions(): WordPressSelectFieldGroupOptions;
}

interface MappingRowReversedProps extends MappingRowPropsBase {
    texts: MappingRowReversedTexts;
    mappingManager: MappingManagerReversed;
}
interface MappingRowReversedTexts {
    custom_field: string;
    required_field_indicator: string;
    airtable_field: string;
    fields: string;
    sort: string;
    remove: string;
    assigned_to: string;
    wordpress_field_placeholder: string;
}

/**
 * MappingRowReversed <br />
 * Manage mapping from WordPress fields to Airtable ones <br />
 * Use a template to auto Airtable field option
 *
 * @param {MappingRowReversedTexts} texts Texts
 * @param {number} index Row index.
 * @param {string} airtableFieldId Airtable field id
 * @param {string} wordPressFieldId WordPress field id
 * @param {FieldOptions} fieldOptions Field's options
 * @param {React.ReactNode} error Error
 * @param {MappingManagerReversed} mappingManager Mapping manager
 * @param {string} className Class name
 * @param {object} ...props Other props for <tr> HTML element.
 * @constructor
 */
declare function MappingRowReversed({ texts, index, airtableFieldId, wordPressFieldId, fieldOptions, error, mappingManager, className, ...props }: MappingRowReversedProps): react_jsx_runtime.JSX.Element;

interface MappingRowTemplateProps extends React__default.ComponentPropsWithoutRef<"tr"> {
    readOnly: boolean;
    status: MappingRowTemplateStatus;
    expectedAirtableFieldName: string;
    wordPressFieldId: string;
    mappingManager: MappingManager;
    errorMessage?: string;
    index: number;
    texts: MappingRowTemplateTexts;
    sortable?: boolean;
}
declare enum MappingRowTemplateStatus {
    Idle = "idle",
    Valid = "valid",
    Invalid = "invalid",
    Loading = "loading"
}
interface MappingRowTemplateTexts {
    assigned_to: string;
    remove: string;
    sort: string;
}

declare function MappingRowTemplate({ texts, status, index, expectedAirtableFieldName, wordPressFieldId, mappingManager, errorMessage, readOnly, sortable, ...props }: MappingRowTemplateProps): react_jsx_runtime.JSX.Element;

interface PasswordLikeInputProps extends InputProps {
    showPassword: boolean;
    showXCharsOnLeft: number;
    showXCharsOnRight: number;
}

/**
 * PasswordLikeInput. <br />
 *
 * @param {boolean} showPassword
 * @param {number} showXCharsOnLeft
 * @param {number} showXCharsOnRight
 * @param {string} value
 * @param {object} props Other props from Input.
 * @constructor
 */
declare const PasswordLikeInput: ({ showPassword, showXCharsOnLeft, showXCharsOnRight, value, ...props }: PasswordLikeInputProps) => react_jsx_runtime.JSX.Element;

/**
 * Select. <br />
 * Display a dropdown list. Accepts other props from <a href="https://react-select.com/props#statemanager-props" target="_blank">React Select</a> component appart from components, styles and theme.
 *
 * @param {React.ReactNode} label
 * @param {React.ReactNode} instructions
 * @param {('idle' | 'valid' | 'invalid')} status
 * @param {object} ...reactSelectProps Other props from React Select component.
 *
 * @constructor
 */
declare function Select<Option, IsMulti extends boolean = false, Group extends GroupBase<Option> = GroupBase<Option>>({ label, labelHidden, instructions, status, ...reactSelectProps }: Props<Option, IsMulti, Group> & SelectLabelProps): react_jsx_runtime.JSX.Element;

interface ToggleButtonProps extends BaseInputProps, React__default.ComponentPropsWithoutRef<"input"> {
    checked: boolean;
}

/**
 * ToggleButton.
 * Display a toggle button (html checkbox). Accepts other props from React `<input>` component.
 *
 * @param {boolean} checked Checked?
 * @param {boolean} disabled Disabled checkbox prop.
 * @param {object} ...props Other HTML checkbox props.
 *
 * @constructor
 */
declare const ToggleButton: ({ label, checked, disabled, ...props }: ToggleButtonProps) => react_jsx_runtime.JSX.Element;

interface ColumnProps {
    size: 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12;
    children: React__default.ReactNode;
}
interface ColumnsProps extends React__default.ComponentPropsWithoutRef<"div"> {
    columns: ColumnProps[];
}

/**
 * @typedef {{ size:  (1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12), children: {React.ReactNode} }} ColumnProps
 */
/**
 *
 * @param {ColumnProps[]} columns Columns
 * @param {object} ...props Other props for <div> HTML element.
 * @constructor
 */
declare const Columns: ({ columns, ...props }: ColumnsProps) => react_jsx_runtime.JSX.Element;

interface PanelProps {
    children: React.ReactNode;
}

/**
 * Panel.
 * @param {React.ReactNode} children Children
 * @constructor
 */
declare const Panel: ({ children }: PanelProps) => react_jsx_runtime.JSX.Element;

interface PopUpProps {
    isOpen: boolean;
    setIsOpen: (isOpen: boolean) => void;
    children: React.ReactNode;
}

/**
 * @typedef {function(isOpen: boolean):void} SetIsOpenFunction
 */
/**
 * PopUp. <br />
 * Set initial state of the pop-up with `isOpen` prop, pass `setIsOpen` function for the pop-up to be able to close itself.
 *
 * @param {boolean} isOpen Open state.
 * @param {SetIsOpenFunction} setIsOpen Function to toggle isOpen state.
 * @param {React.ReactNode} children Children
 * @constructor
 */
declare const PopUp: ({ isOpen, setIsOpen, children }: PopUpProps) => React$1.ReactPortal;

interface SpacerProps {
    size: 10 | 16 | 24 | 32 | 64;
}

/**
 * Spacer.
 *
 * @param {(10 | 16 | 24 | 32 | 64)} size Space size
 * @constructor
 */
declare function Spacer({ size }: SpacerProps): react_jsx_runtime.JSX.Element;

interface StepProps {
    stepKey: string;
    children: React.ReactNode;
}
interface StepsProps {
    currentStepKey: string;
    steps: StepProps[];
}

/**
 * @typedef {{ key: string, children: {React.ReactNode} }} StepProps
 */
/**
 * Steps.
 * Manage step transition.
 *
 * @param {string} currentStepKey Current step key.
 * @param {StepProps[]} steps Steps.
 *
 * @constructor
 */
declare const Steps: ({ currentStepKey, steps }: StepsProps) => react_jsx_runtime.JSX.Element;

interface IconProps extends SVGProps<SVGSVGElement> {
    title?: string;
    titleId?: string;
    className?: string;
}

declare const SvgArrowLeft: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgArrowRight: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgBase: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgChecked: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgCircleChecked: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgCircleCross: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgCircleInfo: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgCircleLoading: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgCircleQuestion: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgCircleTrash: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgCross: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgDownload: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgDropdownIndicator: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgGrab: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgInfo: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgLoading: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgNewConnection: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgOpenExternal: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgStepChecked: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgSync: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgTriangleExclamation: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const SvgVerify: ({ title, titleId, ...props }: IconProps) => react_jsx_runtime.JSX.Element;

declare const InlineLoading: () => react_jsx_runtime.JSX.Element;

declare const CircleLoadingAnimation: (props: IconProps) => react_jsx_runtime.JSX.Element;

interface ProgressBarProps {
    children: (id: string) => React__default.ReactNode;
    ratio: number;
    color?: string;
    bgColor?: string;
}

declare const ProgressBar: ({ children, ratio, color, bgColor }: ProgressBarProps) => react_jsx_runtime.JSX.Element;

export { SvgArrowLeft as ArrowLeft, SvgArrowRight as ArrowRight, SvgBase as Base, Button, ButtonGroup, ButtonIcon, ButtonLink, ButtonLinkIcon, Callout, SvgChecked as Checked, SvgCircleChecked as CircleChecked, SvgCircleCross as CircleCross, SvgCircleInfo as CircleInfo, SvgCircleLoading as CircleLoading, CircleLoadingAnimation, SvgCircleQuestion as CircleQuestion, SvgCircleTrash as CircleTrash, Columns, SvgCross as Cross, SvgDownload as Download, SvgDropdownIndicator as DropdownIndicator, Filters, FormRow, SvgGrab as Grab, Heading, HelpLink, SvgInfo as Info, InlineLoading, Input, SvgLoading as Loading, MappingRow, MappingRowTemplate$1 as MappingRowGroup, MappingRowReversed, MappingRowTemplate, SvgNewConnection as NewConnection, SvgOpenExternal as OpenExternal, Panel, Paragraph, PasswordLikeInput, PopUp, PrebuiltCallout, ProgressBar, Select, Spacer, SvgStepChecked as StepChecked, Steps, StepsIndex, SvgSync as Sync, ToggleButton, SvgTriangleExclamation as TriangleExclamation, SvgVerify as Verify };
