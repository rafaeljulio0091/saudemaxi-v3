export const date = (value) =>
    value
        ? new Intl.DateTimeFormat('pt-BR').format(
              new Date(value.length === 10 ? value + 'T12:00:00' : value),
          )
        : 'Não informado';
export const money = (value) =>
    new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
export const initials = (value) =>
    (value || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
export const greeting = () => {
    const hour = new Date().getHours();
    return hour < 12 ? 'Bom dia' : hour < 18 ? 'Boa tarde' : 'Boa noite';
};
