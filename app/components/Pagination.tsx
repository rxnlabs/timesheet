import type { ReactNode } from 'react';
import React from 'react';
import { Link, NavLink } from 'react-router';

const Pagination = ({ children, link, type = null }: {children: ReactNode, link: string, type: string|null}) => {

  if (type === 'next') {
    return (
      <NavLink to={link} className="pagination-link is-next">
        {children}
      </NavLink>
    );
  } else if (type === 'previous') {
    return (
      <NavLink to={link} className="pagination-link is-previous">
        {children}
      </NavLink>
    );

    return (
      <NavLink to={link} className="pagination-link">{children}</NavLink>
    );
  }
};

export default Pagination;