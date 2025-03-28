import React from 'react';
import { NavLink, Link } from 'react-router';

const Header = () => {
  return (
    <header className="header">
      <div className="header__logo">

      </div>
      <nav className="header__nav">
        <NavLink to="/" className={({ isActive }) => isActive ? 'active': ''}>
          Home
        </NavLink>
        <NavLink to="/archive" className={({ isActive }) => isActive ? 'active': ''}>
          Archive
        </NavLink>
      </nav>
    </header>
  );
};

export default Header;