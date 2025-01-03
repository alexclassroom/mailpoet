import { TopBar } from '../top-bar';

export default {
  title: 'Top Bar/No Children',
};

export function TopBarWithoutChildren() {
  return (
    <div
      style={{
        backgroundColor: '#bbb',
        width: '100%',
        position: 'fixed',
        height: '100%',
        top: '0px',
        left: '0px',
      }}
    >
      <TopBar />
    </div>
  );
}

export function TopBarWithoutChildrenWithNews() {
  return (
    <div
      style={{
        backgroundColor: '#bbb',
        width: '100%',
        position: 'fixed',
        height: '100%',
        top: '0px',
        left: '0px',
      }}
    >
      <TopBar />
    </div>
  );
}
