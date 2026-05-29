export const isValidEmail = (email) => {
  if (!email) return false;
  // RFC 5322 compliant regex (simplified)
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const isFormatValid = re.test(String(email).toLowerCase());
  
  if (!isFormatValid) return false;

  // Custom project rule: emails ending in old.local are legacy placeholders
  if (String(email).toLowerCase().endsWith('old.local')) return false;

  return true;
};



