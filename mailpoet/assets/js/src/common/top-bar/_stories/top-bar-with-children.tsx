import { TopBar } from '../top-bar';
import { Button } from '../../button/button';

export default {
  title: 'Top Bar/With Children',
};

export function TopBarWithChildren() {
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
      <TopBar>
        <Button>Button</Button>
      </TopBar>
    </div>
  );
}
