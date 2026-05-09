export const isValidEmail = (email) => {
  if (!email) return false;
  // RFC 5322 compliant regex (simplified)
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(String(email).toLowerCase());
};
