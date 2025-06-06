import { CategoriesItem, Category } from './categories-item';

type Props = {
  onSelect: (name: string) => void;
  categories: Category[];
  active: string;
};

function Categories({ onSelect, categories, active }: Props) {
  const cats = categories.map((category) => (
    <CategoriesItem
      {...category}
      key={category.name}
      onSelect={onSelect}
      active={category.name === active}
    />
  ));

  return (
    <div className="mailpoet-categories">
      <div className="components-tab-panel__tabs">{cats}</div>
    </div>
  );
}

Categories.displayName = 'Categories';
export { Categories };
