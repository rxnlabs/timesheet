import React from 'react';
import { useShiftContext } from '../contexts/ShiftContext';

interface NotificationProps {
  message?: string;
  type?: 'error' | 'warning' | 'success' | 'info';
  classes?: string;
}

const Notification: React.FC<NotificationProps> = ({ message, type = 'info', classes, ...atts }) => {
  const { formNotification } = useShiftContext();
  const allowedMessageTypes = ['error', 'warning', 'success', 'info'];

  if (!message && Object.prototype.hasOwnProperty.call(formNotification,'message')) {
    // @ts-expect-error: message is defined in the shift context
    message = formNotification.message;
  }

  if (!message && Object.prototype.hasOwnProperty.call(formNotification,'type')) {
    // @ts-expect-error: type is defined in the shift context
    type = formNotification.type;
  }

  if (!allowedMessageTypes.includes(type.toLowerCase())) {
    type = 'info';
  }

  return (
    <div className={`notification is-${type} ${classes}`.trim()} {...atts}>
      {message}
    </div>
  );
};

export default Notification;