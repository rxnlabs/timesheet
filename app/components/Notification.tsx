import React from 'react';
import { useShiftContext } from '../contexts/ShiftContext';

/**
 * Represents the properties for configuring a notification.
 *
 * @interface NotificationProps
 * @property {string} [message] - The message content to be displayed in the notification.
 * @property {'error' | 'warning' | 'success' | 'info'} [type] - Specifies the type of the notification, determining its purpose or visual representation.
 * @property {string} [classes] - Additional CSS classes for styling the notification.
 */
interface NotificationProps {
  message?: string;
  type?: 'error' | 'warning' | 'success' | 'info';
  classes?: string;
}

/**
 * Notification component for displaying a notification message with a specified type such as
 * "error", "warning", "success", or "info." If no `message` or `type` is explicitly provided,
 *
 * Props:
 * - `message` (string): The content of the notification to display.
 * - `type` (string): The type of the notification, determining its styling. Allowed values are
 *   "error", "warning", "success", and "info". Defaults to "info."
 * - `classes` (string): Additional CSS classes to append to the notification container.
 * - `...atts` (object): Additional attributes to spread onto the notification container.
 */
const Notification: React.FC<NotificationProps> = ({ message, type = 'info', classes, ...atts }) => {
  const { state } = useShiftContext();
  const allowedMessageTypes = ['error', 'warning', 'success', 'info'];

  if (!message && state.formNotification !== null && Object.hasOwn(state.formNotification,'message')) {
    // @ts-expect-error: message is defined in the shift context
    message = state.formNotification.message;
  }

  if (!message && state.formNotification !== null && Object.hasOwn(state.formNotification,'type')) {
    // @ts-expect-error: type is defined in the shift context
    type = state.formNotification.type;
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