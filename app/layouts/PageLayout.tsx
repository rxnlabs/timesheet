import React, { Fragment, type ReactNode } from 'react';
import { Outlet } from 'react-router';
import Header from '../components/Header';
import Footer from '../components/Footer';

const PageLayout = ({ children }: {children: ReactNode}): React.JSX.Element => {
  return (
    <Fragment>
      <Header/>
      <main>
        <Outlet/>
        {children}
      </main>
      <Footer/>
    </Fragment>
  );
};

export default PageLayout;